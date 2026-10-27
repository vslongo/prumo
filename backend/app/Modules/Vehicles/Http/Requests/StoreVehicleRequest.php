<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Requests;

use App\Modules\Tenancy\TenantContext;
use App\Shared\ValueObjects\Plate;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreVehicleRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'uuid', Rule::exists('customers', 'id')->where('tenant_id', TenantContext::idOrFail())->whereNull('deleted_at')],
            'plate' => ['required', 'string', 'max:10', function (string $attribute, mixed $value, Closure $fail) {
                if (! is_string($value) || ! Plate::isValid($value)) {
                    $fail('Placa inválida.');
                }
            }],
            'vin' => ['nullable', 'string', 'regex:/^[A-HJ-NPR-Za-hj-npr-z0-9]{17}$/'],
            'brand' => ['nullable', 'string', 'max:60'],
            'model' => ['nullable', 'string', 'max:80'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
        ];
    }
}
