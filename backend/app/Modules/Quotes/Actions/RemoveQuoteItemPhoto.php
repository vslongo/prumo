<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Actions\FindOwnerMedia;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Validation\ValidationException;

/** Depois de aprovado/recusado, as fotos ficam como o cliente viu. */
final class RemoveQuoteItemPhoto
{
    public function __construct(
        private readonly FindOwnerMedia $findMedia,
        private readonly DeleteMedia $deleteMedia,
    ) {}

    public function handle(Quote $quote, QuoteItem $item, string $mediaId): void
    {
        $media = $this->findMedia->handle(RequestQuoteItemPhotoUpload::OWNER_TYPE, $item->id, $mediaId);

        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages(['quote' => 'Orçamento aprovado, recusado ou expirado: as fotos não podem ser removidas.']);
        }

        $this->deleteMedia->handle($media);
    }
}
