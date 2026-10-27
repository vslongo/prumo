<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

final readonly class ApproveQuoteData
{
    /** @param list<int> $itemPositions itens aprovados pela posição exibida no link (aprovação parcial permitida) */
    public function __construct(
        public string $signerName,
        public array $itemPositions,
        public string $ip,
        public ?string $userAgent,
    ) {}
}
