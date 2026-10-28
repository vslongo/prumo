<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\TenantContext;

it('lista os usuários da própria oficina, desativados com is_active false', function () {
    $me = actingAsRole();
    $colleague = User::factory()->create(['tenant_id' => $me->tenant_id, 'name' => 'Colega']);
    $gone = User::factory()->create(['tenant_id' => $me->tenant_id, 'is_active' => false]);
    inOtherTenant(fn () => User::factory()->create(['tenant_id' => TenantContext::id(), 'name' => 'De fora']));

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.2.id', $gone->id)
        ->assertJsonPath('data.2.is_active', false)
        ->assertJsonFragment(['id' => $colleague->id])
        ->assertJsonMissing(['name' => 'De fora']);
});
