<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use Illuminate\Validation\ValidationException;

final class ChangeUserRole
{
    public function handle(User $user, UserRole $role): User
    {
        if ($role === UserRole::Owner) {
            throw ValidationException::withMessages(['role' => 'A oficina tem um só dono.']);
        }

        $user->role = $role;
        $user->save();

        return $user;
    }
}
