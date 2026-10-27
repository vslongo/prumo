<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Actions;

use App\Modules\ServiceTypes\Data\ServiceTypeData;
use App\Modules\ServiceTypes\Models\ServiceType;
use App\Modules\Tenancy\TenantContext;

/** Cria tipo de serviço da oficina atual (nunca tipo padrão do sistema). */
final class CreateServiceType
{
    public function handle(ServiceTypeData $data): ServiceType
    {
        TenantContext::idOrFail();

        return ServiceType::create([
            'name' => $data->name,
            'fields_schema' => $data->fieldsSchema,
            'default_price_cents' => $data->defaultPriceCents,
        ]);
    }
}
