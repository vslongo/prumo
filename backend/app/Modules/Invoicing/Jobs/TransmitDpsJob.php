<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Jobs;

use App\Modules\Invoicing\Actions\TransmitDps;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Events\InvoiceFailed;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/** Fila `fiscal`. Retry com backoff; esgotou as tentativas (Sefin fora do ar) → nota em `error` para tentar de novo. */
final class TransmitDpsJob implements ShouldBeUnique, ShouldQueueAfterCommit
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [30, 120, 600, 1800];

    /** Acima da soma dos timeouts HTTP (até 4 chamadas de 20 s) + abrir o .pfx; abaixo do retry_after (300). */
    public int $timeout = 150;

    public function __construct(public readonly string $tenantId, public readonly string $invoiceId)
    {
        $this->onQueue('fiscal');
    }

    /** @return list<object> */
    public function middleware(): array
    {
        // Emissão e cancelamento da mesma nota nunca rodam ao mesmo tempo.
        return [(new WithoutOverlapping('invoice:'.$this->invoiceId))->shared()->releaseAfter(30)->expireAfter(300)];
    }

    public function uniqueId(): string
    {
        return $this->invoiceId;
    }

    public function handle(TransmitDps $transmit): void
    {
        TenantContext::run($this->tenantId, fn () => $transmit->handle($this->invoiceId));
    }

    public function failed(?Throwable $e): void
    {
        TenantContext::run($this->tenantId, function () {
            $invoice = Invoice::query()->find($this->invoiceId);
            if ($invoice?->status === InvoiceStatus::Processing) {
                $invoice->transitionTo(InvoiceStatus::Error, [
                    'error_code' => 'SEFIN',
                    'error_message' => 'A Sefin não respondeu. Tente emitir de novo daqui a pouco.',
                ]);
                InvoiceFailed::dispatch($invoice->tenant_id, $invoice->id);
            }
        });
    }
}
