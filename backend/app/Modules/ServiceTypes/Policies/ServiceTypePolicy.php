<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\ServiceTypes\Models\ServiceType;

/** Todos leem; só owner/admin configuram. Tipos padrão do sistema são somente leitura. */
final class ServiceTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role->managesSettings();
    }

    public function update(User $user, ServiceType $serviceType): bool
    {
        return $this->create($user) && $serviceType->tenant_id === $user->tenant_id;
    }
}
