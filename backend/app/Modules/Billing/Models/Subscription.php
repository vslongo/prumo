<?php

declare(strict_types=1);

namespace App\Modules\Billing\Models;

use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Shared\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $tenant_id
 * @property Plan $plan
 * @property SubscriptionStatus $status
 * @property string|null $asaas_customer_id
 * @property string|null $asaas_subscription_id
 * @property string|null $invoice_url
 * @property string|null $error
 * @property Carbon|null $paid_until
 * @property Carbon|null $past_due_since
 * @property Carbon|null $canceled_at
 */
final class Subscription extends Model
{
    use BelongsToTenant, HasUuids;

    protected $guarded = ['id', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'status' => SubscriptionStatus::class,
            'paid_until' => 'date',
            'past_due_since' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }
}
