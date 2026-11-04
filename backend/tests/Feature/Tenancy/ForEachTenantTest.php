<?php

declare(strict_types=1);

use App\Modules\Tenancy\Actions\ForEachTenant;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;

it('roda o callback no contexto de cada tenant e restaura o contexto no fim', function () {
    $tenants = Tenant::factory()->count(3)->create()->pluck('id')->sort()->values()->all();
    TenantContext::set(null);
    $seen = [];

    app(ForEachTenant::class)->handle(function () use (&$seen) {
        $seen[] = TenantContext::id();
    });

    expect($seen)->toBe($tenants)->and(TenantContext::id())->toBeNull();
});
