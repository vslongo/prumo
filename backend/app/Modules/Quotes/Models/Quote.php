<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Modules\Customers\Models\Customer;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Vehicles\Models\Vehicle;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property string $vehicle_id
 * @property string $created_by
 * @property QuoteStatus $status
 * @property int $version
 * @property int|null $mileage
 * @property string|null $notes
 * @property int $total_cents
 * @property string|null $token_hash
 * @property Carbon|null $token_expires_at
 * @property Carbon|null $sent_at
 * @property Carbon|null $viewed_at
 * @property Carbon|null $decided_at
 * @property string|null $rejection_reason
 */
final class Quote extends Model
{
    use BelongsToTenant, HasUuids;

    protected $guarded = ['id', 'tenant_id'];

    protected $hidden = ['token_hash'];

    protected $attributes = ['status' => 'draft', 'version' => 1, 'total_cents' => 0];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'version' => 'integer',
            'mileage' => 'integer',
            'total_cents' => 'integer',
            'token_expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    /** @return HasMany<QuoteItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position');
    }

    /** @return HasOne<QuoteApproval, $this> */
    public function approval(): HasOne
    {
        return $this->hasOne(QuoteApproval::class);
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
