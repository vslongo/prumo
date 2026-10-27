<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Data\OpenServiceOrderData;
use App\Modules\ServiceOrders\Enums\ServiceOrderOrigin;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Events\ServiceOrderOpened;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Vehicles\Actions\VehicleBelongsToCustomer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** OS direta, sem orçamento. */
final class OpenServiceOrder
{
    public function __construct(
        private readonly NextServiceOrderNumber $nextNumber,
        private readonly AddServiceOrderItem $addItem,
        private readonly VehicleBelongsToCustomer $vehicleBelongsToCustomer,
    ) {}

    public function handle(OpenServiceOrderData $data): ServiceOrder
    {
        if (! $this->vehicleBelongsToCustomer->handle($data->vehicleId, $data->customerId)) {
            throw ValidationException::withMessages(['vehicle_id' => 'O veículo não pertence a esse cliente.']);
        }

        $order = DB::transaction(function () use ($data) {
            $order = ServiceOrder::create([
                'number' => $this->nextNumber->handle(),
                'origin' => ServiceOrderOrigin::Direct,
                'customer_id' => $data->customerId,
                'vehicle_id' => $data->vehicleId,
                'opened_by' => $data->openedBy,
                'status' => ServiceOrderStatus::Open,
                'mileage_in' => $data->mileageIn,
                'notes' => $data->notes,
            ]);

            foreach ($data->items as $i => $item) {
                $this->addItem->handle($order, $item, "items.{$i}.fields");
            }

            return $order->refresh();
        });

        ServiceOrderOpened::dispatch($order->tenant_id, $order->id);

        return $order;
    }
}
