<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

/** Placa brasileira, padrão antigo (ABC1234) ou Mercosul (ABC1D23). */
final readonly class Plate
{
    public string $normalized;

    public function __construct(string $raw)
    {
        $normalized = self::normalize($raw);

        if (preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $normalized) !== 1) {
            throw new InvalidArgumentException('Placa inválida.');
        }

        $this->normalized = $normalized;
    }

    public static function normalize(string $raw): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));
    }

    public static function isValid(string $raw): bool
    {
        try {
            new self($raw);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
