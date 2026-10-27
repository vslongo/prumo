<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;

/**
 * Cliente abriu o link. Passado o prazo, o link morre em qualquer status (410) e, se ainda aguardava
 * o cliente, o orçamento vira `expired`. Na primeira visita marca `viewed`.
 */
final class OpenPublicQuote
{
    public function handle(Quote $quote): Quote
    {
        if ($quote->token_expires_at === null || $quote->token_expires_at->isPast()) {
            if ($quote->status->awaitsCustomer()) {
                $quote->update(['status' => QuoteStatus::Expired]);
            }

            abort(410, 'Este link expirou. Peça um novo à oficina.');
        }

        if ($quote->status === QuoteStatus::Sent) {
            $quote->update(['status' => QuoteStatus::Viewed, 'viewed_at' => now()]);
        }

        return $quote;
    }
}
