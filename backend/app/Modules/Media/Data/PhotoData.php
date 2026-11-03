<?php

declare(strict_types=1);

namespace App\Modules\Media\Data;

/** Foto pronta para exibir: URLs de leitura assinadas e de curta duração. */
final readonly class PhotoData
{
    public function __construct(
        public string $id,
        public string $ownerId,
        public string $url,
        public string $thumbUrl,
        public string $createdAt,
    ) {}
}
