<?php

declare(strict_types=1);

namespace App\Shared\Support;

use App\Modules\Tenancy\TenantContext;
use App\Shared\Enums\ItemKind;
use Illuminate\Validation\Rule;

/** Regras de validação de item (orçamento e OS têm o mesmo formato de item). */
final class ItemRules
{
    /** @return array<string, mixed> */
    public static function for(string $prefix = ''): array
    {
        $tenantId = TenantContext::idOrFail();

        return [
            "{$prefix}kind" => ['required', Rule::enum(ItemKind::class)],
            "{$prefix}description" => ['required', 'string', 'max:255'],
            "{$prefix}service_type_id" => ['nullable', 'uuid', Rule::exists('service_types', 'id')
                ->where(fn ($q) => $q->where(fn ($q) => $q->where('tenant_id', $tenantId)->orWhereNull('tenant_id'))->whereNull('deleted_at'))],
            "{$prefix}technician_id" => ['required', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            "{$prefix}quantity" => ['required', 'numeric', 'gt:0', 'max:9999999', 'decimal:0,3'],
            "{$prefix}unit_price_cents" => ['required', 'integer', 'min:0', 'max:100000000'],
            "{$prefix}fields" => ['nullable', 'array'],
        ];
    }
}
