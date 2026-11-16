<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Billing\Actions\EnsureSeatAvailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Funcionário aceita o convite: vira usuário da oficina do convite, com o papel definido pelo dono. Uso único. */
final class AcceptInvitation
{
    public function __construct(private readonly EnsureSeatAvailable $ensureSeat) {}

    public function handle(UserInvitation $invitation, string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($invitation, $name, $email, $password) {
            // Relê com lock: dois cliques no mesmo link não criam dois usuários.
            $invitation = UserInvitation::query()->lockForUpdate()->findOrFail($invitation->id);

            if (! $invitation->isUsable()) {
                throw ValidationException::withMessages(['invitation' => 'Este convite já foi usado ou expirou. Peça um novo ao dono da oficina.']);
            }

            // O plano pode ter diminuído depois do convite.
            $this->ensureSeat->handle(User::query()->where('tenant_id', $invitation->tenant_id)->where('is_active', true)->count(), 'invitation');

            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->tenant_id = $invitation->tenant_id;
            $user->role = $invitation->role;
            $user->save();

            $invitation->accepted_user_id = $user->id;
            $invitation->accepted_at = now();
            $invitation->save();

            return $user;
        });
    }
}
