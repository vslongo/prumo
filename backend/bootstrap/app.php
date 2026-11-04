<?php

use App\Modules\Auth\Http\Middleware\EnsureTwoFactorEnabled;
use App\Modules\Billing\Http\Middleware\EnsureTenantCanWrite;
use App\Modules\Tenancy\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // `tenant`: define a oficina do usuário logado, exige o 2FA do dono passado o prazo (Auth)
        // e barra escrita em modo somente leitura (Billing).
        $middleware->group('tenant', [ResolveTenant::class, EnsureTwoFactorEnabled::class, EnsureTenantCanWrite::class]);
        // O tenant precisa estar no contexto antes do route model binding, senão o scope barra tudo.
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveTenant::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));
    })->create();
