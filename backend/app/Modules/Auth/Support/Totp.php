<?php

declare(strict_types=1);

namespace App\Modules\Auth\Support;

/**
 * TOTP (RFC 6238) compatível com Google/Microsoft Authenticator: SHA1, 6 dígitos, passo de 30 s.
 * ponytail: implementação própria (~40 linhas) em vez de pacote; os vetores da RFC estão no teste.
 */
final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public const PERIOD = 30;

    /** Segredo de 160 bits em base32 (o formato que os apps leem). */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(20));
    }

    public static function code(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('J', $step), self::base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = unpack('N', substr($hash, $offset, 4));

        return str_pad((string) ((($value === false ? 0 : $value[1]) & 0x7FFFFFFF) % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    /** Passo que bate com o código (aceita ±1 passo de relógio desencontrado) ou null. */
    public static function matchingStep(string $secret, string $code, ?int $now = null): ?int
    {
        $code = preg_replace('/\D/', '', $code) ?? '';
        if (strlen($code) !== 6) {
            return null;
        }
        $current = intdiv($now ?? time(), self::PERIOD);
        foreach ([$current, $current - 1, $current + 1] as $step) {
            if (hash_equals(self::code($secret, $step), $code)) {
                return $step;
            }
        }

        return null;
    }

    public static function otpauthUrl(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'.rawurlencode($issuer.':'.$account).'?'.http_build_query(['secret' => $secret, 'issuer' => $issuer, 'digits' => 6, 'period' => self::PERIOD]);
    }

    public static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $chunk) => self::ALPHABET[(int) bindec(str_pad($chunk, 5, '0'))], str_split($bits, 5)));
    }

    public static function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($secret, '='))) as $char) {
            $bits .= str_pad(decbin((int) strpos(self::ALPHABET, $char)), 5, '0', STR_PAD_LEFT);
        }

        return implode('', array_map(fn (string $byte) => chr((int) bindec($byte)), array_filter(str_split($bits, 8), fn (string $b) => strlen($b) === 8)));
    }
}
