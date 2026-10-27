<?php

declare(strict_types=1);

namespace App\Shared\ValueObjects;

use InvalidArgumentException;

/** Valor monetário em centavos. Nunca use ponto flutuante para dinheiro. */
final readonly class Money
{
    public function __construct(public int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Valor monetário não pode ser negativo.');
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    /** Multiplica por uma quantidade decimal ("4.5"), arredondando meio centavo para cima. */
    public function times(string $quantity): self
    {
        if (! is_numeric($quantity) || bccomp($quantity, '0', 3) < 0) {
            throw new InvalidArgumentException('Quantidade inválida.');
        }

        return new self((int) bcadd(bcmul((string) $this->cents, $quantity, 3), '0.5', 0));
    }
}
