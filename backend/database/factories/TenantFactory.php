<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Tenancy\Enums\TenantStatus;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tenant> */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => 'Oficina '.fake()->lastName(),
            'status' => TenantStatus::Trialing,
            'trial_ends_at' => now()->addDays(14),
        ];
    }
}
