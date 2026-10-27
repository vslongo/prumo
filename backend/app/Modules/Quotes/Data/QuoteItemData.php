<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

use App\Shared\Enums\ItemKind;

final readonly class QuoteItemData
{
    /** @param array<string, mixed> $fields */
    public function __construct(
        public ItemKind $kind,
        public string $description,
        public ?string $serviceTypeId,
        public string $technicianId,
        public string $quantity,
        public int $unitPriceCents,
        public array $fields = [],
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            kind: ItemKind::from($input['kind']),
            description: $input['description'],
            serviceTypeId: $input['service_type_id'] ?? null,
            technicianId: $input['technician_id'],
            quantity: (string) $input['quantity'],
            unitPriceCents: (int) $input['unit_price_cents'],
            fields: $input['fields'] ?? [],
        );
    }
}
