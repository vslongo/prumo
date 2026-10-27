<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Actions;

use App\Modules\Vehicles\Models\Vehicle;
use App\Shared\ValueObjects\Plate;

final class FindVehicleByPlate
{
    public function handle(string $plate): ?Vehicle
    {
        return Vehicle::query()->where('plate_normalized', Plate::normalize($plate))->first();
    }
}
