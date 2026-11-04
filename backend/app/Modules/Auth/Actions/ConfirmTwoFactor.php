<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\Totp;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Passo 2: o primeiro código do app prova que o segredo foi lido; a partir daqui o login pede o código.
 * Devolve os códigos de recuperação em texto uma única vez; no banco ficam só os hashes.
 */
final class ConfirmTwoFactor
{
    public const RECOVERY_CODES = 8;

    /** @return list<string> */
    public function handle(User $user, string $code): array
    {
        if ($user->two_factor_confirmed_at !== null || $user->two_factor_secret === null) {
            throw ValidationException::withMessages(['code' => 'Comece a ativação de novo.']);
        }

        $step = Totp::matchingStep($user->two_factor_secret, $code);
        if ($step === null) {
            throw ValidationException::withMessages(['code' => 'Código inválido. Confira a hora do celular e tente o código atual.']);
        }

        $codes = array_map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)), range(1, self::RECOVERY_CODES));
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step' => $step,
            'two_factor_recovery_codes' => array_map(self::hash(...), $codes),
        ])->save();

        return $codes;
    }

    public static function hash(string $code): string
    {
        return hash('sha256', Str::lower(trim($code)));
    }
}
