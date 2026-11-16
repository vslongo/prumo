<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Validation\ValidationException;

/**
 * URL pré-assinada para o técnico subir foto de um item direto no R2.
 * Foto é registro, não condição comercial: não gera nova versão nem invalida o link.
 */
final class RequestQuoteItemPhotoUpload
{
    public const OWNER_TYPE = 'quote_item';

    public function __construct(private readonly RequestUploadUrl $requestUploadUrl) {}

    /** @return array{media_id: string, url: string, headers: array<string, mixed>, expires_at: string} */
    public function handle(Quote $quote, QuoteItem $item, string $mime, int $size): array
    {
        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages(['quote' => 'Orçamento aprovado, recusado ou expirado não recebe fotos.']);
        }

        $upload = $this->requestUploadUrl->handle(self::OWNER_TYPE, $item->id, $mime, $size, RequestUploadUrl::PHOTO_MIMES);

        return ['media_id' => $upload['media']->id, 'url' => $upload['url'], 'headers' => $upload['headers'], 'expires_at' => $upload['expires_at']];
    }
}
