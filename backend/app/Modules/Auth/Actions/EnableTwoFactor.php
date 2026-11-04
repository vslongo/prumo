<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\Totp;
use Illuminate\Validation\ValidationException;

/** Passo 1: gera um segredo novo (ainda não vale no login) para o usuário ler no app autenticador. Pedir de novo troca o segredo. */
final class EnableTwoFactor
{
    /** @return array{secret: string, otpauth_url: string} */
    public function handle(User $user): array
    {
        if ($user->two_factor_confirmed_at !== null) {
            throw ValidationException::withMessages(['two_factor' => 'A verificação em duas etapas já está ativa.']);
        }

        $secret = Totp::generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_recovery_codes' => null, 'two_factor_last_step' => null])->save();

        return ['secret' => $secret, 'otpauth_url' => Totp::otpauthUrl($secret, $user->email, 'Prumo')];
    }
}
