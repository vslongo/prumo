<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Data;

final readonly class ServiceTypeData
{
    /** @param list<array<string, mixed>> $fieldsSchema */
    public function __construct(
        public string $name,
        public array $fieldsSchema,
        public int $defaultPriceCents,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            name: $input['name'],
            fieldsSchema: array_values($input['fields_schema'] ?? []),
            defaultPriceCents: (int) ($input['default_price_cents'] ?? 0),
        );
    }
}
