<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Actions;

use App\Modules\ServiceTypes\Data\ServiceTypeData;
use App\Modules\ServiceTypes\Events\ServiceTypeUpdated;
use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Auth\Access\AuthorizationException;

/** Itens antigos não quebram: cada item guarda `fields_schema_snapshot`. */
final class UpdateServiceType
{
    public function handle(ServiceType $serviceType, ServiceTypeData $data): ServiceType
    {
        if ($serviceType->isSystemDefault()) {
            throw new AuthorizationException('Tipos de serviço padrão não podem ser alterados.');
        }

        $serviceType->update([
            'name' => $data->name,
            'fields_schema' => $data->fieldsSchema,
            'default_price_cents' => $data->defaultPriceCents,
        ]);

        ServiceTypeUpdated::dispatch((string) $serviceType->tenant_id, $serviceType->id);

        return $serviceType;
    }
}
