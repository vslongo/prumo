<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

/** Regimes atendidos (ADR 0010): só Simples Nacional, ISS não retido. */
enum TaxRegime: string
{
    case Mei = 'mei';
    case SimplesMeEpp = 'simples_me_epp';
}
