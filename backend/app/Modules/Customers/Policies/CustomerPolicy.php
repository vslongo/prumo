<?php

declare(strict_types=1);

namespace App\Modules\Customers\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Customers\Models\Customer;

/** Todos os papéis da oficina atendem clientes; exportar e anonimizar (LGPD) só owner/admin. O global scope já isola o tenant. */
final class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->is_active && $customer->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }

    public function export(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer) && $user->role->managesSettings();
    }

    public function anonymize(User $user, Customer $customer): bool
    {
        return $this->export($user, $customer);
    }
}
