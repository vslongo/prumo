<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Middleware;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Tenancy\Actions\ResolvePublicToken;
use App\Modules\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Link público: o tenant vem do orçamento achado pelo token, nunca de parâmetro da URL. */
final class ResolveQuoteFromToken
{
    public function __construct(private readonly ResolvePublicToken $resolve) {}

    public function handle(Request $request, Closure $next): Response
    {
        $quote = $this->resolve->handle(Quote::class, (string) $request->route('token'));
        abort_if($quote === null, 404, 'Link inválido.');

        TenantContext::set($quote->tenant_id);
        $request->attributes->set('quote', $quote);

        return $next($request);
    }
}
