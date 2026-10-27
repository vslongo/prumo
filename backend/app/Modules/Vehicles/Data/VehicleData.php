<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Data;

final readonly class VehicleData
{
    public function __construct(
        public string $customerId,
        public string $plate,
        public ?string $vin = null,
        public ?string $brand = null,
        public ?string $model = null,
        public ?int $year = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            customerId: $input['customer_id'],
            plate: $input['plate'],
            vin: $input['vin'] ?? null,
            brand: $input['brand'] ?? null,
            model: $input['model'] ?? null,
            year: isset($input['year']) ? (int) $input['year'] : null,
        );
    }
}
