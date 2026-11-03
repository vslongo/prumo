<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Jobs\ProvisionAsaasSubscriptionJob;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\TenantContext;
use App\Shared\Support\CpfCnpj;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dono escolhe o plano e informa CPF/CNPJ. Grava a assinatura `pending` e manda criar no Asaas pela fila;
 * o link da fatura aparece quando o job termina. CPF/CNPJ vai só para o Asaas (job criptografado), não fica no banco.
 */
final class Subscribe
{
    public function __construct(private readonly ApplySubscriptionStatus $applyStatus) {}

    public function handle(Plan $plan, string $cpfCnpj, string $ownerEmail): Subscription
    {
        $document = CpfCnpj::normalize($cpfCnpj);
        if (! CpfCnpj::isValid($document)) {
            throw ValidationException::withMessages(['cpf_cnpj' => 'CPF ou CNPJ inválido.']);
        }

        $sub = DB::transaction(function () use ($plan) {
            $sub = Subscription::query()->lockForUpdate()->first();

            if ($sub === null) {
                return Subscription::create(['plan' => $plan, 'status' => SubscriptionStatus::Pending]);
            }

            // Pendente que falhou no Asaas (ex.: CPF recusado) pode tentar de novo; o resto troca de plano.
            $retry = $sub->status === SubscriptionStatus::Pending && $sub->asaas_subscription_id === null;
            if (! $retry && $sub->status !== SubscriptionStatus::Canceled) {
                throw ValidationException::withMessages(['plan' => 'A oficina já tem assinatura. Para mudar, troque o plano.']);
            }

            $this->applyStatus->handle($sub, SubscriptionStatus::Pending, [
                'plan' => $plan,
                'asaas_subscription_id' => null,
                'invoice_url' => null,
                'error' => null,
                'past_due_since' => null,
                'canceled_at' => null,
            ]);

            return $sub;
        });

        ProvisionAsaasSubscriptionJob::dispatch(TenantContext::idOrFail(), $sub->id, $document, $ownerEmail);

        return $sub;
    }
}
