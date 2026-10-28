<?php

declare(strict_types=1);

namespace App\Modules\Auth\Policies;

use App\Modules\Auth\Models\User;

/** Só dono/administrador convidam funcionários. */
final class UserInvitationPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active && $user->role->managesSettings();
    }
}
