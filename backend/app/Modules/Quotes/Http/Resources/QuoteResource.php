<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Resources;

use App\Modules\Quotes\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quote */
final class QuoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'vehicle_id' => $this->vehicle_id,
            'customer' => $this->whenLoaded('customer', fn () => ['id' => $this->customer?->id, 'name' => $this->customer?->name]),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle?->id,
                'plate' => $this->vehicle?->plate_normalized,
                'brand' => $this->vehicle?->brand,
                'model' => $this->vehicle?->model,
            ]),
            'created_by' => $this->created_by,
            'status' => $this->status->value,
            'version' => $this->version,
            'mileage' => $this->mileage,
            'notes' => $this->notes,
            'total_cents' => $this->total_cents,
            'link_expires_at' => $this->token_expires_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'viewed_at' => $this->viewed_at?->toIso8601String(),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'items' => QuoteItemResource::collection($this->whenLoaded('items')),
            'approval' => $this->whenLoaded('approval', fn () => $this->approval === null ? null : [
                'signer_name' => $this->approval->signer_name,
                'approved_item_ids' => $this->approval->approved_item_ids,
                'approved_at' => $this->approval->approved_at->toIso8601String(),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
