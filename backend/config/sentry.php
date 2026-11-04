<?php

declare(strict_types=1);

use App\Shared\Support\SentryScrubber;

// Só o que difere do padrão do sentry-laravel (o resto vem do config do pacote). Sem DSN, nada é enviado.
return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV')),

    'release' => env('SENTRY_RELEASE'),

    // LGPD: nunca IP, cookies, usuário logado completo nem corpo da requisição. Fixo, não vem do .env.
    'send_default_pii' => false,

    'max_request_body_size' => 'never',

    'before_send' => [SentryScrubber::class, 'beforeSend'],

    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_TRACES_SAMPLE_RATE'),
];
