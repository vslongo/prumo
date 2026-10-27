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

it('isola veículos e não aceita cliente de outro tenant', function () {
    actingAsRole();
    $other = inOtherTenant(fn () => Vehicle::factory()->create());

    $this->getJson("/api/vehicles/{$other->id}")->assertNotFound();
    $this->getJson("/api/vehicles?plate={$other->plate_normalized}")->assertNotFound();
    $this->postJson('/api/vehicles', ['customer_id' => $other->customer_id, 'plate' => 'QWE1234'])
        ->assertUnprocessable()->assertJsonValidationErrors('customer_id');
});
