<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Models;

use App\Shared\Traits\BelongsToTenant;
use Database\Factories\ServiceTypeFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tipo de serviço. `tenant_id` null = padrão do sistema (somente leitura para as oficinas).
 *
 * @property string $id
 * @property string|null $tenant_id
 * @property string $name
 * @property list<array<string, mixed>> $fields_schema
 * @property int $default_price_cents
 */
#[UseFactory(ServiceTypeFactory::class)]
final class ServiceType extends Model
{
    /** @use HasFactory<ServiceTypeFactory> */
    use BelongsToTenant, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id', 'tenant_id'];

    protected $attributes = ['fields_schema' => '[]'];

    public function includesGlobalRows(): bool
    {
        return true;
    }

    public function isSystemDefault(): bool
    {
        return $this->tenant_id === null;
    }

    protected function casts(): array
    {
        return [
            'fields_schema' => 'array',
            'default_price_cents' => 'integer',
        ];
    }
}
