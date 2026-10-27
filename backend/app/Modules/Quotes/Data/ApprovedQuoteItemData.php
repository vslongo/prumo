<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

final readonly class ApprovedQuoteItemData
{
    /**
     * @param  array<string, mixed>  $fields
     * @param  list<array<string, mixed>>  $fieldsSchemaSnapshot
     */
    public function __construct(
        public string $quoteItemId,
        public string $kind,
        public string $description,
        public ?string $serviceTypeId,
        public string $technicianId,
        public string $quantity,
        public int $unitPriceCents,
        public int $totalCents,
        public array $fields,
        public array $fieldsSchemaSnapshot,
    ) {}
}
