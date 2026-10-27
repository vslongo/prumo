<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\TenantContext;

it('lista só usuários ativos da própria oficina (para escolher técnico)', function () {
    $me = actingAsRole();
    $colleague = User::factory()->create(['tenant_id' => $me->tenant_id, 'name' => 'Colega']);
    User::factory()->create(['tenant_id' => $me->tenant_id, 'is_active' => false]);
    inOtherTenant(fn () => User::factory()->create(['tenant_id' => TenantContext::id(), 'name' => 'De fora']));

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonFragment(['id' => $colleague->id])
        ->assertJsonMissing(['name' => 'De fora']);
});

it('lista veículos do cliente sem vazar de outro tenant', function () {
    actingAsRole();
    $vehicle = makeVehicle();
    $foreign = inOtherTenant(fn () => makeVehicle());

    $this->getJson("/api/customers/{$vehicle->customer_id}/vehicles")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/customers/{$foreign->customer_id}/vehicles")->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/customers/nao-e-uuid/vehicles')->assertNotFound();
});
