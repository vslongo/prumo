<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;

/** Volta o acesso de quem foi desativado (ex.: desativou por engano). Entra com a mesma senha. */
final class ReactivateUser
{
    public function handle(User $user): User
    {
        $user->is_active = true;
        $user->save();

        return $user;
    }
}
