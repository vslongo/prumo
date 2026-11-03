<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Media\Actions\PurgeStalePendingMedia;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use App\Modules\Quotes\Models\Quote;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('r2'));

function jpegBytes(int $width = 800): string
{
    $image = imagecreatetruecolor($width, (int) ($width * 0.75));
    ob_start();
    imagejpeg($image);

    return (string) ob_get_clean();
}

/** Pede URL, simula o PUT do navegador no R2 e confirma. Devolve o id da mídia. */
function uploadItemPhoto(string $itemPhotosUrl, ?string $content = null): string
{
    $mediaId = test()->postJson($itemPhotosUrl, ['mime' => 'image/jpeg', 'size' => 50_000])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['media_id', 'url', 'headers', 'expires_at']])
        ->json('data.media_id');

    Storage::disk('r2')->put(Media::query()->findOrFail($mediaId)->r2_key, $content ?? jpegBytes());
    test()->postJson("{$itemPhotosUrl}/{$mediaId}/confirm")->assertNoContent();

    return $mediaId;
}

/** Orçamento direto pelo model (sem depender de helpers de outros arquivos de teste). */
function photoQuote(string $createdBy, int $items = 1): Quote
{
    $vehicle = makeVehicle();
    $quote = Quote::create(['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'created_by' => $createdBy]);
    for ($i = 1; $i <= $items; $i++) {
        $quote->items()->create([
            'position' => $i, 'kind' => 'product', 'description' => "Peça {$i}", 'technician_id' => $createdBy,
            'quantity' => '1', 'unit_price_cents' => 1000, 'total_cents' => 1000, 'fields' => [], 'fields_schema_snapshot' => [],
        ]);
    }

    return $quote;
}

function photoOrder(string $createdBy, ServiceOrderStatus $status = ServiceOrderStatus::Open, ?string $quoteItemId = null, ?string $quoteId = null): ServiceOrder
{
    $vehicle = makeVehicle();
    $order = ServiceOrder::create([
        'number' => (int) ServiceOrder::query()->max('number') + 1, 'origin' => $quoteId ? 'from_quote' : 'direct', 'quote_id' => $quoteId,
        'customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'opened_by' => $createdBy, 'status' => $status->value,
    ]);
    $order->items()->create([
        'position' => 1, 'quote_item_id' => $quoteItemId, 'kind' => 'product', 'description' => 'Pastilha',
        'technician_id' => $createdBy, 'quantity' => '1', 'unit_price_cents' => 100, 'total_cents' => 100,
        'fields' => [], 'fields_schema_snapshot' => [],
    ]);

    return $order;
}

function quoteWithItem(): array
{
    $tech = actingAsRole(UserRole::Technician);
    $quote = photoQuote($tech->id);

    return [$quote, $quote->items()->firstOrFail()];
}

it('técnico anexa foto ao item do orçamento, com miniatura e URL assinada', function () {
    [$quote, $item] = quoteWithItem();

    $mediaId = uploadItemPhoto("/api/quotes/{$quote->id}/items/{$item->id}/photos");

    $media = Media::query()->findOrFail($mediaId);
    expect($media->status)->toBe(MediaStatus::Uploaded)
        ->and($media->thumb_key)->toEndWith('_thumb.webp')
        ->and(getimagesizefromstring((string) Storage::disk('r2')->get((string) $media->thumb_key))[0])->toBe(320);

    $this->getJson("/api/quotes/{$quote->id}/photos")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mediaId)
        ->assertJsonPath('data.0.item_id', $item->id)
        ->assertJsonStructure(['data' => [['url', 'thumb_url', 'created_at']]]);
});

it('lista só fotos confirmadas', function () {
    [$quote, $item] = quoteWithItem();
    $this->postJson("/api/quotes/{$quote->id}/items/{$item->id}/photos", ['mime' => 'image/jpeg', 'size' => 1000])->assertCreated();

    $this->getJson("/api/quotes/{$quote->id}/photos")->assertJsonCount(0, 'data');
});

it('foto de item aceita só imagem até 2 MB', function (array $payload, string $error) {
    [$quote, $item] = quoteWithItem();

    $this->postJson("/api/quotes/{$quote->id}/items/{$item->id}/photos", $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'PDF' => [['mime' => 'application/pdf', 'size' => 1000], 'mime'],
    'grande demais' => [['mime' => 'image/jpeg', 'size' => 3 * 1024 * 1024], 'size'],
]);

it('foto não gera nova versão do orçamento enviado', function () {
    [$quote, $item] = quoteWithItem();
    $this->postJson("/api/quotes/{$quote->id}/send")->assertOk();

    uploadItemPhoto("/api/quotes/{$quote->id}/items/{$item->id}/photos");

    expect($quote->refresh()->version)->toBe(1)->and($quote->status->value)->toBe('sent');
});

it('orçamento decidido não recebe nem perde fotos', function () {
    [$quote, $item] = quoteWithItem();
    $mediaId = uploadItemPhoto("/api/quotes/{$quote->id}/items/{$item->id}/photos");
    Quote::query()->whereKey($quote->id)->update(['status' => 'approved']);

    $this->postJson("/api/quotes/{$quote->id}/items/{$item->id}/photos", ['mime' => 'image/jpeg', 'size' => 1000])
        ->assertUnprocessable()->assertJsonValidationErrors('quote');
    $this->deleteJson("/api/quotes/{$quote->id}/items/{$item->id}/photos/{$mediaId}")
        ->assertUnprocessable()->assertJsonValidationErrors('quote');
});

it('remove foto do item: apaga arquivo, miniatura e registro', function () {
    [$quote, $item] = quoteWithItem();
    $mediaId = uploadItemPhoto("/api/quotes/{$quote->id}/items/{$item->id}/photos");
    $media = Media::query()->findOrFail($mediaId);

    $this->deleteJson("/api/quotes/{$quote->id}/items/{$item->id}/photos/{$mediaId}")->assertNoContent();

    expect(Media::query()->find($mediaId))->toBeNull()
        ->and(Storage::disk('r2')->exists($media->r2_key))->toBeFalse()
        ->and(Storage::disk('r2')->exists((string) $media->thumb_key))->toBeFalse();
});

it('não mexe em foto pelo item errado', function () {
    $tech = actingAsRole();
    $quote = photoQuote($tech->id, 2);
    [$first, $second] = $quote->items()->orderBy('position')->get()->all();
    $mediaId = uploadItemPhoto("/api/quotes/{$quote->id}/items/{$first->id}/photos");

    $this->deleteJson("/api/quotes/{$quote->id}/items/{$second->id}/photos/{$mediaId}")->assertNotFound();
    $this->postJson("/api/quotes/{$quote->id}/items/{$second->id}/photos/{$mediaId}/confirm")->assertNotFound();
});

it('item de outro orçamento não serve na rota', function () {
    $tech = actingAsRole();
    $quote = photoQuote($tech->id);
    $other = photoQuote($tech->id);

    $this->postJson("/api/quotes/{$quote->id}/items/{$other->items()->value('id')}/photos", ['mime' => 'image/jpeg', 'size' => 1000])
        ->assertNotFound();
});

it('cliente vê as fotos no link público pela posição do item, sem ids', function () {
    $tech = actingAsRole();
    $quote = photoQuote($tech->id, 2);
    $second = $quote->items()->where('position', 2)->firstOrFail();
    uploadItemPhoto("/api/quotes/{$quote->id}/items/{$second->id}/photos");
    $token = substr((string) $this->postJson("/api/quotes/{$quote->id}/send")->json('link'), -64);
    app('auth')->forgetGuards();
    TenantContext::set(null);

    $photos = $this->getJson("/api/public/quotes/{$token}/photos")->assertOk()->json('data');

    expect($photos)->toHaveCount(1)
        ->and($photos[0])->toHaveKeys(['position', 'url', 'thumb_url'])->not->toHaveKeys(['id', 'item_id'])
        ->and($photos[0]['position'])->toBe(2);
});

it('OS mostra fotos próprias e as do item de orçamento de origem', function () {
    [$quote, $quoteItem] = quoteWithItem();
    $fromQuote = uploadItemPhoto("/api/quotes/{$quote->id}/items/{$quoteItem->id}/photos");
    $order = photoOrder($quote->created_by, quoteItemId: $quoteItem->id, quoteId: $quote->id);
    $orderItem = $order->items()->firstOrFail();

    $own = uploadItemPhoto("/api/service-orders/{$order->id}/items/{$orderItem->id}/photos");

    $this->getJson("/api/service-orders/{$order->id}/photos")
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $fromQuote)
        ->assertJsonPath('data.0.item_id', $orderItem->id)
        ->assertJsonPath('data.0.from_quote', true)
        ->assertJsonPath('data.1.id', $own)
        ->assertJsonPath('data.1.from_quote', false);

    // Foto do orçamento não é removida pela OS.
    $this->deleteJson("/api/service-orders/{$order->id}/items/{$orderItem->id}/photos/{$fromQuote}")->assertNotFound();
    $this->deleteJson("/api/service-orders/{$order->id}/items/{$orderItem->id}/photos/{$own}")->assertNoContent();
});

it('OS aceita foto até ser entregue e recusa depois de fechada ou cancelada', function (ServiceOrderStatus $status, bool $accepts) {
    $tech = actingAsRole(UserRole::Technician);
    $order = photoOrder($tech->id, $status);
    $orderId = $order->id;
    $itemId = $order->items()->value('id');

    $response = $this->postJson("/api/service-orders/{$orderId}/items/{$itemId}/photos", ['mime' => 'image/webp', 'size' => 1000]);

    $accepts ? $response->assertCreated() : $response->assertUnprocessable()->assertJsonValidationErrors('status');
})->with([
    'open' => [ServiceOrderStatus::Open, true],
    'in_progress' => [ServiceOrderStatus::InProgress, true],
    'ready' => [ServiceOrderStatus::Ready, true],
    'delivered' => [ServiceOrderStatus::Delivered, true],
    'closed' => [ServiceOrderStatus::Closed, false],
    'canceled' => [ServiceOrderStatus::Canceled, false],
]);

it('arquivo que não é imagem de verdade vira failed e não aparece', function () {
    [$quote, $item] = quoteWithItem();
    $url = "/api/quotes/{$quote->id}/items/{$item->id}/photos";
    $mediaId = $this->postJson($url, ['mime' => 'image/jpeg', 'size' => 1000])->json('data.media_id');
    Storage::disk('r2')->put(Media::query()->findOrFail($mediaId)->r2_key, '%PDF-1.4 falso');

    $this->postJson("{$url}/{$mediaId}/confirm")->assertUnprocessable();
    $this->getJson("/api/quotes/{$quote->id}/photos")->assertJsonCount(0, 'data');
});

it('limpa upload pendente há mais de 24 h e mantém os recentes e os confirmados', function () {
    [$quote, $item] = quoteWithItem();
    $url = "/api/quotes/{$quote->id}/items/{$item->id}/photos";
    $stale = $this->postJson($url, ['mime' => 'image/jpeg', 'size' => 1000])->json('data.media_id');
    $recent = $this->postJson($url, ['mime' => 'image/jpeg', 'size' => 1000])->json('data.media_id');
    $uploaded = uploadItemPhoto($url);
    Media::query()->whereKey([$stale, $uploaded])->update(['created_at' => now()->subHours(25)]);

    expect(app(PurgeStalePendingMedia::class)->handle())->toBe(1)
        ->and(Media::query()->pluck('id')->sort()->values()->all())->toBe(collect([$recent, $uploaded])->sort()->values()->all());
});
