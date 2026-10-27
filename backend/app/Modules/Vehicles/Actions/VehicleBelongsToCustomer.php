<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Actions;

use App\Modules\Vehicles\Models\Vehicle;

/** API pública: Quotes e ServiceOrders conferem que o veículo é do cliente informado. */
final class VehicleBelongsToCustomer
{
    public function handle(string $vehicleId, string $customerId): bool
    {
        return Vehicle::query()->whereKey($vehicleId)->where('customer_id', $customerId)->exists();
    }
}
