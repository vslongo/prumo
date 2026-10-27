<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ServiceTypeUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly string $tenantId, public readonly string $serviceTypeId) {}
}
