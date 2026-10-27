<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Resources;

use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceOrder */
final class ServiceOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'origin' => $this->origin->value,
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'customer' => $this->whenLoaded('customer', fn () => ['id' => $this->customer?->id, 'name' => $this->customer?->name]),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle?->id,
                'plate' => $this->vehicle?->plate_normalized,
                'brand' => $this->vehicle?->brand,
                'model' => $this->vehicle?->model,
            ]),
            'opened_by' => $this->opened_by,
            'status' => $this->status->value,
            'mileage_in' => $this->mileage_in,
            'mileage_out' => $this->mileage_out,
            'notes' => $this->notes,
            'total_cents' => $this->total_cents,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn (ServiceOrderItem $item) => [
                'id' => $item->id,
                'position' => $item->position,
                'quote_item_id' => $item->quote_item_id,
                'kind' => $item->kind->value,
                'description' => $item->description,
                'service_type_id' => $item->service_type_id,
                'technician_id' => $item->technician_id,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'total_cents' => $item->total_cents,
                'fields' => (object) $item->fields,
                'fields_schema_snapshot' => $item->fields_schema_snapshot,
                'done_at' => $item->done_at?->toIso8601String(),
                'done_by' => $item->done_by,
            ])),
        ];
    }
}
