<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Data\PhotoData;
use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;

/** Fotos já enviadas de vários donos do mesmo tipo, em ordem de envio. */
final class ListOwnerPhotos
{
    public function __construct(private readonly GetSignedReadUrl $signedUrl) {}

    /**
     * @param  list<string>  $ownerIds
     * @return list<PhotoData>
     */
    public function handle(string $ownerType, array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        return array_values(Media::query()
            ->where('owner_type', $ownerType)
            ->whereIn('owner_id', $ownerIds)
            ->where('status', MediaStatus::Uploaded)
            ->oldest()
            ->get()
            ->map(fn (Media $media) => new PhotoData(
                id: $media->id,
                ownerId: $media->owner_id,
                url: $this->signedUrl->handle($media),
                thumbUrl: $this->signedUrl->handle($media, thumb: true),
                createdAt: $media->created_at->toIso8601String(),
            ))
            ->all());
    }
}
