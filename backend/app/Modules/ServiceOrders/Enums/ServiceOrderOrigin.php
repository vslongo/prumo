<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Enums;

enum ServiceOrderOrigin: string
{
    case FromQuote = 'from_quote';
    case Direct = 'direct';
}
