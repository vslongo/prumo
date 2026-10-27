<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListQuotes
{
    /** @return LengthAwarePaginator<int, Quote> */
    public function handle(?QuoteStatus $status = null, ?string $vehicleId = null): LengthAwarePaginator
    {
        return Quote::query()
            ->with(['customer:id,name', 'vehicle:id,plate_normalized,brand,model'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->latest()
            ->paginate(20);
    }
}
