<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\TenantContext;

it('não lista usuários de outro tenant', function () {
    actingAsRole();
    inOtherTenant(fn () => User::factory()->create(['tenant_id' => TenantContext::id(), 'name' => 'De fora']));

    $this->getJson('/api/users')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['name' => 'De fora']);
});
