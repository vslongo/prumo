<?php

declare(strict_types=1);

namespace App\Shared\Support;

/** Hash determinístico (HMAC) para buscar dado pessoal criptografado sem descriptografar. */
final class BlindIndex
{
    public static function digits(?string $value): ?string
    {
        $digits = (string) preg_replace('/\D/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    public static function hash(?string $value): ?string
    {
        $digits = self::digits($value);

        return $digits === null ? null : hash_hmac('sha256', $digits, (string) config('app.key'));
    }
}
