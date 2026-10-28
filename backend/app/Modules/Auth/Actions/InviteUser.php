<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Events\UserInvited;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;

/**
 * Gera link de convite (token ≥ 32 bytes) para o funcionário criar o próprio acesso.
 * A busca do link é pelo hash; o token cru fica criptografado só para o dono copiar o link de novo.
 * O retorno traz o wa.me com texto pronto, sem número: o dono escolhe o contato no WhatsApp.
 *
 * @phpstan-type InvitationLinks array{link: string, whatsapp_url: string}
 * @phpstan-type SentInvitation array{invitation: UserInvitation, link: string, whatsapp_url: string, expires_at: string}
 */
final class InviteUser
{
    public const LINK_TTL_DAYS = 7;

    /** @return SentInvitation */
    public function handle(User $invitedBy, string $name, UserRole $role): array
    {
        $token = bin2hex(random_bytes(32));

        $invitation = UserInvitation::create([
            'name' => $name,
            'role' => $role,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'expires_at' => now()->addDays(self::LINK_TTL_DAYS),
            'invited_by_id' => $invitedBy->id,
        ]);

        UserInvited::dispatch($invitation->tenant_id, $invitation->id);

        return [
            'invitation' => $invitation,
            ...self::links($name, $token),
            'expires_at' => $invitation->expires_at->toIso8601String(),
        ];
    }

    /** @return InvitationLinks */
    public static function links(string $name, string $token): array
    {
        $link = rtrim((string) config('app.frontend_url'), '/').'/convite/'.$token;
        $text = sprintf('Olá, %s! Crie seu acesso ao Prumo da oficina por este link: %s', $name, $link);

        return ['link' => $link, 'whatsapp_url' => 'https://wa.me/?text='.rawurlencode($text)];
    }
}
