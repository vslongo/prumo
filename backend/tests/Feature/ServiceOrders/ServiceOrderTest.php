<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Quotes\Data\ApprovedQuoteData;
use App\Modules\Quotes\Data\ApprovedQuoteItemData;
use App\Modules\Quotes\Models\Quote;
use App\Modules\ServiceOrders\Actions\ConvertApprovedQuoteToServiceOrder;
use App\Modules\ServiceOrders\Actions\OpenServiceOrder;
use App\Modules\ServiceOrders\Data\OpenServiceOrderData;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\TenantContext;

function openOrder(User $tech, array $items = []): string
{
    $vehicle = makeVehicle();

    return test()->postJson('/api/service-orders', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'mileage_in' => 10000,
        'items' => $items,
    ])->assertCreated()->json('data.id');
}

function setStatus(string $orderId, ServiceOrderStatus $status): void
{
    ServiceOrder::query()->whereKey($orderId)->update(['status' => $status->value]);
}

it('abre OS direta, sem orçamento, com itens e técnico', function () {
    $tech = actingAsRole(UserRole::Technician);
    $type = oilChangeType();
    $vehicle = makeVehicle();

    $this->postJson('/api/service-orders', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [oilItem($tech, $type), productItem($tech)],
    ])
        ->assertCreated()
        ->assertJsonPath('data.origin', 'direct')
        ->assertJsonPath('data.status', 'open')
        ->assertJsonPath('data.quote_id', null)
        ->assertJsonPath('data.number', 1)
        ->assertJsonPath('data.opened_by', $tech->id)
        ->assertJsonPath('data.total_cents', 25996)
        ->assertJsonPath('data.items.1.technician_id', $tech->id);
});

it('numera OS em sequência por tenant', function () {
    $tech = actingAsRole();
    openOrder($tech);
    $second = openOrder($tech);

    $otherNumber = inOtherTenant(function () {
        $vehicle = makeVehicle();
        $user = User::factory()->create(['tenant_id' => TenantContext::id()]);

        return app(OpenServiceOrder::class)->handle(
            new OpenServiceOrderData($vehicle->customer_id, $vehicle->id, $user->id)
        )->number;
    });

    expect(ServiceOrder::find($second)->number)->toBe(2)->and($otherNumber)->toBe(1);
});

