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
