<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

/** URL de leitura assinada (bucket privado). Curta: o front busca de novo antes de expirar. */
final class GetSignedReadUrl
{
    public const TTL_MINUTES = 30;

    public function handle(Media $media, bool $thumb = false): string
    {
        $key = $thumb && $media->thumb_key !== null ? $media->thumb_key : $media->r2_key;

        return Storage::disk('r2')->temporaryUrl($key, now()->addMinutes(self::TTL_MINUTES));
    }
}
