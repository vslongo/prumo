<?php

declare(strict_types=1);

namespace App\Modules\Media\Models;

use App\Modules\Media\Enums\MediaStatus;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $owner_type
 * @property string $owner_id
 * @property string $r2_key
 * @property string|null $thumb_key
 * @property string $mime
 * @property int $size
 * @property MediaStatus $status
 * @property Carbon $created_at
 */
final class Media extends Model
{
    use BelongsToTenant, HasUuids;

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'status' => MediaStatus::class,
            'size' => 'integer',
        ];
    }
}
