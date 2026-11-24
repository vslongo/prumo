<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Listeners;

use App\Modules\Invoicing\Actions\GenerateDanfse;
use App\Modules\Invoicing\Events\InvoiceAuthorized;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/** InvoiceAuthorized → PDF da nota. Em fila, restaura o tenant; a Action é idempotente. */
final class GenerateDanfseOnAuthorized implements ShouldQueueAfterCommit
{
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(private readonly GenerateDanfse $generate) {}

    public function handle(InvoiceAuthorized $event): void
    {
        TenantContext::run($event->tenantId, fn () => $this->generate->handle($event->invoiceId));
    }
}
