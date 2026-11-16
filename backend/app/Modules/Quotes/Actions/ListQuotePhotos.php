<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Media\Actions\ListOwnerPhotos;
use App\Modules\Media\Data\PhotoData;
use App\Modules\Quotes\Models\Quote;

/** Fotos dos itens do orçamento. No link público, sem ids: o item é identificado pela posição. */
final class ListQuotePhotos
{
    public function __construct(private readonly ListOwnerPhotos $listPhotos) {}

    /** @return list<array<string, mixed>> */
    public function handle(Quote $quote, bool $public = false): array
    {
        $positions = $quote->items()->pluck('position', 'id')->all();
        $photos = $this->listPhotos->handle(RequestQuoteItemPhotoUpload::OWNER_TYPE, array_map('strval', array_keys($positions)));

        return array_map(fn (PhotoData $photo) => $public
            ? ['position' => (int) $positions[$photo->ownerId], 'url' => $photo->url, 'thumb_url' => $photo->thumbUrl]
            : ['id' => $photo->id, 'item_id' => $photo->ownerId, 'url' => $photo->url, 'thumb_url' => $photo->thumbUrl, 'created_at' => $photo->createdAt],
            $photos);
    }
}
