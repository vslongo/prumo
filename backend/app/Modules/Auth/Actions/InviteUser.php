<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Events\UserInvited;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;

/**
 * Gera link de convite (token ≥ 32 bytes, guardado só o hash) para o funcionário criar o próprio acesso.
 * O retorno traz o wa.me com texto pronto, sem número: o dono escolhe o contato no WhatsApp.
 *
 * @phpstan-type SentInvitation array{invitation: UserInvitation, link: string, whatsapp_url: string, expires_at: string}
 */
final class InviteUser
{
    public const LINK_TTL_DAYS = 7;

    /** @return SentInvitation */
    public function handle(User $invitedBy, string $name, UserRole $role): array
    {
        $token = bin2hex(random_bytes(32));
        $link = rtrim((string) config('app.frontend_url'), '/').'/convite/'.$token;

        $invitation = UserInvitation::create([
            'name' => $name,
            'role' => $role,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(self::LINK_TTL_DAYS),
            'invited_by_id' => $invitedBy->id,
        ]);

        UserInvited::dispatch($invitation->tenant_id, $invitation->id);

        $text = sprintf('Olá, %s! Crie seu acesso ao Prumo da oficina por este link: %s', $name, $link);

        return [
            'invitation' => $invitation,
            'link' => $link,
            'whatsapp_url' => 'https://wa.me/?text='.rawurlencode($text),
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ];
    }
}
