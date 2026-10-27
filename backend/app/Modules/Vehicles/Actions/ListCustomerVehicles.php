<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Actions;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;

final class ListCustomerVehicles
{
    /** @return Collection<int, Vehicle> */
    public function handle(string $customerId): Collection
    {
        return Vehicle::query()->where('customer_id', $customerId)->orderBy('plate_normalized')->get();
    }
}
