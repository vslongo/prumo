<?php

declare(strict_types=1);

namespace App\Modules\Media\Actions;

use App\Modules\Media\Enums\MediaStatus;
use App\Modules\Media\Models\Media;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Cria `media` pendente e devolve URL pré-assinada para o navegador fazer PUT direto no R2.
 * O arquivo nunca passa pelo Laravel.
 */
final class RequestUploadUrl
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public const MIMES = [
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    /** Fotos de item (orçamento/OS): só imagem. */
    public const PHOTO_MIMES = ['image/jpeg', 'image/webp', 'image/png'];

    /**
     * @param  list<string>|null  $allowedMimes  restringe além da lista padrão (ex.: assinatura só PNG)
     * @return array{media: Media, url: string, headers: array<string, mixed>, expires_at: string}
     */
    public function handle(string $ownerType, string $ownerId, string $mime, int $size, ?array $allowedMimes = null): array
    {
        $allowed = $allowedMimes ?? array_keys(self::MIMES);

        if (! isset(self::MIMES[$mime]) || ! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages(['mime' => 'Tipo de arquivo não permitido.']);
        }
        if ($size < 1 || $size > self::MAX_BYTES) {
            throw ValidationException::withMessages(['size' => 'Arquivo deve ter no máximo 2 MB.']);
        }

        // Sem tenant_id na chave: a URL pré-assinada pode ir para o cliente final (link público).
        TenantContext::idOrFail();
        $key = sprintf('%s/%s.%s', $ownerType, Str::uuid7(), self::MIMES[$mime]);

        $media = Media::create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'r2_key' => $key,
            'mime' => $mime,
            'size' => $size,
            'status' => MediaStatus::Pending,
        ]);

        $expiresAt = now()->addMinutes(5);
        $upload = Storage::disk('r2')->temporaryUploadUrl($key, $expiresAt, ['ContentType' => $mime]);

        return [
            'media' => $media,
            'url' => $upload['url'],
            'headers' => $upload['headers'],
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
