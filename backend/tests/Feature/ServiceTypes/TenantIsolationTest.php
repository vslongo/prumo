<?php

declare(strict_types=1);

use App\Modules\ServiceTypes\Models\ServiceType;
use App\Modules\Tenancy\TenantContext;

it('não lê nem edita tipo de serviço de outro tenant', function () {
    actingAsRole();
    $other = inOtherTenant(fn () => ServiceType::factory()->create(['name' => 'Da outra']));

    $this->getJson('/api/service-types')->assertJsonMissing(['name' => 'Da outra']);
    $this->putJson("/api/service-types/{$other->id}", [
        'name' => 'Sequestrado',
        'default_price_cents' => 0,
        'fields_schema' => [],
    ])->assertNotFound();

    expect(TenantContext::run($other->tenant_id, fn () => ServiceType::findOrFail($other->id)->name))->toBe('Da outra');
});

it('não usa tipo de serviço de outro tenant em item de orçamento', function () {
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    $foreignType = inOtherTenant(fn () => oilChangeType());

    $this->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id,
        'vehicle_id' => $vehicle->id,
        'items' => [oilItem($tech, $foreignType)],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.0.service_type_id');
});
