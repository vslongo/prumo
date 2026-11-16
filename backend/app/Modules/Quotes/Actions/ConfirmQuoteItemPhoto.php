<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Media\Actions\ConfirmUpload;
use App\Modules\Media\Actions\FindOwnerMedia;
use App\Modules\Quotes\Models\QuoteItem;

/**
 * Confirma a foto depois do PUT no R2. Sem checar estado do orçamento: o upload foi autorizado quando
 * pedido e sobe em segundo plano, então pode terminar depois de o cliente decidir.
 */
final class ConfirmQuoteItemPhoto
{
    public function __construct(
        private readonly FindOwnerMedia $findMedia,
        private readonly ConfirmUpload $confirmUpload,
    ) {}

    public function handle(QuoteItem $item, string $mediaId): void
    {
        $this->confirmUpload->handle($this->findMedia->handle(RequestQuoteItemPhotoUpload::OWNER_TYPE, $item->id, $mediaId));
    }
}
