<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use Illuminate\Support\Facades\Password;

/**
 * Pede o link de senha nova. Não devolve nada de propósito: quem chama nunca fica sabendo se o e-mail
 * tem cadastro. Usuário desativado não recebe link. O broker do Laravel limita a 1 pedido por minuto por e-mail.
 */
final class RequestPasswordReset
{
    public function handle(string $email): void
    {
        Password::sendResetLink(['email' => $email, 'is_active' => true]);
    }
}
