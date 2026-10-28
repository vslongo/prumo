<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Callback do worker WhatsApp: corpo assinado com HMAC-SHA256 do segredo compartilhado
 * (`X-Signature: sha256=<hex>`). Sem segredo configurado, recusa tudo.
 */
final class VerifyWorkerSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.whatsapp.worker_secret');
        $given = (string) $request->header('X-Signature');
        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), is_string($secret) ? $secret : '');

        abort_if(! is_string($secret) || $secret === '' || ! hash_equals($expected, $given), 401, 'Assinatura inválida.');

        return $next($request);
    }
}
