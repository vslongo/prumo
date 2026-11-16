<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;

/** Upload pedido e nunca confirmado (pending há mais de 24 h) no tenant atual: apaga arquivo e registro. */
final class PurgeStalePendingMedia
{
    public function __construct(private readonly DeleteMedia $delete) {}

    public function handle(): int
    {
        $stale = Media::query()
            ->where('status', MediaStatus::Pending)
            ->where('created_at', '<', now()->subDay())
            ->get();

        $stale->each(fn (Media $media) => $this->delete->handle($media));

        return $stale->count();
    }
}
