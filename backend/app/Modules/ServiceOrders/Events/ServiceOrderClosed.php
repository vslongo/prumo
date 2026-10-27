<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ServiceOrderClosed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly string $tenantId, public readonly string $serviceOrderId) {}
}
