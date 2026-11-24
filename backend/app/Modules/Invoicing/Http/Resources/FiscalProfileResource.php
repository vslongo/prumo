<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Http\Resources;

use App\Modules\Invoicing\Models\FiscalProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Nunca devolve senha nem arquivo do certificado: só se há um e até quando vale.
 *
 * @mixin FiscalProfile
 */
final class FiscalProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'cnpj' => $this->cnpj,
            'municipal_registration' => $this->municipal_registration,
            'city_ibge_code' => $this->city_ibge_code,
            'tax_regime' => $this->tax_regime->value,
            'national_tax_code' => $this->national_tax_code,
            'municipal_tax_code' => $this->municipal_tax_code,
            'iss_rate_bp' => $this->iss_rate_bp,
            'dps_series' => $this->dps_series,
            'dps_next_number' => $this->dps_next_number,
            'include_products' => $this->include_products,
            'environment' => $this->environment->value,
            'certificate' => $this->certificate_media_id === null ? null : [
                'expires_at' => $this->certificate_expires_at?->toIso8601String(),
                'valid' => $this->hasValidCertificate(),
            ],
        ];
    }
}
