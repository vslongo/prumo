<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use Illuminate\Validation\ValidationException;

/** Idempotente: marcar de novo não muda quem/quando fez. */
final class MarkItemDone
{
    public function handle(ServiceOrder $order, ServiceOrderItem $item, string $doneBy): ServiceOrderItem
    {
        if ($item->service_order_id !== $order->id) {
            throw ValidationException::withMessages(['item' => 'Item não pertence a esta OS.']);
        }
        if (! $order->status->acceptsItemChanges()) {
            throw ValidationException::withMessages(['service_order' => 'Só dá para marcar itens de OS aberta ou em andamento.']);
        }

        if ($item->done_at === null) {
            $item->update(['done_at' => now(), 'done_by' => $doneBy]);
        }

        return $item;
    }
}
