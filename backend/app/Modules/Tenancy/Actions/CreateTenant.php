<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Actions;

use App\Modules\Tenancy\Enums\TenantStatus;
use App\Modules\Tenancy\Events\TenantCreated;
use App\Modules\Tenancy\Models\Tenant;

/** Nova oficina já em trial (dias em `config/plans.php`). */
final class CreateTenant
{
    public function handle(string $name): Tenant
    {
        $tenant = new Tenant(['name' => $name]);
        $tenant->status = TenantStatus::Trialing;
        $tenant->trial_ends_at = now()->addDays((int) config('plans.trial_days'));
        $tenant->save();

        TenantCreated::dispatch($tenant->id);

        return $tenant;
    }
}
