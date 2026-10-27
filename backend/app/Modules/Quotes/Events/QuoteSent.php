<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class QuoteSent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly string $tenantId,
        public readonly string $quoteId,
        public readonly string $link,
    ) {}
}
