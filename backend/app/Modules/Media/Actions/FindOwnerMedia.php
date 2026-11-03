<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\Media;

/** Mídia de um dono específico; de outro dono (ou de outro tenant) = 404. */
final class FindOwnerMedia
{
    public function handle(string $ownerType, string $ownerId, string $mediaId): Media
    {
        return Media::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->whereKey($mediaId)
            ->firstOrFail();
    }
}
