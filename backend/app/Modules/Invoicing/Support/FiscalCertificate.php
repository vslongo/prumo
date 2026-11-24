<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Certificado A1 da oficina aberto em memória, para assinar a DPS e fazer o mTLS.
 * Nunca vai para disco: o cURL recebe certificado e chave como blob (CURLOPT_SSL*_BLOB).
 * Confere o sha256 guardado no aceite: arquivo trocado no R2 depois disso não é usado.
 */
final class FiscalCertificate
{
    private function __construct(
        #[\SensitiveParameter] public readonly string $certPem,
        #[\SensitiveParameter] public readonly string $keyPem,
    ) {}

    /** @throws InvalidCertificate */
    public static function load(FiscalProfile $profile): self
    {
        if (! $profile->hasValidCertificate()) {
            throw new InvalidCertificate('Certificado digital ausente ou vencido. Envie um novo em Configurações → Nota fiscal.');
        }

        $media = Media::query()->find($profile->certificate_media_id);
        $pfx = $media === null ? null : Storage::disk('r2')->get($media->r2_key);
        if ($pfx === null || ! hash_equals((string) $profile->certificate_sha256, hash('sha256', $pfx))) {
            throw new InvalidCertificate('O arquivo do certificado não confere com o enviado. Envie o certificado de novo.');
        }

        $pem = Pkcs12::toPem($pfx, (string) $profile->certificate_password, withKey: true);
        if ($pem === null
            || preg_match('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', $pem, $cert) !== 1
            || preg_match('/-----BEGIN (?:RSA )?PRIVATE KEY-----.+?-----END (?:RSA )?PRIVATE KEY-----/s', $pem, $key) !== 1) {
            throw new InvalidCertificate('Não foi possível abrir o certificado. Envie o certificado de novo.');
        }

        return new self($cert[0], $key[0]);
    }
}
