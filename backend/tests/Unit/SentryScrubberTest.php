<?php

declare(strict_types=1);

use App\Shared\Support\SentryScrubber;
use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Sentry\UserDataBag;

it('tira da requisição tudo menos método e caminho, e o token de link público', function () {
    $event = Event::createEvent();
    $event->setRequest([
        'method' => 'POST',
        'url' => 'https://api.prumo.test/api/public/quotes/abc123secreto/approve?x=1',
        'data' => ['signer_name' => 'João da Silva'],
        'headers' => ['cookie' => ['laravel_session=x']],
        'query_string' => 'x=1',
        'cookies' => ['laravel_session' => 'x'],
    ]);

    expect(SentryScrubber::beforeSend($event)->getRequest())
        ->toBe(['method' => 'POST', 'url' => 'https://api.prumo.test/api/public/quotes/[token]/approve']);
});

it('busca de cliente não vaza o telefone pela query string', function () {
    expect(SentryScrubber::url('/api/customers?q=11988887777'))->toBe('/api/customers')
        ->and(SentryScrubber::url('/api/public/invitations/tok3n'))->toBe('/api/public/invitations/[token]');
});

it('usuário só com o id', function () {
    $event = Event::createEvent();
    $event->setUser(UserDataBag::createFromArray(['id' => 'u1', 'email' => 'dono@oficina.test', 'ip_address' => '1.2.3.4', 'username' => 'Carlos']));

    $user = SentryScrubber::beforeSend($event)->getUser();

    expect($user?->getId())->toBe('u1')
        ->and($user?->getEmail())->toBeNull()
        ->and($user?->getIpAddress())->toBeNull()
        ->and($user?->getUsername())->toBeNull();
});

it('erro de banco perde o SQL com valores e o DETAIL', function () {
    $sql = "SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint \"users_email_unique\"\n"
        .'DETAIL:  Key (email)=(joao@cliente.test) already exists. (Connection: pgsql, SQL: insert into "users" ("name", "email") values (João, joao@cliente.test))';
    $event = Event::createEvent();
    $event->setExceptions([new ExceptionDataBag(new RuntimeException($sql))]);
    $event->setBreadcrumb([new Breadcrumb(Breadcrumb::LEVEL_ERROR, Breadcrumb::TYPE_DEFAULT, 'log', $sql, ['url' => '/api/customers?q=123'])]);

    $scrubbed = SentryScrubber::beforeSend($event);

    expect($scrubbed->getExceptions()[0]->getValue())
        ->toBe('SQLSTATE[23505]: Unique violation: 7 ERROR:  duplicate key value violates unique constraint "users_email_unique"')
        ->and($scrubbed->getBreadcrumbs()[0]->getMessage())->not->toContain('joao@')
        ->and($scrubbed->getBreadcrumbs()[0]->getMetadata()['url'])->toBe('/api/customers');
});

it('mensagem comum passa intacta', function () {
    expect(SentryScrubber::message('Assinatura inválida.'))->toBe('Assinatura inválida.');
});
