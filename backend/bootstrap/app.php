<?php

use App\Modules\Auth\Http\Middleware\EnsureTwoFactorEnabled;
use App\Modules\Billing\Http\Middleware\EnsureTenantCanWrite;
use App\Modules\Tenancy\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // Em dev o Next (localhost:5173) faz proxy para cá e troca o Host. Confiar no loopback e na rede do Docker faz o Laravel
        // ler o X-Forwarded-Host e validar as URLs assinadas do R2 local com a origem do navegador (ADR 0009).
        $middleware->trustProxies(at: ['127.0.0.1', '::1', '172.16.0.0/12']);
        // `tenant`: define a oficina do usuário logado, exige o 2FA do dono passado o prazo (Auth)
        // e barra escrita em modo somente leitura (Billing).
        $middleware->group('tenant', [ResolveTenant::class, EnsureTwoFactorEnabled::class, EnsureTenantCanWrite::class]);
        // O tenant precisa estar no contexto antes do route model binding, senão o scope barra tudo.
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveTenant::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sentry (sem DSN não envia nada); o filtro de dado pessoal está em config/sentry.php.
        Integration::handles($exceptions);
        $exceptions->shouldRenderJsonWhen(fn ($request) => $request->is('api/*'));
    })->create();
