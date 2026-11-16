<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Auth\Actions\ListTenantUsers;
use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Jobs\SyncAsaasSubscriptionValueJob;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

/** Troca de plano. Plano menor exige caber os usuários ativos de hoje. O valor novo vale a partir da próxima fatura. */
final class ChangePlan
{
    public function __construct(private readonly ListTenantUsers $listUsers) {}

    public function handle(Plan $plan): Subscription
    {
        $sub = Subscription::query()->first();
        if ($sub === null || $sub->status === SubscriptionStatus::Canceled) {
            throw ValidationException::withMessages(['plan' => 'A oficina não tem assinatura ativa. Assine um plano primeiro.']);
        }

        $active = $this->listUsers->handle()->where('is_active', true)->count();
        $max = $plan->maxUsers();
        if ($max !== null && $active > $max) {
            throw ValidationException::withMessages(['plan' => sprintf(
                'O plano %s permite até %d %s e a oficina tem %d ativos. Desative alguém em Configurações › Equipe antes.',
                $plan->label(), $max, $max === 1 ? 'usuário' : 'usuários', $active,
            )]);
        }

        if ($sub->plan === $plan) {
            return $sub;
        }

        $sub->update(['plan' => $plan]);

        // Sem id no Asaas ainda: o job de criação lê o plano do banco e já cria com o valor novo.
        if ($sub->asaas_subscription_id !== null) {
            SyncAsaasSubscriptionValueJob::dispatch(TenantContext::idOrFail(), $sub->id);
        }

        return $sub;
    }
}
