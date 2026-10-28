<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Tenancy\TenantContext;

it('não lista usuários de outro tenant', function () {
    actingAsRole();
    inOtherTenant(fn () => User::factory()->create(['tenant_id' => TenantContext::id(), 'name' => 'De fora']));

    $this->getJson('/api/users')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissing(['name' => 'De fora']);
});

it('convites de outra oficina não aparecem no tenant atual', function () {
    actingAsRole();
    inOtherTenant(fn () => UserInvitation::create([
        'name' => 'De fora', 'role' => 'technician', 'token_hash' => str_repeat('b', 64),
        'expires_at' => now()->addDay(), 'invited_by_id' => User::factory()->create(['tenant_id' => TenantContext::id()])->id,
    ]));

    expect(UserInvitation::count())->toBe(0);
});
