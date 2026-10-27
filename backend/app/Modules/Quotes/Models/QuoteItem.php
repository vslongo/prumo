<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Shared\Enums\ItemKind;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_id
 * @property int $position
 * @property ItemKind $kind
 * @property string $description
 * @property string|null $service_type_id
 * @property string $technician_id
 * @property string $quantity
 * @property int $unit_price_cents
 * @property int $total_cents
 * @property array<string, mixed> $fields
 * @property list<array<string, mixed>> $fields_schema_snapshot
 */
final class QuoteItem extends Model
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
        ];
    }
}
