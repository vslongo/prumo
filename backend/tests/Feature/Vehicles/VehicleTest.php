<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;
use App\Modules\Vehicles\Models\Vehicle;

it('cadastra veículo com placa normalizada e acha pela placa', function () {
    actingAsRole();
    $customer = Customer::factory()->create();

    $this->postJson('/api/vehicles', ['customer_id' => $customer->id, 'plate' => 'bra-2e19', 'brand' => 'VW', 'model' => 'Gol'])
        ->assertCreated()
        ->assertJsonPath('data.plate', 'BRA2E19');

    $this->getJson('/api/vehicles?plate=BRA 2E19')->assertOk()->assertJsonPath('data.model', 'Gol');
});

it('recusa placa repetida no mesmo tenant e placa inválida', function () {
    actingAsRole();
    $vehicle = makeVehicle();

    $this->postJson('/api/vehicles', ['customer_id' => $vehicle->customer_id, 'plate' => $vehicle->plate_normalized])
        ->assertUnprocessable()->assertJsonValidationErrors('plate');
    $this->postJson('/api/vehicles', ['customer_id' => $vehicle->customer_id, 'plate' => 'XX123'])
        ->assertUnprocessable()->assertJsonValidationErrors('plate');
});

it('permite a mesma placa em outro tenant', function () {
    actingAsRole();
    $other = inOtherTenant(fn () => Vehicle::factory()->create(['plate_normalized' => 'ABC1234']));
    $customer = Customer::factory()->create();

    $this->postJson('/api/vehicles', ['customer_id' => $customer->id, 'plate' => 'ABC1234'])->assertCreated();
    expect($other->plate_normalized)->toBe('ABC1234');
});

it('salva e retorna a quilometragem no cadastro e nas consultas', function (?int $mileage) {
    actingAsRole();
    $customer = Customer::factory()->create();

    $response = $this->postJson('/api/vehicles', [
        'customer_id' => $customer->id,
        'plate' => 'ABC1234',
        'mileage' => $mileage,
    ])->assertCreated()->assertJsonPath('data.mileage', $mileage);

    $id = $response->json('data.id');
    $this->assertDatabaseHas('vehicles', ['id' => $id, 'mileage' => $mileage]);
    $this->getJson("/api/vehicles/{$id}")->assertOk()->assertJsonPath('data.mileage', $mileage);
    $this->getJson("/api/customers/{$customer->id}/vehicles")
        ->assertOk()->assertJsonPath('data.0.mileage', $mileage);
})->with([null, 0, 125000]);

it('recusa quilometragem inválida', function (mixed $mileage) {
    actingAsRole();
    $customer = Customer::factory()->create();

    $this->postJson('/api/vehicles', [
        'customer_id' => $customer->id,
        'plate' => 'ABC1234',
        'mileage' => $mileage,
    ])->assertUnprocessable()->assertJsonValidationErrors('mileage');
})->with([-1, 1.5, 'abc', 2147483648]);

it('edita o veículo mantendo cliente e chassi e normalizando a placa', function () {
    actingAsRole();
    $vehicle = makeVehicle();
    $this->patchJson("/api/vehicles/{$vehicle->id}", [
        'plate' => 'bra-2e19', 'brand' => 'VW', 'model' => null, 'year' => null, 'mileage' => 50000,
    ])->assertOk()->assertJsonPath('data.plate', 'BRA2E19')
        ->assertJsonPath('data.mileage', 50000)->assertJsonPath('data.model', null)
        ->assertJsonPath('data.customer_id', $vehicle->customer_id)->assertJsonPath('data.vin', $vehicle->vin);
    $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'mileage' => 50000, 'plate_normalized' => 'BRA2E19']);
});

it('aceita a própria placa mas recusa placa de outro veículo e KM inválido', function () {
    actingAsRole();
    $vehicle = makeVehicle();
    $other = makeVehicle();
    $url = "/api/vehicles/{$vehicle->id}";
    $this->patchJson($url, ['plate' => $vehicle->plate_normalized, 'mileage' => 0])->assertOk();
    $this->patchJson($url, ['plate' => $other->plate_normalized])->assertUnprocessable()->assertJsonValidationErrors('plate');
    $this->patchJson($url, ['plate' => $vehicle->plate_normalized, 'mileage' => -1])->assertUnprocessable()->assertJsonValidationErrors('mileage');
});

it('não edita veículos de outro tenant', function () {
    actingAsRole();
    $vehicle = inOtherTenant(fn () => Vehicle::factory()->create());
    $this->patchJson("/api/vehicles/{$vehicle->id}", ['plate' => 'ABC1234', 'mileage' => 100])->assertNotFound();
});
