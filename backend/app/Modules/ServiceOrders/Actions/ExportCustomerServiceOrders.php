<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;

/** OS do cliente para a exportação LGPD (ExportCustomerData). */
final class ExportCustomerServiceOrders
{
    /** @return array<int, array<string, mixed>> */
    public function handle(string $customerId): array
    {
        return ServiceOrder::query()->with('items')->where('customer_id', $customerId)->orderBy('number')->get()
            ->map(fn (ServiceOrder $order) => [
                'number' => $order->number,
                'vehicle_id' => $order->vehicle_id,
                'quote_id' => $order->quote_id,
                'status' => $order->status->value,
                'mileage_in' => $order->mileage_in,
                'mileage_out' => $order->mileage_out,
                'notes' => $order->notes,
                'total_cents' => $order->total_cents,
                'created_at' => $order->created_at?->toIso8601String(),
                'closed_at' => $order->closed_at?->toIso8601String(),
                'items' => $order->items->map(fn (ServiceOrderItem $item) => $item->only(['description', 'quantity', 'unit_price_cents', 'total_cents', 'fields']))->all(),
            ])->values()->all();
    }
}
