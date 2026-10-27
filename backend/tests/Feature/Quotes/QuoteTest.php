<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Event;

function createQuote(User $tech, array $items = []): Quote
{
    $vehicle = makeVehicle();
    $id = test()->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'mileage' => 50000,
        'items' => $items,
    ])->assertCreated()->json('data.id');

    return Quote::findOrFail($id);
}

it('técnico cria orçamento com itens, campos específicos e total em centavos', function () {
    $tech = actingAsRole(UserRole::Technician);
    $type = oilChangeType();
    $vehicle = makeVehicle();

    $this->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [oilItem($tech, $type), productItem($tech)],
    ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.version', 1)
        ->assertJsonPath('data.total_cents', 8000 + 17996)
        ->assertJsonPath('data.items.0.technician_id', $tech->id)
        ->assertJsonPath('data.items.0.fields.viscosity', '5W30')
        ->assertJsonPath('data.items.0.fields_schema_snapshot.1.key', 'viscosity');
});

it('valida fields contra o schema do tipo de serviço', function (array $fields, string $errorKey) {
    $tech = actingAsRole();
    $type = oilChangeType();
    $vehicle = makeVehicle();

    $this->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [oilItem($tech, $type, ['fields' => $fields])],
    ])->assertUnprocessable()->assertJsonValidationErrors($errorKey);
})->with([
    'obrigatório faltando' => [['viscosity' => '5W30', 'liters' => 4], 'items.0.fields.oil_brand'],
    'tipo errado' => [['oil_brand' => 'Mobil', 'viscosity' => '5W30', 'liters' => 'quatro'], 'items.0.fields.liters'],
    'opção inválida' => [['oil_brand' => 'Mobil', 'viscosity' => '0W99', 'liters' => 4], 'items.0.fields.viscosity'],
    'campo desconhecido' => [['oil_brand' => 'Mobil', 'viscosity' => '5W30', 'liters' => 4, 'cor' => 'azul'], 'items.0.fields.cor'],
]);

it('exige técnico da própria oficina e veículo do cliente', function () {
    actingAsRole();
    $vehicle = makeVehicle();
    $otherVehicle = makeVehicle();
    $outsider = inOtherTenant(fn () => User::factory()->create());

    $this->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [productItem($outsider)],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.0.technician_id');

    $this->postJson('/api/quotes', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $otherVehicle->id])
        ->assertUnprocessable()->assertJsonValidationErrors('vehicle_id');
});

it('envia orçamento: gera link público, fallback wa.me e evento', function () {
    Event::fake([QuoteSent::class]);
    $tech = actingAsRole();
    $quote = createQuote($tech, [productItem($tech)]);

    $response = $this->postJson("/api/quotes/{$quote->id}/send")->assertOk()->assertJsonPath('data.status', 'sent');

    expect($response->json('link'))->toMatch('#/o/[0-9a-f]{64}$#')
        ->and($response->json('whatsapp_url'))->toStartWith('https://wa.me/55')
        ->and($quote->refresh()->token_hash)->toBe(hash('sha256', substr($response->json('link'), -64)))
        ->and($quote->token_expires_at->isSameDay(now()->addDays(7)))->toBeTrue();
    Event::assertDispatched(QuoteSent::class);
});

it('não envia orçamento sem itens', function () {
    $tech = actingAsRole();
    $quote = createQuote($tech);

    $this->postJson("/api/quotes/{$quote->id}/send")->assertUnprocessable();
});

it('editar orçamento enviado cria nova versão e invalida o link anterior', function () {
    $tech = actingAsRole();
    $quote = createQuote($tech, [productItem($tech)]);
    $link = $this->postJson("/api/quotes/{$quote->id}/send")->json('link');

    $this->postJson("/api/quotes/{$quote->id}/items", productItem($tech, ['description' => 'Filtro']))
        ->assertOk()
        ->assertJsonPath('data.version', 2)
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonCount(2, 'data.items');

    $this->getJson('/api/public/quotes/'.substr($link, -64))->assertNotFound();
});

it('remove item e recalcula total', function () {
    $tech = actingAsRole();
    $quote = createQuote($tech, [productItem($tech), productItem($tech, ['unit_price_cents' => 1000, 'quantity' => '2'])]);
    $first = $quote->items()->first();

    $this->deleteJson("/api/quotes/{$quote->id}/items/{$first->id}")
        ->assertOk()
        ->assertJsonPath('data.total_cents', 2000);
});

it('orçamento aprovado não pode ser editado', function () {
    $tech = actingAsRole();
    $quote = createQuote($tech, [productItem($tech)]);
    $quote->update(['status' => 'approved']);

    $this->postJson("/api/quotes/{$quote->id}/items", productItem($tech))->assertUnprocessable();
});

it('isola orçamentos entre tenants', function () {
    $tech = actingAsRole();
    $foreign = inOtherTenant(function () {
        $owner = User::factory()->create(['tenant_id' => TenantContext::id()]);
        $vehicle = makeVehicle();

        return Quote::create(['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'created_by' => $owner->id]);
    });

    $this->getJson("/api/quotes/{$foreign->id}")->assertNotFound();
    $this->postJson("/api/quotes/{$foreign->id}/send")->assertNotFound();
    $this->postJson("/api/quotes/{$foreign->id}/items", productItem($tech))->assertNotFound();
    $this->getJson('/api/quotes')->assertJsonCount(0, 'data');
});
