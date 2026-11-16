<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;

/** Orçamentos do cliente para a exportação LGPD (ExportCustomerData). A imagem da assinatura não vai junto, só os dados da aprovação. */
final class ExportCustomerQuotes
{
    /** @return array<int, array<string, mixed>> */
    public function handle(string $customerId): array
    {
        return Quote::query()->with(['items', 'approval'])->where('customer_id', $customerId)->orderBy('created_at')->get()
            ->map(fn (Quote $quote) => [
                'id' => $quote->id,
                'vehicle_id' => $quote->vehicle_id,
                'status' => $quote->status->value,
                'version' => $quote->version,
                'mileage' => $quote->mileage,
                'notes' => $quote->notes,
                'total_cents' => $quote->total_cents,
                'created_at' => $quote->created_at?->toIso8601String(),
                'sent_at' => $quote->sent_at?->toIso8601String(),
                'decided_at' => $quote->decided_at?->toIso8601String(),
                'rejection_reason' => $quote->rejection_reason,
                'items' => $quote->items->map(fn (QuoteItem $item) => $item->only(['description', 'quantity', 'unit_price_cents', 'total_cents', 'fields']))->all(),
                'approval' => $quote->approval === null ? null : [
                    'signer_name' => $quote->approval->signer_name,
                    'approved_item_ids' => $quote->approval->approved_item_ids,
                    'ip' => $quote->approval->ip,
                    'user_agent' => $quote->approval->user_agent,
                    'approved_at' => $quote->approval->approved_at->toIso8601String(),
                ],
            ])->values()->all();
    }
}
