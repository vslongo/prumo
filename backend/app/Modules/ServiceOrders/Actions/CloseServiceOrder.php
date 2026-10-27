<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Events\ServiceOrderClosed;
use App\Modules\ServiceOrders\Events\ServiceOrderStatusChanged;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Fecha OS entregue. Exige: ao menos um item, todos com preço e técnico (garantidos pelo schema),
 * todos marcados como feitos e km de saída ≥ km de entrada. Pagamento entra com o módulo Payments.
 */
final class CloseServiceOrder
{
    public function handle(ServiceOrder $order, int $mileageOut): ServiceOrder
    {
        return DB::transaction(function () use ($order, $mileageOut) {
            $order = ServiceOrder::query()->lockForUpdate()->findOrFail($order->id);

            if (! $order->status->canTransitionTo(ServiceOrderStatus::Closed)) {
                throw ValidationException::withMessages(['status' => 'Só é possível fechar uma OS entregue.']);
            }

            $items = $order->items()->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['items' => 'A OS precisa ter ao menos um item.']);
            }
            if ($items->contains(fn ($item) => $item->done_at === null)) {
                throw ValidationException::withMessages(['items' => 'Todos os itens precisam estar marcados como feitos.']);
            }
            if ($order->mileage_in !== null && $mileageOut < $order->mileage_in) {
                throw ValidationException::withMessages(['mileage_out' => 'O km de saída não pode ser menor que o de entrada.']);
            }

            $order->update([
                'status' => ServiceOrderStatus::Closed,
                'mileage_out' => $mileageOut,
                'closed_at' => now(),
            ]);

            ServiceOrderStatusChanged::dispatch($order->tenant_id, $order->id, ServiceOrderStatus::Delivered, ServiceOrderStatus::Closed);
            ServiceOrderClosed::dispatch($order->tenant_id, $order->id);

            return $order;
        });
    }
}
