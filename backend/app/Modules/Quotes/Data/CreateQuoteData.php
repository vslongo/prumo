<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

final readonly class CreateQuoteData
{
    /** @param list<QuoteItemData> $items */
    public function __construct(
        public string $customerId,
        public string $vehicleId,
        public string $createdBy,
        public ?int $mileage = null,
        public ?string $notes = null,
        public array $items = [],
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input, string $createdBy): self
    {
        return new self(
            customerId: $input['customer_id'],
            vehicleId: $input['vehicle_id'],
            createdBy: $createdBy,
            mileage: isset($input['mileage']) ? (int) $input['mileage'] : null,
            notes: $input['notes'] ?? null,
            items: array_map(QuoteItemData::fromArray(...), array_values($input['items'] ?? [])),
        );
    }
}
