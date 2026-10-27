<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use Illuminate\Support\Facades\DB;

final class RemoveQuoteItem
{
    public function __construct(private readonly PrepareQuoteForEdit $prepareForEdit) {}

    public function handle(Quote $quote, QuoteItem $item): Quote
    {
        return DB::transaction(function () use ($quote, $item) {
            $this->prepareForEdit->handle($quote);
            $item->delete();
            $quote->update(['total_cents' => (int) $quote->items()->sum('total_cents')]);

            return $quote;
        });
    }
}
