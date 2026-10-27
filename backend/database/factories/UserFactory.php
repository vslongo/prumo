<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Technician,
            'is_active' => true,
            'password' => self::$password ??= Hash::make('password'),
        ];
    }

    public function role(UserRole $role): static
    {
        return $this->state(['role' => $role]);
    }
}
