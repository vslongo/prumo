<?php

declare(strict_types=1);

namespace App\Shared\Support;

final class BrazilPhone
{
    /** Só dígitos, com DDI 55 quando vier só DDD + número (até 11 dígitos). Null se vazio. */
    public static function e164Digits(?string $phone): ?string
    {
        $digits = BlindIndex::digits($phone);
        if ($digits === null) {
            return null;
        }

        return strlen($digits) <= 11 ? '55'.$digits : $digits;
    }
}
