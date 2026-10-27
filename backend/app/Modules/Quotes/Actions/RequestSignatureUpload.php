<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Validation\ValidationException;

/** URL pré-assinada para o navegador do cliente subir o PNG da assinatura direto no R2. */
final class RequestSignatureUpload
{
    public const OWNER_TYPE = 'quote_signature';

    public function __construct(
        private readonly OpenPublicQuote $open,
        private readonly RequestUploadUrl $requestUploadUrl,
    ) {}

    /** @return array{url: string, headers: array<string, mixed>, expires_at: string} */
    public function handle(Quote $quote, int $size): array
    {
        $this->open->handle($quote);

        if (! $quote->status->awaitsCustomer()) {
            throw ValidationException::withMessages(['quote' => 'Este orçamento não está aguardando aprovação.']);
        }

        $upload = $this->requestUploadUrl->handle(self::OWNER_TYPE, $quote->id, 'image/png', $size, ['image/png']);

        return ['url' => $upload['url'], 'headers' => $upload['headers'], 'expires_at' => $upload['expires_at']];
    }
}
