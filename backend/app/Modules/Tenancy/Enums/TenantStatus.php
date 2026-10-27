<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Enums;

enum TenantStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Canceled = 'canceled';
}
