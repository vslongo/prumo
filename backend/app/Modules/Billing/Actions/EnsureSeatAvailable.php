<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use Illuminate\Validation\ValidationException;

/**
 * Limite de usuários do plano. Quem chama (Auth) informa quantos lugares já estão ocupados
 * (usuários ativos, e no convite também os convites pendentes).
 */
final class EnsureSeatAvailable
{
    public function __construct(private readonly ResolveTenantAccess $access) {}

    public function handle(int $usedSeats, string $field = 'plan'): void
    {
        $access = $this->access->handle();
        $max = $access->maxUsers();

        if ($max !== null && $usedSeats >= $max) {
            throw ValidationException::withMessages([$field => sprintf(
                'O plano %s permite até %d %s. Troque de plano em Configurações › Assinatura para adicionar mais.',
                $access->plan?->label(),
                $max,
                $max === 1 ? 'usuário' : 'usuários',
            )]);
        }
    }
}
