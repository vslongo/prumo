<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Actions;

use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Closure;

/** Para jobs agendados: roda o callback no contexto de cada tenant, sem tirar o global scope. */
final class ForEachTenant
{
    /** @param Closure(): mixed $callback */
    public function handle(Closure $callback): void
    {
        Tenant::query()->select('id')->orderBy('id')->each(
            fn (Tenant $tenant) => TenantContext::run($tenant->id, $callback)
        );
    }
}
