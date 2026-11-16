<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

use LogicException;

/** Planos do SaaS. Preço e limite vêm de `config/plans.php`, nunca daqui. */
enum Plan: string
{
    case Solo = 'solo';
    case Oficina = 'oficina';
    case Pro = 'pro';

    public function label(): string
    {
        return (string) config("plans.plans.{$this->value}.name");
    }

    public function priceCents(): int
    {
        return (int) config("plans.plans.{$this->value}.price_cents");
    }

    /** null = sem limite. */
    public function maxUsers(): ?int
    {
        $max = config("plans.plans.{$this->value}.max_users");

        return $max === null ? null : (int) $max;
    }

    /**
     * O Asaas recebe o valor em reais (número). Para não passar por ponto flutuante, só aceitamos preço em reais inteiros.
     * ponytail: preço com centavos (ex.: 49,90) exige mandar string decimal; ajustar aqui se mudar.
     */
    public function priceReais(): int
    {
        if ($this->priceCents() % 100 !== 0) {
            throw new LogicException("Preço do plano {$this->value} precisa ser em reais inteiros.");
        }

        return intdiv($this->priceCents(), 100);
    }
}
