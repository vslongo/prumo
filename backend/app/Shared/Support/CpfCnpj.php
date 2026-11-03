<?php

declare(strict_types=1);

namespace App\Shared\Support;

/**
 * CPF (11 dígitos) ou CNPJ (14 posições). Aceita o CNPJ alfanumérico (Receita, a partir de jul/2026):
 * as 12 primeiras posições podem ser 0-9 ou A-Z, os 2 dígitos verificadores são numéricos.
 */
final class CpfCnpj
{
    /** Só dígitos e letras maiúsculas, sem máscara. */
    public static function normalize(string $value): string
    {
        return (string) preg_replace('/[^0-9A-Z]/', '', strtoupper($value));
    }

    public static function isValid(string $value): bool
    {
        $v = self::normalize($value);

        return match (strlen($v)) {
            11 => self::validCpf($v),
            14 => self::validCnpj($v),
            default => false,
        };
    }

    private static function validCpf(string $v): bool
    {
        if (! ctype_digit($v) || preg_match('/^(\d)\1{10}$/', $v)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $v[$i] * ($t + 1 - $i);
            }
            if ((int) $v[$t] !== ((10 * $sum) % 11) % 10) {
                return false;
            }
        }

        return true;
    }

    private static function validCnpj(string $v): bool
    {
        if (! preg_match('/^[0-9A-Z]{12}\d{2}$/', $v) || preg_match('/^(\d)\1{13}$/', $v)) {
            return false;
        }

        foreach ([12, 13] as $length) {
            $sum = 0;
            $weight = $length - 7;
            for ($i = 0; $i < $length; $i++) {
                // Valor do caractere = código ASCII − 48 (dígito vale ele mesmo; 'A' vale 17).
                $sum += (ord($v[$i]) - 48) * $weight;
                $weight = $weight === 2 ? 9 : $weight - 1;
            }
            $digit = $sum % 11 < 2 ? 0 : 11 - $sum % 11;
            if ((int) $v[$length] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
