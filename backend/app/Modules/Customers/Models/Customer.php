<?php

declare(strict_types=1);

namespace App\Modules\Customers\Models;

use App\Shared\Traits\BelongsToTenant;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $document
 * @property Carbon|null $consent_whatsapp_at
 * @property string|null $consent_whatsapp_source
 * @property Carbon|null $anonymized_at
 */
#[UseFactory(CustomerFactory::class)]
final class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use BelongsToTenant, HasFactory, HasUuids, SoftDeletes;

    protected $guarded = ['id', 'tenant_id'];

    protected $hidden = ['phone_hash', 'document_hash'];

    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
            'document' => 'encrypted',
            'consent_whatsapp_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }
}
