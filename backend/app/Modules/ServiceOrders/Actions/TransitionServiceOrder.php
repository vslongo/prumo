<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Events\ServiceOrderStatusChanged;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Única porta para mudar status da OS. Fechar tem regras próprias: use CloseServiceOrder. */
final class TransitionServiceOrder
{
    public function handle(ServiceOrder $order, ServiceOrderStatus $to): ServiceOrder
    {
        if ($to === ServiceOrderStatus::Closed) {
            throw ValidationException::withMessages(['status' => 'Para fechar a OS use o fechamento (exige km de saída).']);
        }

        return DB::transaction(function () use ($order, $to) {
            $order = ServiceOrder::query()->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;

            if (! $from->canTransitionTo($to)) {
                throw ValidationException::withMessages(['status' => "Não é possível mudar a OS de {$from->value} para {$to->value}."]);
            }

            $order->update([
                'status' => $to,
                'canceled_at' => $to === ServiceOrderStatus::Canceled ? now() : $order->canceled_at,
            ]);

            ServiceOrderStatusChanged::dispatch($order->tenant_id, $order->id, $from, $to);

            return $order;
        });
    }
}
