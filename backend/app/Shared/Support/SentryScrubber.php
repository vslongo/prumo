<?php

declare(strict_types=1);

namespace App\Shared\Support;

use Sentry\Breadcrumb;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;

/**
 * Último filtro antes de mandar um erro ao Sentry (CLAUDE.md §10: nada de dado pessoal fora do nosso banco).
 * - Requisição: só método e caminho. Sem corpo, headers, cookies nem query string (a busca leva telefone/CPF).
 * - Links públicos: o token do orçamento/convite vira `[token]` (ele dá acesso ao orçamento do cliente).
 * - Usuário: só o id. O tenant vai como tag (ResolveTenant).
 * - Erros de banco: o Laravel põe o SQL com os valores na mensagem e o Postgres repete valores no DETAIL;
 *   fica só a primeira linha do erro, sem o SQL.
 */
final class SentryScrubber
{
    public static function beforeSend(Event $event, ?EventHint $hint = null): Event
    {
        $request = $event->getRequest();
        if ($request !== []) {
            $event->setRequest(array_filter([
                'method' => $request['method'] ?? null,
                'url' => isset($request['url']) && is_string($request['url']) ? self::url($request['url']) : null,
            ]));
        }

        $userId = $event->getUser()?->getId();
        $event->setUser($userId === null ? null : UserDataBag::createFromUserIdentifier($userId));

        foreach ($event->getExceptions() as $exception) {
            $exception->setValue(self::message($exception->getValue()));
        }

        $event->setBreadcrumb(array_map(self::breadcrumb(...), $event->getBreadcrumbs()));

        return $event;
    }

    /** Sem query string e sem token de link público. */
    public static function url(string $url): string
    {
        $url = explode('?', $url, 2)[0];

        return (string) preg_replace('#(/public/(?:quotes|invitations)/)[^/]+#', '$1[token]', $url);
    }

    public static function message(string $message): string
    {
        if (! str_starts_with($message, 'SQLSTATE')) {
            return $message;
        }

        return trim((string) preg_replace('/\s*\(Connection: .*$/s', '', explode("\n", $message, 2)[0]));
    }

    private static function breadcrumb(Breadcrumb $breadcrumb): Breadcrumb
    {
        $metadata = $breadcrumb->getMetadata();
        if (isset($metadata['url']) && is_string($metadata['url'])) {
            $breadcrumb = $breadcrumb->withMetadata('url', self::url($metadata['url']));
        }
        $message = $breadcrumb->getMessage();

        return $message === null ? $breadcrumb : $breadcrumb->withMessage(self::message($message));
    }
}
