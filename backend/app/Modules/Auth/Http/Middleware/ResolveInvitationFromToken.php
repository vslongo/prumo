<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Middleware;

use App\Modules\Auth\Models\UserInvitation;
use App\Modules\Tenancy\Actions\ResolvePublicToken;
use App\Modules\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Link público de convite: o tenant vem do convite achado pelo token, nunca da URL. */
final class ResolveInvitationFromToken
{
    public function __construct(private readonly ResolvePublicToken $resolve) {}

    public function handle(Request $request, Closure $next): Response
    {
        $invitation = $this->resolve->handle(UserInvitation::class, (string) $request->route('token'));
        abort_if($invitation === null, 404, 'Convite inválido.');

        TenantContext::set($invitation->tenant_id);
        $request->attributes->set('invitation', $invitation);

        return $next($request);
    }
}
