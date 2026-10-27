<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Actions;

use App\Modules\Vehicles\Data\VehicleData;
use App\Modules\Vehicles\Events\VehicleCreated;
use App\Modules\Vehicles\Models\Vehicle;
use App\Shared\ValueObjects\Plate;
use Illuminate\Validation\ValidationException;

final class CreateVehicle
{
    public function __construct(private readonly FindVehicleByPlate $findByPlate) {}

    public function handle(VehicleData $data): Vehicle
    {
        $plate = new Plate($data->plate);

        if ($this->findByPlate->handle($plate->normalized) !== null) {
            throw ValidationException::withMessages(['plate' => 'Já existe um veículo com essa placa nesta oficina.']);
        }

        $vehicle = Vehicle::create([
            'customer_id' => $data->customerId,
            'plate_normalized' => $plate->normalized,
            'vin' => $data->vin !== null ? strtoupper($data->vin) : null,
            'brand' => $data->brand,
            'model' => $data->model,
            'year' => $data->year,
        ]);

        VehicleCreated::dispatch($vehicle->tenant_id, $vehicle->id);

        return $vehicle;
    }
}
