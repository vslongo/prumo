<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Events\QuoteRejected;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RejectQuote
{
    public function __construct(private readonly OpenPublicQuote $open) {}

    public function handle(Quote $quote, ?string $reason): Quote
    {
        return DB::transaction(function () use ($quote, $reason) {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            $this->open->handle($quote);

            if (! $quote->status->awaitsCustomer()) {
                throw ValidationException::withMessages(['quote' => 'Este orçamento não está aguardando aprovação.']);
            }

            $quote->update(['status' => QuoteStatus::Rejected, 'decided_at' => now(), 'rejection_reason' => $reason]);

            QuoteRejected::dispatch($quote->tenant_id, $quote->id);

            return $quote;
        });
    }
}
