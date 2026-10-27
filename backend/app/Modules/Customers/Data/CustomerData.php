<?php

declare(strict_types=1);

namespace App\Modules\Customers\Data;

final readonly class CustomerData
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $document = null,
        public bool $consentWhatsapp = false,
        public ?string $consentSource = null,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            name: $input['name'],
            email: $input['email'] ?? null,
            phone: $input['phone'] ?? null,
            document: $input['document'] ?? null,
            consentWhatsapp: (bool) ($input['consent_whatsapp'] ?? false),
            consentSource: $input['consent_source'] ?? null,
        );
    }
}
