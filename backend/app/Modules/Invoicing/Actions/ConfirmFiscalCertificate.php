<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Media\Actions\ConfirmUpload;
use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Actions\FindOwnerMedia;
use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Depois do PUT no R2: abre o .pfx com a senha, confere CNPJ e validade e passa a usá-lo.
 * Certificado recusado é apagado na hora; o anterior só sai depois que o novo é aceito.
 * Guarda o sha256 dos bytes validados: quem usar o certificado confere que o arquivo não foi trocado.
 */
final class ConfirmFiscalCertificate
{
    /** Teto para abrir o .pfx: a contagem de iterações vem do próprio arquivo (arquivo montado = CPU presa). */
    private const OPEN_TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly FindOwnerMedia $findMedia,
        private readonly ConfirmUpload $confirmUpload,
        private readonly DeleteMedia $deleteMedia,
    ) {}

    public function handle(string $mediaId, #[\SensitiveParameter] string $password): FiscalProfile
    {
        $profile = FiscalProfile::query()->firstOrFail();
        $media = $this->findMedia->handle(RequestCertificateUpload::OWNER_TYPE, $profile->id, $mediaId);

        // Já em uso: não revalida (o objeto pode ter sido trocado depois do aceite; o hash guardado vale).
        if ($profile->certificate_media_id === $media->id) {
            return $profile;
        }

        $this->confirmUpload->handle($media);

        try {
            $pfx = self::readCapped($media->r2_key);
            [$cnpj, $expiresAt] = self::inspect($pfx, $password);
            self::assertUsable($cnpj, $expiresAt, $profile->cnpj);
        } catch (ValidationException $e) {
            $this->deleteMedia->handle($media);

            throw $e;
        }

        $previousId = DB::transaction(function () use ($media, $password, $pfx, $cnpj, $expiresAt) {
            // Lock + revalida: o CNPJ pode ter mudado enquanto o certificado era conferido.
            $locked = FiscalProfile::query()->lockForUpdate()->firstOrFail();
            if ($locked->cnpj !== $cnpj) {
                throw ValidationException::withMessages(['certificate' => 'O CNPJ da oficina mudou. Envie o certificado de novo.']);
            }
            $previousId = $locked->certificate_media_id;
            $locked->forceFill([
                'certificate_media_id' => $media->id,
                'certificate_password' => $password,
                'certificate_sha256' => hash('sha256', $pfx),
                'certificate_expires_at' => $expiresAt,
            ])->save();

            return $previousId;
        });

        $previous = $previousId !== null ? Media::query()->find($previousId) : null;
        if ($previous !== null) {
            $this->deleteMedia->handle($previous);
        }

        return $profile->refresh();
    }

    /** Lê no máximo o limite do upload: o objeto pode ter sido regravado com outro tamanho depois da checagem. */
    private static function readCapped(string $key): string
    {
        $stream = Storage::disk('r2')->readStream($key);
        $bytes = is_resource($stream) ? (string) stream_get_contents($stream, RequestUploadUrl::MAX_BYTES + 1) : '';
        if (is_resource($stream)) {
            fclose($stream);
        }
        if ($bytes === '' || strlen($bytes) > RequestUploadUrl::MAX_BYTES) {
            throw ValidationException::withMessages(['certificate' => 'Arquivo inválido.']);
        }

        return $bytes;
    }

    /**
     * Abre o .pfx num processo `openssl` com tempo máximo (senha por variável de ambiente, nunca na linha de comando).
     * Tenta de novo com `-legacy`: muito A1 brasileiro ainda vem cifrado com algoritmo antigo (RC2/3DES).
     *
     * @return array{0: string|null, 1: Carbon}
     */
    private static function inspect(#[\SensitiveParameter] string $pfx, #[\SensitiveParameter] string $password): array
    {
        $pem = null;
        foreach ([[], ['-legacy']] as $extra) {
            $result = Process::timeout(self::OPEN_TIMEOUT_SECONDS)
                ->env(['PFX_PASS' => $password])
                ->input($pfx)
                ->run(['openssl', 'pkcs12', '-clcerts', '-nokeys', '-passin', 'env:PFX_PASS', ...$extra]);
            if ($result->successful() && str_contains($result->output(), 'BEGIN CERTIFICATE')) {
                $pem = $result->output();
                break;
            }
        }

        $x509 = $pem === null ? false : openssl_x509_parse($pem);
        if ($x509 === false) {
            throw ValidationException::withMessages(['password' => 'Não foi possível abrir o certificado: senha errada ou o arquivo não é um A1 (.pfx).']);
        }

        return [self::cnpjOf($x509), Carbon::createFromTimestampUTC((int) $x509['validTo_time_t'])];
    }

    private static function assertUsable(?string $cnpj, Carbon $expiresAt, string $profileCnpj): void
    {
        if ($cnpj !== $profileCnpj) {
            throw ValidationException::withMessages(['certificate' => 'O certificado é de outro CNPJ. Envie o e-CNPJ da oficina.']);
        }
        if ($expiresAt->isPast()) {
            throw ValidationException::withMessages(['certificate' => 'Certificado vencido em '.$expiresAt->timezone('America/Sao_Paulo')->format('d/m/Y').'.']);
        }
    }

    /**
     * e-CNPJ ICP-Brasil: o CN termina em ":<CNPJ>".
     *
     * @param  array<string, mixed>  $x509
     */
    private static function cnpjOf(array $x509): ?string
    {
        $cn = $x509['subject']['CN'] ?? '';
        $cn = is_array($cn) ? implode(' ', $cn) : (string) $cn;

        return preg_match('/:([0-9A-Z]{12}\d{2})\s*$/', $cn, $m) === 1 ? $m[1] : null;
    }
}
