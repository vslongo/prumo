<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Customers\Models\Customer;
use App\Shared\Support\BlindIndex;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
final class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $phone = '119'.fake()->numerify('########');

        return [
            'name' => fake()->name(),
            'phone' => $phone,
            'phone_hash' => BlindIndex::hash($phone),
            'consent_whatsapp_at' => now(),
            'consent_whatsapp_source' => 'balcao',
        ];
    }
}
