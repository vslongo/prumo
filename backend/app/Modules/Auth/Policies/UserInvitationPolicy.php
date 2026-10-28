<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;

/** Só dono/administrador convidam, veem e cancelam convites. O global scope já limita à oficina. */
final class UserInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->create($user);
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role->managesSettings();
    }

    public function delete(User $user, UserInvitation $invitation): bool
    {
        return $this->create($user) && $invitation->tenant_id === $user->tenant_id;
    }
}
