<?php

declare(strict_types=1);

use App\Modules\Vehicles\Models\Vehicle;

it('não lê veículos de outro tenant nem por id nem por placa', function () {
    actingAsRole();
    $other = inOtherTenant(fn () => Vehicle::factory()->create());

    $this->getJson("/api/vehicles/{$other->id}")->assertNotFound();
    $this->getJson("/api/vehicles?plate={$other->plate_normalized}")->assertNotFound();
});

it('não cadastra veículo para cliente de outro tenant', function () {
    actingAsRole();
    $other = inOtherTenant(fn () => Vehicle::factory()->create());

    $this->postJson('/api/vehicles', ['customer_id' => $other->customer_id, 'plate' => 'QWE1234'])
        ->assertUnprocessable()->assertJsonValidationErrors('customer_id');
});

it('lista veículos do cliente sem vazar de outro tenant', function () {
    actingAsRole();
    $vehicle = makeVehicle();
    $foreign = inOtherTenant(fn () => makeVehicle());

    $this->getJson("/api/customers/{$vehicle->customer_id}/vehicles")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/customers/{$foreign->customer_id}/vehicles")->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/customers/nao-e-uuid/vehicles')->assertNotFound();
});
