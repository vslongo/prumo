<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Desliga o 2FA (celular perdido, troca de app), confirmando a senha. Para o dono passado o prazo,
 * o app volta a exigir a ativação na mesma hora (ResolveTwoFactorRequirement).
 */
final class DisableTwoFactor
{
    public function handle(User $user, string $password): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'Senha incorreta.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();
    }
}
