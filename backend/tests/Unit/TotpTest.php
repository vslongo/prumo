<?php

declare(strict_types=1);

use App\Modules\Auth\Support\Totp;

// RFC 6238, apêndice B (SHA1, segredo ASCII "12345678901234567890"); os apps usam os 6 últimos dígitos.
$rfcSecret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

it('gera os códigos dos vetores da RFC 6238', function (int $time, string $code) use ($rfcSecret) {
    expect(Totp::code($rfcSecret, intdiv($time, Totp::PERIOD)))->toBe($code);
})->with([
    [59, '287082'],
    [1111111109, '081804'],
    [1234567890, '005924'],
    [2000000000, '279037'],
]);

it('base32 ida e volta', function () {
    expect(Totp::base32Encode('12345678901234567890'))->toBe('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ')
        ->and(Totp::base32Decode(Totp::base32Encode($bytes = random_bytes(20))))->toBe($bytes)
        ->and(strlen(Totp::generateSecret()))->toBe(32);
});

it('aceita um passo de diferença no relógio e nada além', function () use ($rfcSecret) {
    $now = 1234567890;
    $step = intdiv($now, 30);

    expect(Totp::matchingStep($rfcSecret, Totp::code($rfcSecret, $step - 1), $now))->toBe($step - 1)
        ->and(Totp::matchingStep($rfcSecret, Totp::code($rfcSecret, $step + 1), $now))->toBe($step + 1)
        ->and(Totp::matchingStep($rfcSecret, Totp::code($rfcSecret, $step - 2), $now))->toBeNull()
        ->and(Totp::matchingStep($rfcSecret, '12345', $now))->toBeNull();
});
