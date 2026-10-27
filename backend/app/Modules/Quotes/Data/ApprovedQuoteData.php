<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;

/** Payload público do evento QuoteApproved: tudo que a OS precisa, só com os itens aprovados. */
final readonly class ApprovedQuoteData
{
    /** @param list<ApprovedQuoteItemData> $items */
    public function __construct(
        public string $tenantId,
        public string $quoteId,
        public string $customerId,
        public string $vehicleId,
        public string $createdBy,
        public ?int $mileage,
        public ?string $notes,
        public array $items,
    ) {}

    /** @param iterable<QuoteItem> $approvedItems */
    public static function fromQuote(Quote $quote, iterable $approvedItems): self
    {
        $items = [];
        foreach ($approvedItems as $item) {
            $items[] = new ApprovedQuoteItemData(
                quoteItemId: $item->id,
                kind: $item->kind->value,
                description: $item->description,
                serviceTypeId: $item->service_type_id,
                technicianId: $item->technician_id,
                quantity: $item->quantity,
                unitPriceCents: $item->unit_price_cents,
                totalCents: $item->total_cents,
                fields: $item->fields,
                fieldsSchemaSnapshot: $item->fields_schema_snapshot,
            );
        }

        return new self($quote->tenant_id, $quote->id, $quote->customer_id, $quote->vehicle_id, $quote->created_by, $quote->mileage, $quote->notes, $items);
    }
}
