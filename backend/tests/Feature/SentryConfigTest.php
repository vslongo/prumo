<?php

declare(strict_types=1);

use App\Modules\Tenancy\TenantContext;
use App\Shared\Support\SentryScrubber;
use Sentry\State\Scope;

use function Sentry\configureScope;

it('liga o filtro e desliga o envio de dado pessoal', function () {
    expect(config('sentry.send_default_pii'))->toBeFalse()
        ->and(config('sentry.max_request_body_size'))->toBe('never')
        ->and(config('sentry.before_send'))->toBe([SentryScrubber::class, 'beforeSend']);
});

it('marca o tenant atual no escopo do Sentry e limpa ao sair', function () {
    $tag = function (): ?string {
        $value = null;
        configureScope(function (Scope $scope) use (&$value) {
            $value = (fn () => $this->tags['tenant_id'] ?? null)->call($scope);
        });

        return $value;
    };

    TenantContext::run('t-1', function () use ($tag) {
        expect($tag())->toBe('t-1');
    });
    expect($tag())->toBeNull();
});
