<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use App\Modules\Invoicing\Enums\FiscalEnvironment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * API da Sefin Nacional (NFS-e), com mTLS pelo certificado da oficina. Só usado em jobs (fila `fiscal`).
 * Corpo e respostas trafegam o XML compactado (GZip) em Base64.
 * ponytail: caminhos e nomes de campo conferidos em fontes de terceiros; validar no Swagger oficial na homologação.
 */
final class SefinClient
{
    /** Erro da Sefin para DPS já recebida: recupera a nota em vez de emitir de novo. */
    public const DUPLICATE_DPS = 'E0014';

    public function __construct(
        private readonly FiscalEnvironment $environment,
        private readonly FiscalCertificate $certificate,
    ) {}

    /** Teto do XML descompactado: resposta inflada não derruba o worker. */
    private const MAX_XML_BYTES = 5 * 1024 * 1024;

    /** Envia a DPS assinada. */
    public function submit(string $signedXml): SefinResult
    {
        return SefinResult::from($this->http()->post('/nfse', ['dpsXmlGZipB64' => self::pack($signedXml)]));
    }

    /** Chave de acesso da NFS-e gerada para essa DPS, ou null se a Sefin não a recebeu. */
    public function findAccessKey(string $dpsId): ?string
    {
        $response = $this->http()->get('/dps/'.$dpsId);
        // 404 (ou 400 "não encontrada") = a Sefin não recebeu essa DPS. 401/403/5xx sobem como erro.
        if (in_array($response->status(), [400, 404], true)) {
            return null;
        }
        $this->failOnServerError($response);
        $key = $response->json('chaveAcesso');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /** XML da NFS-e autorizada. */
    public function fetchNfse(string $accessKey): string
    {
        $response = $this->http()->get('/nfse/'.$accessKey);
        $this->failOnServerError($response);
        $xml = self::unpack($response->json('nfseXmlGZipB64'));
        if ($xml === null) {
            throw new RuntimeException('Sefin não devolveu o XML da NFS-e.');
        }

        return $xml;
    }

    /** O evento (ex.: cancelamento) já está registrado nessa nota? */
    public function hasEvent(string $accessKey, string $eventType, int $sequence = 1): bool
    {
        $response = $this->http()->get('/nfse/'.$accessKey.'/eventos/'.$eventType.'/'.$sequence);
        if (in_array($response->status(), [400, 404], true)) {
            return false;
        }
        $this->failOnServerError($response);

        return true;
    }

    /** Registra um evento (ex.: cancelamento) já assinado. */
    public function registerEvent(string $accessKey, string $signedXml): SefinResult
    {
        return SefinResult::from($this->http()->post('/nfse/'.$accessKey.'/eventos', ['pedidoRegistroEventoXmlGZipB64' => self::pack($signedXml)]));
    }

    public static function pack(string $xml): string
    {
        return base64_encode((string) gzencode($xml));
    }

    public static function unpack(mixed $b64): ?string
    {
        if (! is_string($b64) || $b64 === '') {
            return null;
        }
        $xml = @gzdecode((string) base64_decode($b64, true), self::MAX_XML_BYTES);

        return $xml === false ? null : $xml;
    }

    private function http(): PendingRequest
    {
        $baseUrl = (string) config('services.nfse.'.$this->environment->value.'.sefin_url');

        return Http::baseUrl($baseUrl)
            ->withOptions(['curl' => [
                CURLOPT_SSLCERTTYPE => 'PEM',
                CURLOPT_SSLCERT_BLOB => $this->certificate->certPem,
                CURLOPT_SSLKEYTYPE => 'PEM',
                CURLOPT_SSLKEY_BLOB => $this->certificate->keyPem,
            ]])
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(20);
    }

    private function failOnServerError(Response $response): void
    {
        if (! $response->successful()) {
            throw new RuntimeException('Sefin respondeu '.$response->status().'.');
        }
    }
}
