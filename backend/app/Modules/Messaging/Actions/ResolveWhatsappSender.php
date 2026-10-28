<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Actions;

/**
 * De qual número a oficina manda WhatsApp. Único ponto da decisão (ADR 0003): hoje sempre o número do Prumo;
 * quando a oficina puder conectar o próprio número, é aqui que ele entra. Null = sem WhatsApp configurado (só wa.me).
 */
final class ResolveWhatsappSender
{
    public function handle(string $tenantId): ?string
    {
        $prumo = config('services.whatsapp.phone_number_id');

        return is_string($prumo) && $prumo !== '' ? $prumo : null;
    }
}
