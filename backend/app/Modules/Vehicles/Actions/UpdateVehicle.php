<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Actions;

use App\Modules\Vehicles\Models\Vehicle;
use App\Shared\ValueObjects\Plate;
use Illuminate\Validation\ValidationException;

final class UpdateVehicle
{
    public function __construct(private readonly FindVehicleByPlate $findByPlate) {}

    /** @param array<string, mixed> $input */
    public function handle(Vehicle $vehicle, array $input): Vehicle
    {
        $plate = new Plate($input['plate']);
        $existing = $this->findByPlate->handle($plate->normalized);
        if ($existing !== null && $existing->id !== $vehicle->id) {
            throw ValidationException::withMessages(['plate' => 'Já existe um veículo com essa placa nesta oficina.']);
        }

        unset($input['plate']);
        $input['plate_normalized'] = $plate->normalized;
        if (isset($input['vin'])) {
            $input['vin'] = strtoupper($input['vin']);
        }
        $vehicle->update($input);

        return $vehicle->refresh();
    }
}
