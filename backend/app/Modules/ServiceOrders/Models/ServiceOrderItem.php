<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Models;

use App\Shared\Enums\ItemKind;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $service_order_id
 * @property int $position
 * @property string|null $quote_item_id
 * @property ItemKind $kind
 * @property string $description
 * @property string|null $service_type_id
 * @property string $technician_id
 * @property string $quantity
 * @property int $unit_price_cents
 * @property int $total_cents
 * @property array<string, mixed> $fields
 * @property list<array<string, mixed>> $fields_schema_snapshot
 * @property Carbon|null $done_at
 * @property string|null $done_by
 */
final class ServiceOrderItem extends Model
{
    use BelongsToTenant, HasUuids;

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'kind' => ItemKind::class,
            'position' => 'integer',
            'quantity' => 'decimal:3',
            'unit_price_cents' => 'integer',
            'total_cents' => 'integer',
            'fields' => 'array',
            'fields_schema_snapshot' => 'array',
            'done_at' => 'datetime',
        ];
    }
}
