<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Http\Requests;

use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Enums\TaxRegime;
use App\Shared\Support\CpfCnpj;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class UpdateFiscalProfileRequest extends FormRequest
{
    /** Autoriza antes de validar: quem não pode não recebe 422 com as regras. */
    public function authorize(): bool
    {
        return Gate::allows('manage-fiscal');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['cnpj' => CpfCnpj::normalize((string) $this->input('cnpj', ''))]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', 'size:14', function (string $attribute, mixed $value, Closure $fail) {
                if (! CpfCnpj::isValid((string) $value)) {
                    $fail('CNPJ inválido.');
                }
            }],
            'municipal_registration' => ['nullable', 'string', 'max:20'],
            'city_ibge_code' => ['required', 'digits:7'],
            'tax_regime' => ['required', Rule::enum(TaxRegime::class)],
            'national_tax_code' => ['required', 'digits:6'],
            'municipal_tax_code' => ['nullable', 'string', 'max:20'],
            // Simples ME/EPP informa a alíquota do ISS; MEI não (recolhe valor fixo no DAS).
            'iss_rate_bp' => ['nullable', 'integer', 'between:200,500', Rule::requiredIf($this->input('tax_regime') === TaxRegime::SimplesMeEpp->value)],
            'dps_series' => ['required', 'digits_between:1,5'],
            'dps_next_number' => ['nullable', 'integer', 'min:1', 'max:999999999999999'],
            'include_products' => ['required', 'boolean'],
            'environment' => ['required', Rule::enum(FiscalEnvironment::class)],
        ];
    }
}
