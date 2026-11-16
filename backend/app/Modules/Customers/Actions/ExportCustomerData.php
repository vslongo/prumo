<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\Customer;
use App\Modules\Messaging\Actions\ExportCustomerMessages;
use App\Modules\Quotes\Actions\ExportCustomerQuotes;
use App\Modules\ServiceOrders\Actions\ExportCustomerServiceOrders;
use App\Modules\Vehicles\Actions\ListCustomerVehicles;

/**
 * Direito de acesso do titular (LGPD art. 18): tudo o que a oficina guarda sobre o cliente, em JSON.
 * Cada módulo entrega a sua parte por Action pública.
 */
final class ExportCustomerData
{
    public function __construct(
        private readonly ListCustomerVehicles $vehicles,
        private readonly ExportCustomerQuotes $quotes,
        private readonly ExportCustomerServiceOrders $serviceOrders,
        private readonly ExportCustomerMessages $messages,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Customer $customer): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'document' => $customer->document,
                'consent_whatsapp_at' => $customer->consent_whatsapp_at?->toIso8601String(),
                'consent_whatsapp_source' => $customer->consent_whatsapp_source,
                'anonymized_at' => $customer->anonymized_at?->toIso8601String(),
                'created_at' => $customer->created_at?->toIso8601String(),
            ],
            'vehicles' => $this->vehicles->handle($customer->id)
                ->map(fn ($v) => $v->only(['id', 'plate_normalized', 'vin', 'brand', 'model', 'year']))->values()->all(),
            'quotes' => $this->quotes->handle($customer->id),
            'service_orders' => $this->serviceOrders->handle($customer->id),
            'whatsapp_messages' => $this->messages->handle($customer->id),
        ];
    }
}
