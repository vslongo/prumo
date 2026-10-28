<?php

declare(strict_types=1);

namespace App\Modules\Auth\Actions;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\DB;

/** Funcionário saiu: não entra mais e cai das sessões abertas. OS e itens antigos continuam com o nome dele. */
final class DeactivateUser
{
    public function handle(User $user): User
    {
        DB::transaction(function () use ($user) {
            $user->is_active = false;
            $user->save();

            // ponytail: sessões no banco (SESSION_DRIVER=database); o ResolveTenant barra o resto.
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->tokens()->delete();
        });

        return $user;
    }
}
