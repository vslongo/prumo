<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\Media;
use DateTimeInterface;

final class FindLatestOwnerMedia
{
    public function handle(string $ownerType, string $ownerId, ?DateTimeInterface $since = null): ?Media
    {
        return Media::query()
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->latest()
            ->first();
    }
}
