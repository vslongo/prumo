<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Models;

use App\Modules\Customers\Models\Customer;
use App\Shared\Traits\BelongsToTenant;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $customer_id
 * @property string $plate_normalized
 * @property string|null $vin
 * @property string|null $brand
 * @property string|null $model
 * @property int|null $year
 */
#[UseFactory(VehicleFactory::class)]
final class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use BelongsToTenant, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id', 'tenant_id'];

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