it('valida item da OS (técnico obrigatório e fields)', function () {
    $tech = actingAsRole();
    $type = oilChangeType();
    $vehicle = makeVehicle();

    $this->postJson('/api/service-orders', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [
            productItem($tech, ['technician_id' => null]),
            oilItem($tech, $type, ['fields' => ['oil_brand' => 'Mobil']]),
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.0.technician_id');

    $this->postJson('/api/service-orders', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [oilItem($tech, $type, ['fields' => ['oil_brand' => 'Mobil']])],
    ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.fields.viscosity', 'items.0.fields.liters']);
});

it('conversão de orçamento aprovado é idempotente', function () {
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    $quote = Quote::create(['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'created_by' => $tech->id]);
    $item = $quote->items()->create([
        'position' => 1, 'kind' => 'product', 'description' => 'Pastilha', 'technician_id' => $tech->id,
        'quantity' => '1', 'unit_price_cents' => 15000, 'total_cents' => 15000, 'fields' => [], 'fields_schema_snapshot' => [],
    ]);
    $data = new ApprovedQuoteData(TenantContext::idOrFail(), $quote->id, $vehicle->customer_id, $vehicle->id, $tech->id, null, null, [
        new ApprovedQuoteItemData($item->id, 'product', 'Pastilha', null, $tech->id, '1.000', 15000, 15000, [], []),
    ]);

    $convert = app(ConvertApprovedQuoteToServiceOrder::class);
    $first = $convert->handle($data);
    $second = $convert->handle($data);

    expect($second->id)->toBe($first->id)
        ->and(ServiceOrder::where('quote_id', $quote->id)->count())->toBe(1)
        ->and($first->items()->first()->quote_item_id)->toBe($item->id);
});

it('segue a máquina de estados: transições válidas', function (ServiceOrderStatus $from, ServiceOrderStatus $to) {
    $tech = actingAsRole();
    $id = openOrder($tech);
    setStatus($id, $from);

    $this->postJson("/api/service-orders/{$id}/transition", ['status' => $to->value])
        ->assertOk()->assertJsonPath('data.status', $to->value);
})->with([
    [ServiceOrderStatus::Open, ServiceOrderStatus::InProgress],
    [ServiceOrderStatus::InProgress, ServiceOrderStatus::Ready],
    [ServiceOrderStatus::Ready, ServiceOrderStatus::Delivered],
    [ServiceOrderStatus::Open, ServiceOrderStatus::Canceled],
    [ServiceOrderStatus::InProgress, ServiceOrderStatus::Canceled],
    [ServiceOrderStatus::Ready, ServiceOrderStatus::Canceled],
]);

it('recusa transições inválidas', function (ServiceOrderStatus $from, ServiceOrderStatus $to) {
    $tech = actingAsRole();
    $id = openOrder($tech);
    setStatus($id, $from);

    $this->postJson("/api/service-orders/{$id}/transition", ['status' => $to->value])->assertUnprocessable();
})->with([
    [ServiceOrderStatus::Open, ServiceOrderStatus::Ready],
    [ServiceOrderStatus::Open, ServiceOrderStatus::Delivered],
    [ServiceOrderStatus::InProgress, ServiceOrderStatus::Open],
    [ServiceOrderStatus::Delivered, ServiceOrderStatus::Canceled],
    [ServiceOrderStatus::Delivered, ServiceOrderStatus::Closed], // fechar só pelo fechamento
    [ServiceOrderStatus::Closed, ServiceOrderStatus::Open],
    [ServiceOrderStatus::Canceled, ServiceOrderStatus::Open],
]);

it('técnico marca item como feito; OS pronta não aceita mudanças de item', function () {
    $tech = actingAsRole(UserRole::Technician);
    $id = openOrder($tech, [productItem($tech)]);
    $item = ServiceOrder::find($id)->items()->first();

    $this->postJson("/api/service-orders/{$id}/items/{$item->id}/done")
        ->assertOk()->assertJsonPath('data.items.0.done_by', $tech->id);

    setStatus($id, ServiceOrderStatus::Ready);
    $this->postJson("/api/service-orders/{$id}/items", productItem($tech))->assertUnprocessable();
});

it('fecha OS entregue com itens feitos e km de saída', function () {
    $tech = actingAsRole();
    $id = openOrder($tech, [productItem($tech)]);
    $item = ServiceOrder::find($id)->items()->first();
    setStatus($id, ServiceOrderStatus::Delivered);

    $this->postJson("/api/service-orders/{$id}/close", ['mileage_out' => 10050])
        ->assertUnprocessable()->assertJsonValidationErrors('items'); // item não feito

    $item->update(['done_at' => now(), 'done_by' => $tech->id]);

    $this->postJson("/api/service-orders/{$id}/close", ['mileage_out' => 9000])
        ->assertUnprocessable()->assertJsonValidationErrors('mileage_out');
    $this->postJson("/api/service-orders/{$id}/close", ['mileage_out' => 10050])
        ->assertOk()->assertJsonPath('data.status', 'closed')->assertJsonPath('data.mileage_out', 10050);
});

it('não fecha OS que não foi entregue nem OS sem itens', function () {
    $tech = actingAsRole();
    $withItem = openOrder($tech, [productItem($tech)]);
    $empty = openOrder($tech);
    setStatus($empty, ServiceOrderStatus::Delivered);

    $this->postJson("/api/service-orders/{$withItem}/close", ['mileage_out' => 10050])->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->postJson("/api/service-orders/{$empty}/close", ['mileage_out' => 10050])->assertUnprocessable()->assertJsonValidationErrors('items');
});

it('lista histórico do veículo', function () {
    $tech = actingAsRole();
    $id = openOrder($tech);
    $vehicleId = ServiceOrder::find($id)->vehicle_id;
    openOrder($tech);

    $this->getJson("/api/service-orders?vehicle_id={$vehicleId}")->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $id);
});
