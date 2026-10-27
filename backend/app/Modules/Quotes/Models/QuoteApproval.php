<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Models;

use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Registro imutável da aprovação assinada. Nunca editar nem apagar.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $quote_id
 * @property int $quote_version
 * @property string $signer_name
 * @property string $signature_media_id
 * @property string $signature_key
 * @property list<string> $approved_item_ids
 * @property string $ip
 * @property string|null $user_agent
 * @property Carbon $approved_at
 */
final class QuoteApproval extends Model
{
    use BelongsToTenant, HasUuids;

    public $timestamps = false;

    protected $guarded = ['id', 'tenant_id'];

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('Aprovação de orçamento é imutável.'));
        self::deleting(fn () => throw new LogicException('Aprovação de orçamento é imutável.'));
    }

    protected function casts(): array
    {
        return [
            'quote_version' => 'integer',
            'approved_item_ids' => 'array',
            'approved_at' => 'datetime',
        ];
    }
}
