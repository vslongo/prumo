<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Models;

use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Enums\MessageTemplate;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $customer_id
 * @property MessageTemplate $template
 * @property MessageStatus $status
 * @property string $to
 * @property list<string> $params
 * @property string $sender_phone_number_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string $idempotency_key
 * @property string|null $provider_message_id
 * @property string|null $error
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $failed_at
 */
final class Message extends Model
{
    use BelongsToTenant, HasUuids;

    protected $fillable = ['customer_id', 'template', 'status', 'to', 'params', 'sender_phone_number_id', 'subject_type', 'subject_id', 'idempotency_key'];

    protected $hidden = ['to'];

    protected function casts(): array
    {
        return [
            'template' => MessageTemplate::class,
            'status' => MessageStatus::class,
            'to' => 'encrypted',
            'params' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
