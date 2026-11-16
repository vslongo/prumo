<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Middleware;

use App\Modules\Billing\Actions\ResolveTenantAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Somente leitura (trial vencido, atraso além da carência, cancelada sem período pago): bloqueia toda escrita
 * das rotas logadas da oficina. Leitura segue livre; assinar e sair também.
 * Links públicos (cliente aprovando orçamento) e webhooks não passam por aqui.
 */
final class EnsureTenantCanWrite
{
    public function __construct(private readonly ResolveTenantAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || $request->is('api/billing', 'api/billing/*', 'api/logout')) {
            return $next($request);
        }

        if (! $this->access->handle()->canWrite) {
            return response()->json([
                'message' => 'A oficina está em modo somente leitura: a assinatura não está em dia. O dono pode regularizar em Configurações › Assinatura.',
                'code' => 'read_only',
            ], 402);
        }

        return $next($request);
    }
}
