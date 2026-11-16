<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Support\Totp;
use Illuminate\Support\Facades\DB;

/**
 * Segunda etapa do login: código do app (não aceita reusar o mesmo código nem um mais antigo) ou um código de
 * recuperação, que vale uma vez só.
 */
final class VerifyTwoFactorChallenge
{
    public function handle(User $user, ?string $code, ?string $recoveryCode): bool
    {
        return DB::transaction(function () use ($user, $code, $recoveryCode) {
            $user = User::query()->lockForUpdate()->find($user->id);
            if ($user === null || $user->two_factor_confirmed_at === null || $user->two_factor_secret === null) {
                return false;
            }

            if ($code !== null && $code !== '') {
                $step = Totp::matchingStep($user->two_factor_secret, $code);
                if ($step === null || $step <= (int) $user->two_factor_last_step) {
                    return false;
                }
                $user->forceFill(['two_factor_last_step' => $step])->save();

                return true;
            }

            $codes = $user->two_factor_recovery_codes ?? [];
            $index = array_search(ConfirmTwoFactor::hash((string) $recoveryCode), $codes, true);
            if ($recoveryCode === null || $index === false) {
                return false;
            }
            unset($codes[$index]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

            return true;
        });
    }
}
