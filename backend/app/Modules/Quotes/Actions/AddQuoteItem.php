<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\ServiceTypes\Actions\ValidateItemFields;
use App\Shared\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final class AddQuoteItem
{
    public function __construct(
        private readonly ValidateItemFields $validateFields,
        private readonly PrepareQuoteForEdit $prepareForEdit,
    ) {}

    public function handle(Quote $quote, QuoteItemData $data, string $errorPrefix = 'fields'): QuoteItem
    {
        $snapshot = $this->validateFields->handle($data->serviceTypeId, $data->fields, $errorPrefix);

        return DB::transaction(function () use ($quote, $data, $snapshot) {
            $this->prepareForEdit->handle($quote);

            $item = $quote->items()->create([
                'position' => (int) $quote->items()->max('position') + 1,
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

            $quote->update(['total_cents' => (int) $quote->items()->sum('total_cents')]);

            return $item;
        });
    }
}
