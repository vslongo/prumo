<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Models;

use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $service_order_id
 * @property string $requested_by
 * @property InvoiceStatus $status
 * @property FiscalEnvironment $environment
 * @property string $dps_series
 * @property int $dps_number
 * @property string $dps_id
 * @property string|null $access_key
 * @property string|null $nfse_number
 * @property int $amount_cents
 * @property bool $include_products
 * @property string|null $xml_key
 * @property string|null $pdf_key
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon|null $submitted_at
 * @property Carbon|null $authorized_at
 * @property Carbon|null $canceled_at
 * @property string|null $cancel_reason
 * @property Carbon $created_at
 */
final class Invoice extends Model
{
    use BelongsToTenant, HasUuids;

    protected $guarded = ['id', 'tenant_id', 'status'];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'environment' => FiscalEnvironment::class,
            'dps_number' => 'integer',
            'amount_cents' => 'integer',
            'include_products' => 'boolean',
            'submitted_at' => 'datetime',
            'authorized_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * Única porta para mudar o estado: recusa transição inválida.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function transitionTo(InvoiceStatus $to, array $attributes = []): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw new LogicException("Nota fiscal: transição inválida {$this->status->value} → {$to->value}.");
        }

        $this->forceFill(['status' => $to, ...$attributes])->save();
    }
}
