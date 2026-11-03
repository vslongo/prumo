<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

/** Apaga arquivo, miniatura e registro. Quem chama decide se pode (estado do dono). */
final class DeleteMedia
{
    public function handle(Media $media): void
    {
        Storage::disk('r2')->delete(array_values(array_filter([$media->r2_key, $media->thumb_key])));
        $media->delete();
    }
}
