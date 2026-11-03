<?php

declare(strict_types=1);

namespace App\Modules\Billing\Jobs;

use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Support\AsaasClient;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/** Leva o valor do plano atual para o Asaas. Idempotente: sempre manda o valor do plano que está no banco. */
final class SyncAsaasSubscriptionValueJob implements ShouldBeUnique, ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly string $tenantId, public readonly string $subscriptionId) {}

    public function uniqueId(): string
    {
        return $this->subscriptionId;
    }

    public function handle(AsaasClient $asaas): void
    {
        TenantContext::run($this->tenantId, function () use ($asaas) {
            $sub = Subscription::query()->find($this->subscriptionId);
            if ($sub?->asaas_subscription_id === null) {
                return;
            }

            $asaas->updateSubscriptionValue($sub->asaas_subscription_id, $sub->plan->priceReais(), 'Prumo — plano '.$sub->plan->label());
        });
    }
}
