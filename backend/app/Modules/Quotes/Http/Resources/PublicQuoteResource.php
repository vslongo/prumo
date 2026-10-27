<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Resources;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * O que o cliente final vê no link. Sem ids internos: itens são identificados pela posição.
 *
 * @mixin Quote
 */
final class PublicQuoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'workshop' => $this->tenant?->name,
            'customer_name' => $this->customer?->name,
            'vehicle' => [
                'plate' => $this->vehicle?->plate_normalized,
                'brand' => $this->vehicle?->brand,
                'model' => $this->vehicle?->model,
                'year' => $this->vehicle?->year,
            ],
            'status' => $this->status->value,
            'version' => $this->version,
            'notes' => $this->notes,
            'total_cents' => $this->total_cents,
            'expires_at' => $this->token_expires_at?->toIso8601String(),
            'items' => $this->items->map(fn (QuoteItem $item) => [
                'position' => $item->position,
                'kind' => $item->kind->value,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price_cents' => $item->unit_price_cents,
                'total_cents' => $item->total_cents,
                'details' => collect($item->fields_schema_snapshot)
                    ->filter(fn (array $field) => isset($item->fields[$field['key']]))
                    ->map(fn (array $field) => [
                        'label' => $field['label'],
                        'value' => $item->fields[$field['key']],
                        'unit' => $field['unit'] ?? null,
                    ])->values(),
            ]),
        ];
    }
}
