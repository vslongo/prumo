<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Events;

use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Messaging ouve (ready → aviso de OS pronta); Audit registra quando existir. */
final class ServiceOrderStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $serviceOrderId,
        public readonly ServiceOrderStatus $from,
        public readonly ServiceOrderStatus $to,
    ) {}
}
