<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Models;

use App\Modules\Customers\Models\Customer;
use App\Modules\ServiceOrders\Enums\ServiceOrderOrigin;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\Vehicles\Models\Vehicle;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Ordem de serviço: centro dos dados (cliente, veículo, itens com técnico e valores, orçamento de origem).
 *
 * @property string $id
 * @property string $tenant_id
 * @property int $number
 * @property ServiceOrderOrigin $origin
 * @property string|null $quote_id
 * @property string $customer_id
 * @property string $vehicle_id
 * @property string $opened_by
 * @property ServiceOrderStatus $status
 * @property int|null $mileage_in
 * @property int|null $mileage_out
 * @property string|null $notes
 * @property int $total_cents
 * @property Carbon|null $closed_at
 * @property Carbon|null $canceled_at
 */
final class ServiceOrder extends Model
{
    use BelongsToTenant, HasUuids, SoftDeletes;

    protected $guarded = ['id', 'tenant_id'];

    protected $attributes = ['status' => 'open', 'total_cents' => 0];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'origin' => ServiceOrderOrigin::class,
            'status' => ServiceOrderStatus::class,
            'mileage_in' => 'integer',
            'mileage_out' => 'integer',
            'total_cents' => 'integer',
            'closed_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /** @return HasMany<ServiceOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class)->orderBy('position');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
