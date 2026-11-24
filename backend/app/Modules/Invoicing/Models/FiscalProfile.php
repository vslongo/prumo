<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Models;

use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Enums\TaxRegime;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $cnpj
 * @property string|null $municipal_registration
 * @property string $city_ibge_code
 * @property TaxRegime $tax_regime
 * @property string $national_tax_code
 * @property string|null $municipal_tax_code
 * @property int|null $iss_rate_bp
 * @property string $dps_series
 * @property int|null $dps_next_number
 * @property bool $include_products
 * @property FiscalEnvironment $environment
 * @property string|null $certificate_media_id
 * @property string|null $certificate_password
 * @property string|null $certificate_sha256
 * @property Carbon|null $certificate_expires_at
 */
final class FiscalProfile extends Model
{
    use BelongsToTenant, HasUuids;

    /** Só os campos do formulário. Certificado muda só por ConfirmFiscalCertificate/RemoveFiscalCertificate (forceFill). */
    protected $fillable = [
        'cnpj', 'municipal_registration', 'city_ibge_code', 'tax_regime', 'national_tax_code',
        'municipal_tax_code', 'iss_rate_bp', 'dps_series', 'dps_next_number', 'include_products', 'environment',
    ];

    protected $hidden = ['certificate_password', 'certificate_media_id', 'certificate_sha256'];

    public const NO_CERTIFICATE = [
        'certificate_media_id' => null,
        'certificate_password' => null,
        'certificate_sha256' => null,
        'certificate_expires_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'tax_regime' => TaxRegime::class,
            'environment' => FiscalEnvironment::class,
            'iss_rate_bp' => 'integer',
            'dps_next_number' => 'integer',
            'include_products' => 'boolean',
            'certificate_password' => 'encrypted',
            'certificate_expires_at' => 'datetime',
        ];
    }

    public function hasValidCertificate(): bool
    {
        return $this->certificate_media_id !== null && $this->certificate_expires_at?->isFuture() === true;
    }
}
