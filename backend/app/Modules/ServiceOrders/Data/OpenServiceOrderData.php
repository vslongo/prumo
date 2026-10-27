<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Data;

final readonly class OpenServiceOrderData
{
    /** @param list<ServiceOrderItemData> $items */
    public function __construct(
        public string $customerId,
        public string $vehicleId,
        public string $openedBy,
        public ?int $mileageIn = null,
        public ?string $notes = null,
        public array $items = [],
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input, string $openedBy): self
    {
        return new self(
            customerId: $input['customer_id'],
            vehicleId: $input['vehicle_id'],
            openedBy: $openedBy,
            mileageIn: isset($input['mileage_in']) ? (int) $input['mileage_in'] : null,
            notes: $input['notes'] ?? null,
            items: array_map(ServiceOrderItemData::fromArray(...), array_values($input['items'] ?? [])),
        );
    }
}
