<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

/** Ambiente da Sefin Nacional: produção restrita (homologação) ou produção. */
enum FiscalEnvironment: string
{
    case Restricted = 'restricted';
    case Production = 'production';
}
