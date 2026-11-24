<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Enums\CancelReason;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Jobs\TransmitCancellationJob;
use App\Modules\Invoicing\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Pede o cancelamento da NFS-e autorizada. O evento vai à Sefin pela fila `fiscal`. */
final class CancelServiceInvoice
{
    public function handle(string $invoiceId, CancelReason $reason, string $text): Invoice
    {
        $invoice = DB::transaction(function () use ($invoiceId, $reason, $text) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceId);
            if ($invoice->status !== InvoiceStatus::Authorized) {
                throw ValidationException::withMessages(['invoice' => 'Só dá para cancelar nota autorizada.']);
            }
            $invoice->transitionTo(InvoiceStatus::CancelRequested, [
                'cancel_reason' => $reason->value.': '.$text,
                'error_code' => null,
                'error_message' => null,
            ]);

            return $invoice;
        });

        TransmitCancellationJob::dispatch($invoice->tenant_id, $invoice->id);

        return $invoice->refresh();
    }
}
