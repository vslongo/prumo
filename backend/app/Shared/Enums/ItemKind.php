<?php

declare(strict_types=1);

namespace App\Shared\Enums;

enum ItemKind: string
{
    case Service = 'service';
    case Product = 'product';
}
