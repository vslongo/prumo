<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Actions;

use App\Modules\Tenancy\Enums\TenantStatus;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;

/** Espelha o status da assinatura no tenant atual (quem decide é o Billing). */
final class ChangeTenantStatus
{
    public function handle(TenantStatus $status): void
    {
        Tenant::query()->whereKey(TenantContext::idOrFail())->update(['status' => $status->value]);
    }
}
