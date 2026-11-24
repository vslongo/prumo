<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Jobs\TransmitDpsJob;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Support\DpsXmlBuilder;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use App\Modules\Tenancy\TenantContext;
use App\Shared\Enums\ItemKind;
use App\Shared\Support\CpfCnpj;
use App\Shared\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pede a NFS-e da OS (botão na OS entregue/fechada). Reserva o número da DPS e manda para a fila `fiscal`.
 * Nota com erro: tenta de novo com o mesmo número (a Sefin trata reenvio da mesma DPS).
 */
final class IssueServiceInvoice
{
    public function handle(string $serviceOrderId, string $requestedBy): Invoice
    {
        $order = ServiceOrder::query()->with(['items', 'customer'])->findOrFail($serviceOrderId);

        if (! in_array($order->status, [ServiceOrderStatus::Delivered, ServiceOrderStatus::Closed], true)) {
            throw ValidationException::withMessages(['service_order' => 'A nota fiscal sai com a OS entregue ou fechada.']);
        }

        $profile = FiscalProfile::query()->first()
            ?? throw ValidationException::withMessages(['fiscal_profile' => 'Preencha os dados fiscais em Configurações → Nota fiscal.']);
        if (! $profile->hasValidCertificate()) {
            throw ValidationException::withMessages(['fiscal_profile' => 'Envie um certificado digital válido em Configurações → Nota fiscal.']);
        }

        $customer = $order->customer;
        if ($customer === null || $customer->anonymized_at !== null || ! CpfCnpj::isValid((string) $customer->document)) {
            throw ValidationException::withMessages(['customer' => 'Cadastre o CPF ou CNPJ do cliente para emitir a nota.']);
        }

        $items = self::billableItems($order, $profile->include_products);
        $amount = $items->reduce(fn (Money $sum, ServiceOrderItem $i) => $sum->plus(new Money($i->total_cents)), Money::zero());
        if ($amount->cents === 0) {
            throw ValidationException::withMessages(['items' => $profile->include_products
                ? 'A OS não tem valor para faturar.'
                : 'A OS não tem serviço com valor. Peças estão fora da nota (Configurações → Nota fiscal).']);
        }

        $invoice = DB::transaction(function () use ($order, $profile, $amount, $requestedBy) {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['invoices:'.TenantContext::idOrFail()]);

            $current = Invoice::query()->where('service_order_id', $order->id)->where('status', '<>', InvoiceStatus::Canceled)->first();
            if ($current !== null) {
                if ($current->status !== InvoiceStatus::Error) {
                    throw ValidationException::withMessages(['service_order' => 'Esta OS já tem nota fiscal.']);
                }

                return $current;
            }

            // Nunca abaixo do número que a oficina informou (já usou os anteriores em outro sistema).
            $number = max((int) Invoice::query()->where('dps_series', $profile->dps_series)->max('dps_number') + 1, $profile->dps_next_number ?? 1);

            return Invoice::create([
                'service_order_id' => $order->id,
                'requested_by' => $requestedBy,
                'environment' => $profile->environment,
                'dps_series' => $profile->dps_series,
                'dps_number' => $number,
                'dps_id' => DpsXmlBuilder::dpsId($profile, $profile->dps_series, $number),
                'amount_cents' => $amount->cents,
                'include_products' => $profile->include_products,
            ])->refresh();
        });

        TransmitDpsJob::dispatch($invoice->tenant_id, $invoice->id);

        // Com fila síncrona (testes/dev) o envio já aconteceu; em produção volta `pending`.
        return $invoice->refresh();
    }

    /** @return Collection<int, ServiceOrderItem> */
    public static function billableItems(ServiceOrder $order, bool $includeProducts): Collection
    {
        return $order->items->filter(fn (ServiceOrderItem $i) => $includeProducts || $i->kind === ItemKind::Service)->values();
    }
}
