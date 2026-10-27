<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Policies;

use App\Modules\Auth\Models\User;
use App\Modules\Vehicles\Models\Vehicle;

final class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->is_active && $vehicle->tenant_id === $user->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }
}
