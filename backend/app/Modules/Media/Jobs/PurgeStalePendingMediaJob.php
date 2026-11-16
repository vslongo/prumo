<?php

declare(strict_types=1);

namespace App\Modules\Media\Jobs;

use App\Modules\Media\Actions\PurgeStalePendingMedia;
use App\Modules\Tenancy\Actions\ForEachTenant;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Diário (routes/console.php). Roda tenant a tenant, sem tirar o escopo de tenant. */
final class PurgeStalePendingMediaJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function handle(ForEachTenant $forEachTenant, PurgeStalePendingMedia $purge): void
    {
        $forEachTenant->handle(fn () => $purge->handle());
    }
}
