<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Media\Actions\RequestUploadUrl;
use Illuminate\Validation\ValidationException;

/** URL pré-assinada para o navegador subir o certificado A1 (.pfx) direto no R2. */
final class RequestCertificateUpload
{
    public const OWNER_TYPE = 'fiscal_certificate';

    public function __construct(private readonly RequestUploadUrl $requestUploadUrl) {}

    /** @return array{media_id: string, url: string, headers: array<string, mixed>, expires_at: string} */
    public function handle(int $size): array
    {
        $profile = FiscalProfile::query()->first()
            ?? throw ValidationException::withMessages(['profile' => 'Preencha os dados fiscais antes de enviar o certificado.']);

        $mime = RequestUploadUrl::CERTIFICATE_MIME;
        $upload = $this->requestUploadUrl->handle(self::OWNER_TYPE, $profile->id, $mime, $size, [$mime]);

        return ['media_id' => $upload['media']->id, 'url' => $upload['url'], 'headers' => $upload['headers'], 'expires_at' => $upload['expires_at']];
    }
}
