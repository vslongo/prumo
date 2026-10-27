<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;

/** Usuários ativos da oficina atual. `User` não tem global scope (ADR 0001): o filtro de tenant é explícito aqui. */
final class ListTenantUsers
{
    /** @return Collection<int, User> */
    public function handle(): Collection
    {
        return User::query()
            ->where('tenant_id', TenantContext::idOrFail())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
