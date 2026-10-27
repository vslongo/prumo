<?php

declare(strict_types=1);

use App\Shared\ValueObjects\Money;
use App\Shared\ValueObjects\Plate;

it('multiplica dinheiro por quantidade decimal arredondando meio centavo para cima', function () {
    expect((new Money(3999))->times('4.5')->cents)->toBe(17996) // 17995.5
        ->and((new Money(1000))->times('1')->cents)->toBe(1000)
        ->and((new Money(333))->times('0.333')->cents)->toBe(111) // 110.889
        ->and(Money::zero()->plus(new Money(5))->cents)->toBe(5);
});

it('recusa valor negativo e quantidade inválida', function () {
    expect(fn () => new Money(-1))->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new Money(1))->times('-1'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => (new Money(1))->times('abc'))->toThrow(InvalidArgumentException::class);
});

it('normaliza placas antiga e Mercosul', function (string $raw, string $normalized) {
    expect((new Plate($raw))->normalized)->toBe($normalized);
})->with([
    ['abc-1234', 'ABC1234'],
    ['BRA2E19', 'BRA2E19'],
    [' bra 2e19 ', 'BRA2E19'],
]);

it('recusa placa inválida', function (string $raw) {
    expect(Plate::isValid($raw))->toBeFalse();
})->with(['AB1234', 'ABCD123', '1234ABC', '']);
