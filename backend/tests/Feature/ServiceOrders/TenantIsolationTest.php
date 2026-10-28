<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\ServiceOrders\Actions\OpenServiceOrder;
use App\Modules\ServiceOrders\Data\OpenServiceOrderData;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use App\Modules\Tenancy\TenantContext;

/** OS com um item em outro tenant; devolve [OS, item]. */
function foreignOrder(): array
{
    return inOtherTenant(function () {
        $vehicle = makeVehicle();
        $user = User::factory()->create(['tenant_id' => TenantContext::id()]);
        $order = app(OpenServiceOrder::class)->handle(new OpenServiceOrderData($vehicle->customer_id, $vehicle->id, $user->id));
        $item = $order->items()->create([
            'position' => 1, 'kind' => 'product', 'description' => 'Pastilha', 'technician_id' => $user->id,
            'quantity' => '1', 'unit_price_cents' => 15000, 'total_cents' => 15000, 'fields' => [], 'fields_schema_snapshot' => [],
        ]);

        return [$order, $item];
    });
}

it('não lê nem altera OS de outro tenant', function () {
    $tech = actingAsRole();
    [$foreign, $item] = foreignOrder();

    $this->getJson("/api/service-orders/{$foreign->id}")->assertNotFound();
    $this->getJson('/api/service-orders')->assertJsonCount(0, 'data');
    $this->getJson("/api/service-orders?vehicle_id={$foreign->vehicle_id}")->assertJsonCount(0, 'data');
    $this->postJson("/api/service-orders/{$foreign->id}/transition", ['status' => 'in_progress'])->assertNotFound();
    $this->postJson("/api/service-orders/{$foreign->id}/items", productItem($tech))->assertNotFound();
    $this->postJson("/api/service-orders/{$foreign->id}/items/{$item->id}/done")->assertNotFound();
    $this->postJson("/api/service-orders/{$foreign->id}/close", ['mileage_out' => 99999])->assertNotFound();

    [$status, $doneAt] = TenantContext::run($foreign->tenant_id, fn () => [
        ServiceOrder::findOrFail($foreign->id)->status->value,
        ServiceOrderItem::findOrFail($item->id)->done_at,
    ]);
    expect($status)->toBe('open')->and($doneAt)->toBeNull();
});

it('não marca como feito item de outro tenant pela própria OS', function () {
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    $mine = app(OpenServiceOrder::class)->handle(new OpenServiceOrderData($vehicle->customer_id, $vehicle->id, $tech->id));
    [, $item] = foreignOrder();

    $this->postJson("/api/service-orders/{$mine->id}/items/{$item->id}/done")->assertNotFound();
});

it('não abre OS para veículo nem com técnico de outro tenant', function () {
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    $foreignVehicle = inOtherTenant(fn () => makeVehicle());
    $outsider = inOtherTenant(fn () => User::factory()->create(['tenant_id' => TenantContext::id()]));

    $this->postJson('/api/service-orders', ['customer_id' => $foreignVehicle->customer_id, 'vehicle_id' => $foreignVehicle->id])
        ->assertUnprocessable()->assertJsonValidationErrors(['customer_id', 'vehicle_id']);
    $this->postJson('/api/service-orders', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [productItem($outsider)],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.0.technician_id');
});
