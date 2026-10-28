<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Http\Middleware;

use App\Modules\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Define o tenant atual a partir do usuário logado. Roda antes do route model binding.
 * Usuário desativado com sessão ainda aberta para aqui (401): o front volta para o login.
 */
final class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_if($user !== null && $user->getAttribute('is_active') === false, 401, 'Acesso desativado.');

        $tenantId = $user?->getAttribute('tenant_id');
        abort_if(! is_string($tenantId), 403);

        TenantContext::set($tenantId);

        return $next($request);
    }
}
