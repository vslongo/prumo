<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Collection;

/**
 * Usuários da oficina atual, inclusive desativados (o nome continua aparecendo nas OS antigas).
 * Quem escolhe técnico filtra por `is_active`. `User` não tem global scope (ADR 0001): o filtro de tenant é explícito aqui.
 */
final class ListTenantUsers
{
    /** @return Collection<int, User> */
    public function handle(): Collection
    {
        return User::query()
            ->where('tenant_id', TenantContext::idOrFail())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }
}
