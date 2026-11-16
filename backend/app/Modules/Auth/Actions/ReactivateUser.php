<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Billing\Actions\EnsureSeatAvailable;

/** Volta o acesso de quem foi desativado (ex.: desativou por engano). Entra com a mesma senha. Respeita o limite do plano. */
final class ReactivateUser
{
    public function __construct(private readonly EnsureSeatAvailable $ensureSeat) {}

    public function handle(User $user): User
    {
        if (! $user->is_active) {
            $this->ensureSeat->handle(User::query()->where('tenant_id', $user->tenant_id)->where('is_active', true)->count());
        }

        $user->is_active = true;
        $user->save();

        return $user;
    }
}
