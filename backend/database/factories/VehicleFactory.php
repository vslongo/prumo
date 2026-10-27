<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Customers\Models\Customer;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
final class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'plate_normalized' => strtoupper(fake()->unique()->bothify('???#?##')),
            'brand' => 'Fiat',
            'model' => 'Uno',
            'year' => 2015,
        ];
    }
}
