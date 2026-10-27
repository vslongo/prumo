<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Events\MediaUploaded;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Confere no R2 que o arquivo chegou, respeita o limite e é mesmo do tipo declarado (bytes iniciais);
 * marca `uploaded`. Idempotente.
 */
final class ConfirmUpload
{
    /** Assinatura binária de cada tipo aceito. WebP = "RIFF....WEBP", conferido à parte. */
    private const MAGIC = [
        'image/png' => "\x89PNG\r\n\x1a\n",
        'image/jpeg' => "\xFF\xD8\xFF",
        'application/pdf' => '%PDF-',
        'image/webp' => 'RIFF',
    ];

    public function handle(Media $media): Media
    {
        if ($media->status === MediaStatus::Uploaded) {
            return $media;
        }

        $disk = Storage::disk('r2');

        if (! $disk->exists($media->r2_key)) {
            throw ValidationException::withMessages(['media' => 'Arquivo ainda não foi enviado.']);
        }

        if ($disk->size($media->r2_key) > RequestUploadUrl::MAX_BYTES || ! $this->matchesMime($media)) {
            $disk->delete($media->r2_key);
            $media->update(['status' => MediaStatus::Failed]);

            throw ValidationException::withMessages(['media' => 'Arquivo inválido: maior que 2 MB ou de tipo diferente do informado.']);
        }

        $media->update(['status' => MediaStatus::Uploaded]);
        MediaUploaded::dispatch($media->tenant_id, $media->id);

        return $media;
    }

    private function matchesMime(Media $media): bool
    {
        $stream = Storage::disk('r2')->readStream($media->r2_key);
        $head = is_resource($stream) ? (string) fread($stream, 12) : '';
        if (is_resource($stream)) {
            fclose($stream);
        }

        $magic = self::MAGIC[$media->mime] ?? null;
        if ($magic === null || ! str_starts_with($head, $magic)) {
            return false;
        }

        return $media->mime !== 'image/webp' || substr($head, 8, 4) === 'WEBP';
    }
}
