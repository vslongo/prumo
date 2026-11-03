<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Auth\Actions\ListTenantUsers;
use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Models\Subscription;

/** Tela de assinatura do dono: situação, assinatura, planos e quantos usuários ativos a oficina tem. */
final class GetBillingOverview
{
    public function __construct(
        private readonly ResolveTenantAccess $access,
        private readonly ListTenantUsers $listUsers,
    ) {}

    /** @return array<string, mixed> */
    public function handle(): array
    {
        $sub = Subscription::query()->first();

        return [
            'access' => $this->access->handle()->toArray(),
            'subscription' => $sub === null ? null : [
                'plan' => $sub->plan->value,
                'status' => $sub->status->value,
                'invoice_url' => $sub->invoice_url,
                'error' => $sub->error,
                // Pedido feito, aguardando o Asaas devolver o link (o front consulta de novo).
                'provisioning' => $sub->invoice_url === null && $sub->error === null && $sub->asaas_subscription_id === null && $sub->status->value === 'pending',
                'paid_until' => $sub->paid_until?->toDateString(),
                'past_due_since' => $sub->past_due_since?->toIso8601String(),
                'canceled_at' => $sub->canceled_at?->toIso8601String(),
            ],
            'plans' => array_map(fn (Plan $plan) => [
                'key' => $plan->value,
                'name' => $plan->label(),
                'price_cents' => $plan->priceCents(),
                'max_users' => $plan->maxUsers(),
            ], Plan::cases()),
            'active_users' => $this->listUsers->handle()->where('is_active', true)->count(),
        ];
    }
}
