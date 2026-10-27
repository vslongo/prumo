<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Requests;

use App\Modules\Tenancy\TenantContext;
use App\Shared\Support\ItemRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreServiceOrderRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = TenantContext::idOrFail();

        return [
            'customer_id' => ['required', 'uuid', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'vehicle_id' => ['required', 'uuid', Rule::exists('vehicles', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at')],
            'mileage_in' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['array', 'max:100'],
            ...ItemRules::for('items.*.'),
        ];
    }
}
