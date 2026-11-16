<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Actions\ResolveTwoFactorRequirement;
use App\Modules\Auth\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dono que passou do prazo sem ativar o 2FA: só consegue ver quem está logado, sair e ativar.
 * O resto das rotas da oficina responde 403 `two_factor_required` (o app mostra a tela de ativação).
 */
final class EnsureTwoFactorEnabled
{
    public function __construct(private readonly ResolveTwoFactorRequirement $requirement) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $request->is('api/me', 'api/logout', 'api/two-factor', 'api/two-factor/*')) {
            return $next($request);
        }

        if ($this->requirement->handle($user)['blocked']) {
            return response()->json([
                'message' => 'Ative a verificação em duas etapas para continuar usando o Prumo.',
                'code' => 'two_factor_required',
            ], 403);
        }

        return $next($request);
    }
}
