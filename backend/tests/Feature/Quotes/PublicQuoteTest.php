<?php

declare(strict_types=1);

use App\Modules\Media\Models\Media;
use App\Modules\Quotes\Actions\AddQuoteItem;
use App\Modules\Quotes\Actions\SendQuote;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteApproval;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Orçamento enviado com 2 itens; devolve [quote, token, técnico]. */
function sentQuote(): array
{
    $tech = actingAsRole();
    $type = oilChangeType();
    $vehicle = makeVehicle();
    $quoteId = test()->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'mileage' => 42000,
        'items' => [oilItem($tech, $type), productItem($tech)],
    ])->json('data.id');
    $link = test()->postJson("/api/quotes/{$quoteId}/send")->json('link');
    $quote = Quote::findOrFail($quoteId);

    // O cliente final não está logado nem tem tenant no contexto.
    app('auth')->forgetGuards();
    TenantContext::set(null);

    return [$quote, substr($link, -64), $tech];
}

/** Lê dados como a oficina dona do orçamento (o teste roda sem tenant, como o cliente final). */
function asOwnerOf(Quote $quote, Closure $callback): mixed
{
    return TenantContext::run($quote->tenant_id, $callback);
}

function uploadSignature(Quote $quote, string $token, ?string $content = null): void
{
    test()->postJson("/api/public/quotes/{$token}/signature-upload-url", ['size' => 2048])
        ->assertOk()
        ->assertJsonStructure(['data' => ['url', 'headers', 'expires_at']]);

    $media = asOwnerOf($quote, fn () => Media::query()->latest()->firstOrFail());
    // simula o PUT direto do navegador no R2
    Storage::disk('r2')->put($media->r2_key, $content ?? "\x89PNG\r\n\x1a\n".str_repeat('x', 2040));
}

beforeEach(fn () => Storage::fake('r2'));

it('cliente abre o link: marca visto e não vê ids internos', function () {
    [$quote, $token] = sentQuote();

    $response = $this->getJson("/api/public/quotes/{$token}")
        ->assertOk()
        ->assertJsonPath('data.status', 'viewed')
        ->assertJsonPath('data.items.0.position', 1)
        ->assertJsonPath('data.items.0.details.1.label', 'Viscosidade');

    expect($response->getContent())->not->toContain($quote->id)
        ->not->toContain($quote->tenant_id)
        ->not->toContain($quote->customer_id);
    expect($quote->refresh()->status->value)->toBe('viewed');
});

it('token inválido dá 404', function () {
    $this->getJson('/api/public/quotes/'.str_repeat('a', 64))->assertNotFound();
    $this->getJson('/api/public/quotes/curto')->assertNotFound();
});

it('link vencido expira o orçamento', function () {
    [$quote, $token] = sentQuote();
    $this->travel(8)->days();

    $this->getJson("/api/public/quotes/{$token}")->assertStatus(410);
    expect($quote->refresh()->status->value)->toBe('expired');
});

it('aprovação parcial com assinatura gera a OS automaticamente com os itens aprovados', function () {
    [$quote, $token, $tech] = sentQuote();
    uploadSignature($quote, $token);

    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João da Silva', 'items' => [1]])
        ->assertOk();

    $quote->refresh();
    $approval = asOwnerOf($quote, fn () => QuoteApproval::query()->where('quote_id', $quote->id)->firstOrFail());
    expect($quote->status->value)->toBe('approved')
        ->and($approval->signer_name)->toBe('João da Silva')
        ->and($approval->approved_item_ids)->toHaveCount(1)
        ->and($approval->ip)->not->toBeEmpty()
        ->and($approval->signature_key)->toEndWith('.png');

    [$order, $items] = asOwnerOf($quote, function () use ($quote) {
        $order = ServiceOrder::query()->where('quote_id', $quote->id)->firstOrFail();

        return [$order, $order->items()->get()];
    });
    expect($order->origin->value)->toBe('from_quote')
        ->and($order->status->value)->toBe('open')
        ->and($order->number)->toBe(1)
        ->and($order->mileage_in)->toBe(42000)
        ->and($order->total_cents)->toBe(8000)
        ->and($items)->toHaveCount(1)
        ->and($items[0]->technician_id)->toBe($tech->id)
        ->and($items[0]->fields['viscosity'])->toBe('5W30');
});

