<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\UserInvitation;
use Illuminate\Validation\ValidationException;

/** Cancela um convite ainda não usado: o link passa a mostrar "já foi usado ou expirou". */
final class RevokeInvitation
{
    public function handle(UserInvitation $invitation): void
    {
        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages(['invitation' => 'Esse convite já foi usado.']);
        }

        // ponytail: cancelar = expirar agora; coluna revoked_at só se precisar distinguir no histórico.
        $invitation->expires_at = now();
        $invitation->save();
    }
}
