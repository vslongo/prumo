<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Events;

use App\Modules\Quotes\Data\ApprovedQuoteData;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class QuoteApproved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly ApprovedQuoteData $quote) {}
}
