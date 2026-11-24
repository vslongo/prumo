<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Jobs;

use App\Modules\Invoicing\Actions\TransmitCancellation;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Fila `fiscal`. Esgotou as tentativas (Sefin fora do ar) → a nota volta a autorizada para pedir de novo. */
final class TransmitCancellationJob implements ShouldBeUnique, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 1800];

    public int $timeout = 120;

    public function __construct(public readonly string $tenantId, public readonly string $invoiceId)
    {
        $this->onQueue('fiscal');
    }

    public function uniqueId(): string
    {
        return 'cancel:'.$this->invoiceId;
    }

    public function handle(TransmitCancellation $transmit): void
    {
        TenantContext::run($this->tenantId, fn () => $transmit->handle($this->invoiceId));
    }

    public function failed(?Throwable $e): void
    {
        TenantContext::run($this->tenantId, function () {
            $invoice = Invoice::query()->find($this->invoiceId);
            if ($invoice?->status === InvoiceStatus::CancelRequested) {
                $invoice->transitionTo(InvoiceStatus::Authorized, [
                    'error_code' => 'SEFIN',
                    'error_message' => 'Cancelamento não enviado: a Sefin não respondeu. Tente de novo daqui a pouco.',
                ]);
            }
        });
    }
}
