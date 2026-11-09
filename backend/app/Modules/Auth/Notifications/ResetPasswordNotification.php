<?php

declare(strict_types=1);

namespace App\Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail com o link para criar uma senha nova (ADR 0007). Vai pela fila: o envio nunca roda na request.
 * O link não leva o e-mail (dado pessoal fora da URL); a pessoa digita de novo na tela.
 */
final class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(private readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Criar uma senha nova no Prumo')
            ->greeting('Olá!')
            ->line('Recebemos um pedido para criar uma senha nova para a sua conta no Prumo.')
            ->action('Criar senha nova', $this->url())
            ->line("O link vale por {$minutes} minutos e só pode ser usado uma vez.")
            ->line('Se não foi você que pediu, ignore este e-mail: a sua senha continua a mesma.')
            ->salutation('Equipe Prumo');
    }

    public function url(): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/redefinir-senha/'.$this->token;
    }
}
