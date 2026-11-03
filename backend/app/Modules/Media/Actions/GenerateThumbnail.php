<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

/** Miniatura WebP de até 320 px para as listas no celular. Idempotente; imagem ilegível fica sem miniatura. */
final class GenerateThumbnail
{
    public const WIDTH = 320;

    private const IMAGES = ['image/jpeg', 'image/png', 'image/webp'];

    public function handle(string $mediaId): void
    {
        $media = Media::query()->find($mediaId);
        if ($media === null || $media->thumb_key !== null || $media->status !== MediaStatus::Uploaded || ! in_array($media->mime, self::IMAGES, true)) {
            return;
        }

        $disk = Storage::disk('r2');
        $image = @imagecreatefromstring((string) $disk->get($media->r2_key));
        if ($image === false) {
            return;
        }

        $thumb = imagescale($image, min(self::WIDTH, imagesx($image)));
        if ($thumb === false) {
            return;
        }

        ob_start();
        imagewebp($thumb, null, 70);
        $bytes = (string) ob_get_clean();

        $key = (string) preg_replace('/\.\w+$/', '_thumb.webp', $media->r2_key);
        $disk->put($key, $bytes, ['ContentType' => 'image/webp']);
        $media->update(['thumb_key' => $key]);
    }
}
