<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Resources;

use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuoteItem */
final class QuoteItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'kind' => $this->kind->value,
            'description' => $this->description,
            'service_type_id' => $this->service_type_id,
            'technician_id' => $this->technician_id,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unit_price_cents,
            'total_cents' => $this->total_cents,
            'fields' => (object) $this->fields,
            'fields_schema_snapshot' => $this->fields_schema_snapshot,
        ];
    }
}
