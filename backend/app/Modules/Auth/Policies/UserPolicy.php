<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Dono/administrador gerenciam a equipe; admins só o dono gerencia. Ninguém mexe no dono nem em si mesmo.
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
            && $this->assignRole($actor, $target->role)
            ? Response::allow()
            : Response::deny();
    }

    /** Dar um papel (troca ou convite): só o dono cria admin. */
    public function assignRole(User $actor, UserRole $role): bool
    {
        return $role !== UserRole::Admin || $actor->role === UserRole::Owner;
    }
}
