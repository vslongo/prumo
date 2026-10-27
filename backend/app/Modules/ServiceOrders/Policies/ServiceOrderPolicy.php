<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\ServiceOrders\Models\ServiceOrder;

/** Todos os papéis abrem e executam OS (técnico marca itens feitos). */
final class ServiceOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, ServiceOrder $order): bool
    {
        return $user->is_active && $order->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, ServiceOrder $order): bool
    {
        return $this->view($user, $order);
    }
}
