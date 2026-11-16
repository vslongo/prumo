<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\Media\Actions\ListOwnerPhotos;
use App\Modules\Media\Data\PhotoData;
use App\Modules\Quotes\Actions\RequestQuoteItemPhotoUpload;
use App\Modules\ServiceOrders\Models\ServiceOrder;

/**
 * Fotos dos itens da OS. Item vindo de orçamento também mostra as fotos do item do orçamento
 * (`from_quote`), sem copiar arquivo.
 */
final class ListServiceOrderPhotos
{
    public function __construct(private readonly ListOwnerPhotos $listPhotos) {}

    /** @return list<array<string, mixed>> */
    public function handle(ServiceOrder $order): array
    {
        $items = $order->items()->get(['id', 'quote_item_id']);
        $byQuoteItem = $items->whereNotNull('quote_item_id')->pluck('id', 'quote_item_id')->all();

        $format = fn (PhotoData $photo, string $itemId, bool $fromQuote) => [
            'id' => $photo->id,
            'item_id' => $itemId,
            'url' => $photo->url,
            'thumb_url' => $photo->thumbUrl,
            'created_at' => $photo->createdAt,
            'from_quote' => $fromQuote,
        ];

        $fromQuote = array_map(
            fn (PhotoData $photo) => $format($photo, (string) $byQuoteItem[$photo->ownerId], true),
            $this->listPhotos->handle(RequestQuoteItemPhotoUpload::OWNER_TYPE, array_map('strval', array_keys($byQuoteItem))),
        );
        $own = array_map(
            fn (PhotoData $photo) => $format($photo, $photo->ownerId, false),
            $this->listPhotos->handle(RequestServiceOrderItemPhotoUpload::OWNER_TYPE, array_values(array_map('strval', $items->pluck('id')->all()))),
        );

        return [...$fromQuote, ...$own];
    }
}
