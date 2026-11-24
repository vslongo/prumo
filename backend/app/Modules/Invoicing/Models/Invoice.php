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

    /** Só o que IssueServiceInvoice grava ao reservar; o resto muda por transitionTo/forceFill. */
    protected $fillable = [
        'service_order_id', 'requested_by', 'environment', 'dps_series', 'dps_number', 'dps_id', 'amount_cents', 'include_products',
    ];

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
        $from = $this->status;
        if (! $from->canTransitionTo($to)) {
            throw new LogicException("Nota fiscal: transição inválida {$from->value} → {$to->value}.");
        }

        // Atômico: só muda se ainda estiver no estado lido (outro worker pode ter mudado antes).
        $this->forceFill(['status' => $to, ...$attributes]);
        $changed = self::query()->whereKey($this->id)->where('status', $from->value)
            ->update([...$this->getDirty(), 'updated_at' => now()]);
        if ($changed === 0) {
            $this->refresh();

            throw new LogicException("Nota fiscal: estado mudou durante {$from->value} → {$to->value}.");
        }
        $this->syncOriginal();
    }
}
