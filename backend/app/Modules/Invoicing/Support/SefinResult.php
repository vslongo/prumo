<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Resposta de envio à Sefin: aceita (com XML devolvido) ou recusada (código e descrição do primeiro erro).
 * Erro de servidor/indisponível vira exceção: a fila tenta de novo.
 */
final readonly class SefinResult
{
    private function __construct(
        public bool $accepted,
        public ?string $accessKey,
        public ?string $xml,
        public ?string $errorCode,
        public ?string $errorMessage,
    ) {}

    public static function from(Response $response): self
    {
        if ($response->serverError() || $response->status() === 429 || $response->status() === 0) {
            throw new RuntimeException('Sefin indisponível ('.$response->status().').');
        }

        if ($response->successful()) {
            $key = $response->json('chaveAcesso');
            $xml = SefinClient::unpack($response->json('nfseXmlGZipB64') ?? $response->json('eventoXmlGZipB64'));

            return new self(true, is_string($key) ? $key : null, $xml, null, null);
        }

        // A Sefin já respondeu com "erros"/"Erros" e "codigo"/"Codigo": aceita as duas grafias.
        $errors = $response->json('erros') ?? $response->json('Erros') ?? [];
        $first = is_array($errors) && isset($errors[0]) && is_array($errors[0]) ? array_change_key_case($errors[0]) : [];

        return new self(
            false,
            null,
            null,
            isset($first['codigo']) ? mb_substr((string) $first['codigo'], 0, 20) : 'HTTP'.$response->status(),
            isset($first['descricao']) ? mb_substr((string) $first['descricao'], 0, 1000) : 'A Sefin recusou a nota sem dizer o motivo.',
        );
    }
}
