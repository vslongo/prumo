<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Listeners;

use App\Modules\Quotes\Events\QuoteApproved;
use App\Modules\ServiceOrders\Actions\ConvertApprovedQuoteToServiceOrder;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/** QuoteApproved → OS. Em fila, restaura o tenant do orçamento; a Action é idempotente. */
final class CreateServiceOrderFromApprovedQuote implements ShouldQueueAfterCommit
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(private readonly ConvertApprovedQuoteToServiceOrder $convert) {}

    public function handle(QuoteApproved $event): void
    {
        TenantContext::run($event->quote->tenantId, fn () => $this->convert->handle($event->quote));
    }
}
