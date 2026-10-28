<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Tenancy\TenantContext;

/** Orçamento com um item em outro tenant. */
function foreignQuote(): Quote
{
    return inOtherTenant(function () {
        $owner = User::factory()->create(['tenant_id' => TenantContext::id()]);
        $vehicle = makeVehicle();
        $quote = Quote::create(['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'created_by' => $owner->id]);
        $quote->items()->create([
            'position' => 1, 'kind' => 'product', 'description' => 'Pastilha', 'technician_id' => $owner->id,
            'quantity' => '1', 'unit_price_cents' => 15000, 'total_cents' => 15000, 'fields' => [], 'fields_schema_snapshot' => [],
        ]);

        return $quote;
    });
}

it('não lê nem altera orçamento de outro tenant', function () {
    $tech = actingAsRole();
    $foreign = foreignQuote();
    $foreignItem = TenantContext::run($foreign->tenant_id, fn () => $foreign->items()->firstOrFail());

    $this->getJson("/api/quotes/{$foreign->id}")->assertNotFound();
    $this->getJson('/api/quotes')->assertJsonCount(0, 'data');
    $this->postJson("/api/quotes/{$foreign->id}/send")->assertNotFound();
    $this->postJson("/api/quotes/{$foreign->id}/items", productItem($tech))->assertNotFound();
    $this->deleteJson("/api/quotes/{$foreign->id}/items/{$foreignItem->id}")->assertNotFound();

    expect(TenantContext::run($foreign->tenant_id, fn () => QuoteItem::query()->whereKey($foreignItem->id)->exists()))->toBeTrue();
});

it('não remove item de outro tenant pelo próprio orçamento', function () {
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    $mine = Quote::create(['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'created_by' => $tech->id]);
    $foreign = foreignQuote();
    $foreignItem = TenantContext::run($foreign->tenant_id, fn () => $foreign->items()->firstOrFail());

    $this->deleteJson("/api/quotes/{$mine->id}/items/{$foreignItem->id}")->assertNotFound();
});

it('não cria orçamento para cliente/veículo de outro tenant', function () {
    actingAsRole();
    $foreign = inOtherTenant(fn () => makeVehicle());

    $this->postJson('/api/quotes', ['customer_id' => $foreign->customer_id, 'vehicle_id' => $foreign->id])
        ->assertUnprocessable()->assertJsonValidationErrors(['customer_id', 'vehicle_id']);
});
