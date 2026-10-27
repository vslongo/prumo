<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\Quotes\Data\ApprovedQuoteData;
use App\Modules\ServiceOrders\Enums\ServiceOrderOrigin;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Events\ServiceOrderOpened;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Gera a OS a partir do orçamento aprovado, já `open`, só com os itens aprovados (técnico copiado).
 * Idempotente: um orçamento gera no máximo uma OS (checagem + unique em service_orders.quote_id).
 */
final class ConvertApprovedQuoteToServiceOrder
{
    public function __construct(private readonly NextServiceOrderNumber $nextNumber) {}

    public function handle(ApprovedQuoteData $quote): ServiceOrder
    {
        if (TenantContext::id() !== $quote->tenantId) {
            throw new LogicException('Conversão precisa rodar no tenant do orçamento.');
        }

        $existing = ServiceOrder::withTrashed()->where('quote_id', $quote->quoteId)->first();
        if ($existing !== null) {
            return $existing;
        }

        $order = DB::transaction(function () use ($quote) {
            $order = ServiceOrder::create([
                'number' => $this->nextNumber->handle(),
                'origin' => ServiceOrderOrigin::FromQuote,
                'quote_id' => $quote->quoteId,
                'customer_id' => $quote->customerId,
                'vehicle_id' => $quote->vehicleId,
                'opened_by' => $quote->createdBy,
                'status' => ServiceOrderStatus::Open,
                'mileage_in' => $quote->mileage,
                'notes' => $quote->notes,
            ]);

            $total = 0;
            foreach ($quote->items as $i => $item) {
                $order->items()->create([
                    'position' => $i + 1,
                    'quote_item_id' => $item->quoteItemId,
                    'kind' => $item->kind,
                    'description' => $item->description,
                    'service_type_id' => $item->serviceTypeId,
                    'technician_id' => $item->technicianId,
                    'quantity' => $item->quantity,
                    'unit_price_cents' => $item->unitPriceCents,
                    'total_cents' => $item->totalCents,
                    'fields' => (object) $item->fields,
                    'fields_schema_snapshot' => $item->fieldsSchemaSnapshot,
                ]);
                $total += $item->totalCents;
            }

            $order->update(['total_cents' => $total]);

            return $order;
        });

        ServiceOrderOpened::dispatch($order->tenant_id, $order->id);

        return $order;
    }
}
