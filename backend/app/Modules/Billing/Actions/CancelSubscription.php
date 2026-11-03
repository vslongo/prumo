<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Jobs\CancelAsaasSubscriptionJob;
use App\Modules\Billing\Models\Subscription;
use Illuminate\Validation\ValidationException;

/** Para de cobrar. A oficina usa normalmente até o fim do período pago; depois, somente leitura. Nada é apagado. */
final class CancelSubscription
{
    public function __construct(private readonly ApplySubscriptionStatus $applyStatus) {}

    public function handle(): Subscription
    {
        $sub = Subscription::query()->first();
        if ($sub === null || $sub->status === SubscriptionStatus::Canceled) {
            throw ValidationException::withMessages(['plan' => 'A oficina não tem assinatura ativa.']);
        }

        $asaasId = $sub->asaas_subscription_id;
        $this->applyStatus->handle($sub, SubscriptionStatus::Canceled, ['canceled_at' => now(), 'invoice_url' => null]);

        if ($asaasId !== null) {
            CancelAsaasSubscriptionJob::dispatch($asaasId);
        }

        return $sub;
    }
}
