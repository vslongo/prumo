<?php

declare(strict_types=1);

namespace App\Modules\Auth\Data;

final readonly class SignUpData
{
    public function __construct(
        public string $workshopName,
        public string $name,
        public string $email,
        public string $password,
    ) {}

    /** @param array<string, mixed> $input */
    public static function fromArray(array $input): self
    {
        return new self(
            workshopName: $input['workshop_name'],
            name: $input['name'],
            email: $input['email'],
            password: $input['password'],
        );
    }
}
