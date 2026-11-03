<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Webhook do Asaas: header `asaas-access-token` igual ao token configurado. Sem token configurado, recusa tudo. */
final class VerifyAsaasToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.asaas.webhook_token');
        $given = (string) $request->header('asaas-access-token');

        abort_if(! is_string($expected) || $expected === '' || ! hash_equals($expected, $given), 401, 'Token inválido.');

        return $next($request);
    }
}
