<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class VehicleCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly string $tenantId, public readonly string $vehicleId) {}
}
