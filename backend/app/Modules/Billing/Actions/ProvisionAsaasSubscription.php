<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Billing\Support\AsaasClient;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;

/**
 * Roda no job. Idempotente por etapa (cada id do Asaas é salvo assim que volta): cliente → assinatura → link da fatura.
 * Primeira cobrança no fim do trial (assinar cedo não come dias de teste), ou hoje se o trial já acabou.
 * Erro 4xx do Asaas (dado recusado) fica em `error` para o dono corrigir; erro de rede/5xx sobe para o job tentar de novo.
 */
final class ProvisionAsaasSubscription
{
    public function __construct(private readonly AsaasClient $asaas) {}

    public function handle(string $subscriptionId, string $cpfCnpj, string $ownerEmail): void
    {
        $sub = Subscription::query()->find($subscriptionId);
        if ($sub === null || $sub->status !== SubscriptionStatus::Pending || $sub->invoice_url !== null) {
            return;
        }

        $tenant = Tenant::query()->findOrFail(TenantContext::idOrFail());

        try {
            if ($sub->asaas_customer_id === null) {
                $sub->update(['asaas_customer_id' => $this->asaas->createCustomer($tenant->name, $cpfCnpj, $ownerEmail, $tenant->id)]);
            }

            if ($sub->asaas_subscription_id === null) {
                $trialEnd = $tenant->trial_ends_at;
                $firstDue = $trialEnd !== null && $trialEnd->isFuture() ? $trialEnd : Carbon::today();

                $sub->update(['asaas_subscription_id' => $this->asaas->createSubscription(
                    (string) $sub->asaas_customer_id,
                    $sub->plan->priceReais(),
                    $firstDue->toDateString(),
                    'Prumo — plano '.$sub->plan->label(),
                    $tenant->id,
                )]);
            }

            $sub->update(['invoice_url' => $this->asaas->pendingInvoiceUrl((string) $sub->asaas_subscription_id), 'error' => null]);
        } catch (RequestException $e) {
            if ($e->response->serverError()) {
                throw $e;
            }

            $message = $e->response->json('errors.0.description');
            $sub->update(['error' => is_string($message) ? mb_substr($message, 0, 500) : 'O Asaas recusou os dados. Confira o CPF/CNPJ e tente de novo.']);
        }
    }
}
