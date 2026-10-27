<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Models;

use App\Modules\Tenancy\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property TenantStatus $status
 * @property Carbon|null $trial_ends_at
 */
#[UseFactory(TenantFactory::class)]
final class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ['name'];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
        ];
    }
}
