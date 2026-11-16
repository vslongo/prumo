<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Troca a senha com o token do e-mail (uso único, 60 min). Derruba as sessões e os tokens abertos.
 * Não abre sessão: a pessoa entra pelo login normal, então o 2FA continua valendo.
 */
final class ResetPassword
{
    public function handle(string $email, string $token, string $password): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $password, 'is_active' => true],
            function (User $user, string $password): void {
                DB::transaction(function () use ($user, $password) {
                    $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                    // ponytail: sessões no banco (SESSION_DRIVER=database), igual ao DeactivateUser.
                    DB::table('sessions')->where('user_id', $user->id)->delete();
                    $user->tokens()->delete();
                });
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Mesma mensagem para e-mail errado, token errado e token vencido.
            throw ValidationException::withMessages(['token' => 'Este link não vale mais ou o e-mail não confere. Peça um link novo.']);
        }
    }
}
