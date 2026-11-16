<?php

declare(strict_types=1);

namespace App\Modules\Billing\Jobs;

use App\Modules\Billing\Actions\ProvisionAsaasSubscription;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;

/** Cria cliente e assinatura no Asaas. Criptografado: leva o CPF/CNPJ. */
final class ProvisionAsaasSubscriptionJob implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly string $tenantId,
        public readonly string $subscriptionId,
        public readonly string $cpfCnpj,
        public readonly string $ownerEmail,
    ) {}

    public function uniqueId(): string
    {
        return $this->subscriptionId;
    }

    public function handle(ProvisionAsaasSubscription $provision): void
    {
        TenantContext::run($this->tenantId, fn () => $provision->handle($this->subscriptionId, $this->cpfCnpj, $this->ownerEmail));
    }
}
