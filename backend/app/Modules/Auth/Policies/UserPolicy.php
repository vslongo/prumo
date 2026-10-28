<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Dono/administrador gerenciam a equipe. Ninguém mexe no dono nem em si mesmo (evita se trancar fora).
 * `User` não tem global scope (ADR 0001): usuário de outra oficina vira 404, sem revelar que existe.
 */
final class UserPolicy
{
    public function manage(User $actor, User $target): Response
    {
        if ($target->tenant_id !== $actor->tenant_id) {
            return Response::denyAsNotFound();
        }

        return $actor->is_active
            && $actor->role->managesSettings()
            && $target->role !== UserRole::Owner
            && $target->id !== $actor->id
            ? Response::allow()
            : Response::deny();
    }
}