it('aprovação exige assinatura enviada', function () {
    [, $token] = sentQuote();

    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1, 2]])
        ->assertUnprocessable()->assertJsonValidationErrors('signature');

    $this->postJson("/api/public/quotes/{$token}/signature-upload-url", ['size' => 2048])->assertOk();
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1, 2]])
        ->assertUnprocessable()->assertJsonValidationErrors('media'); // URL pedida mas arquivo não subiu
});

it('recusa item inexistente e aprovação repetida', function () {
    [$quote, $token] = sentQuote();
    uploadSignature($quote, $token);

    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [9]])
        ->assertUnprocessable()->assertJsonValidationErrors('items');
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1, 2]])->assertOk();
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1, 2]])->assertUnprocessable();

    expect(asOwnerOf($quote, fn () => ServiceOrder::count()))->toBe(1);
});

it('assinatura só aceita PNG até 2 MB', function () {
    [, $token] = sentQuote();

    $this->postJson("/api/public/quotes/{$token}/signature-upload-url", ['size' => 3 * 1024 * 1024])
        ->assertUnprocessable()->assertJsonValidationErrors('size');
});

it('cliente recusa o orçamento', function () {
    [$quote, $token] = sentQuote();

    $this->postJson("/api/public/quotes/{$token}/reject", ['reason' => 'Caro demais'])->assertOk();

    expect($quote->refresh()->status->value)->toBe('rejected')
        ->and($quote->rejection_reason)->toBe('Caro demais');
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1]])->assertUnprocessable();
});

it('aprovação é imutável', function () {
    [$quote, $token] = sentQuote();
    uploadSignature($quote, $token);
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1]])->assertOk();
    asOwnerOf($quote, fn () => QuoteApproval::query()->firstOrFail()->update(['signer_name' => 'Outro']));
})->throws(LogicException::class);

it('link morre depois do prazo mesmo com orçamento já aprovado', function () {
    [$quote, $token] = sentQuote();
    uploadSignature($quote, $token);
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1]])->assertOk();
    $this->travel(8)->days();

    $this->getJson("/api/public/quotes/{$token}")->assertStatus(410);
    expect($quote->refresh()->status->value)->toBe('approved');
});

it('recusa assinatura que não é PNG de verdade', function () {
    [$quote, $token] = sentQuote();
    uploadSignature($quote, $token, '<?php echo 1; '.str_repeat('x', 100));

    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1]])
        ->assertUnprocessable()->assertJsonValidationErrors('media');
    expect(asOwnerOf($quote, fn () => Media::query()->latest()->first()?->status->value))->toBe('failed');
});

it('limita pedidos de URL de assinatura por link', function () {
    [, $token] = sentQuote();

    foreach (range(1, 10) as $i) {
        $this->postJson("/api/public/quotes/{$token}/signature-upload-url", ['size' => 2048])->assertOk();
    }
    $this->postJson("/api/public/quotes/{$token}/signature-upload-url", ['size' => 2048])->assertTooManyRequests();
});

it('reenvio enquanto o cliente assinava invalida a aprovação do link antigo', function () {
    [$quote, $token, $tech] = sentQuote();
    uploadSignature($quote, $token);

    // a oficina edita o orçamento (nova versão, link antigo anulado) antes do cliente concluir
    asOwnerOf($quote, fn () => app(AddQuoteItem::class)->handle(
        $quote, QuoteItemData::fromArray(productItem($tech))
    ));

    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1]])->assertNotFound();
    expect($quote->refresh()->status->value)->toBe('draft')->and($quote->version)->toBe(2);
});

it('edição com objeto desatualizado não desfaz orçamento aprovado', function () {
    [$quote, $token, $tech] = sentQuote();
    $stale = asOwnerOf($quote, fn () => Quote::findOrFail($quote->id)); // carregado como "viewed/sent"
    uploadSignature($quote, $token);
    $this->postJson("/api/public/quotes/{$token}/approve", ['signer_name' => 'João', 'items' => [1]])->assertOk();

    expect(fn () => asOwnerOf($quote, fn () => app(AddQuoteItem::class)->handle(
        $stale, QuoteItemData::fromArray(productItem($tech))
    )))->toThrow(ValidationException::class);
    expect(fn () => asOwnerOf($quote, fn () => app(SendQuote::class)->handle($stale)))
        ->toThrow(ValidationException::class);
    expect($quote->refresh()->status->value)->toBe('approved')->and($quote->version)->toBe(1);
});
