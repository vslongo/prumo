<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use Illuminate\Support\Carbon;

/**
 * 2FA é obrigatório só para o dono, depois de GRACE_DAYS de conta (o teste grátis começa sem atrito). Os outros papéis ativam se quiserem.
 * `requiredFrom` = desde quando é obrigatório (null se não precisa ou já ativou); `blocked` = já passou do prazo sem ativar.
 */
final class ResolveTwoFactorRequirement
{
    public const GRACE_DAYS = 7;

    /** @return array{enabled: bool, required_from: Carbon|null, blocked: bool} */
    public function handle(User $user): array
    {
        $enabled = $user->two_factor_confirmed_at !== null;
        $requiredFrom = ! $enabled && $user->role === UserRole::Owner
            ? ($user->created_at ?? now())->copy()->addDays(self::GRACE_DAYS)
            : null;

        return ['enabled' => $enabled, 'required_from' => $requiredFrom, 'blocked' => $requiredFrom?->isPast() ?? false];
    }
}
