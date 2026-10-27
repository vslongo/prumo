<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Data\ServiceOrderItemData;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use App\Modules\ServiceTypes\Actions\ValidateItemFields;
use App\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddServiceOrderItem
{
    public function __construct(private readonly ValidateItemFields $validateFields) {}

    public function handle(ServiceOrder $order, ServiceOrderItemData $data, string $errorPrefix = 'fields'): ServiceOrderItem
    {
        if (! $order->status->acceptsItemChanges()) {
            throw ValidationException::withMessages(['service_order' => 'Não é possível adicionar itens a uma OS pronta, entregue, fechada ou cancelada.']);
        }

        $snapshot = $this->validateFields->handle($data->serviceTypeId, $data->fields, $errorPrefix);

        return DB::transaction(function () use ($order, $data, $snapshot) {
            $item = $order->items()->create([
                'position' => (int) $order->items()->max('position') + 1,
                'kind' => $data->kind,
                'description' => $data->description,
                'service_type_id' => $data->serviceTypeId,
                'technician_id' => $data->technicianId,
                'quantity' => $data->quantity,
                'unit_price_cents' => $data->unitPriceCents,
                'total_cents' => (new Money($data->unitPriceCents))->times($data->quantity)->cents,
                'fields' => (object) $data->fields,
                'fields_schema_snapshot' => $snapshot,
            ]);

            $order->update(['total_cents' => (int) $order->items()->sum('total_cents')]);

            return $item;
        });
    }
}
