<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;

final class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Painel /horizon fora do ambiente local: só os e-mails de HORIZON_ALLOWED_EMAILS (equipe do Prumo),
     * logados pelo app. Vazio = ninguém entra.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user = null) => $user !== null
            && $user->is_active
            && in_array($user->email, config('horizon.allowed_emails'), true));
    }
}
