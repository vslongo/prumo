<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Data\TenantAccessData;
use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;

/**
 * Pode escrever? Calculado na hora (sem job de "vencer trial"):
 * assinatura ativa; atrasada há no máximo `past_due_grace_days`; cancelada mas paga até `paid_until`; ou trial vigente.
 * Fora disso, somente leitura. Nunca apaga dado.
 */
final class ResolveTenantAccess
{
    public function handle(): TenantAccessData
    {
        $tenant = Tenant::query()->findOrFail(TenantContext::idOrFail());
        $sub = Subscription::query()->first();

        $graceEnds = $sub?->past_due_since?->copy()->addDays((int) config('plans.past_due_grace_days'));
        $paidEnds = $sub?->paid_until?->copy()->endOfDay();

        [$state, $readOnlyFrom] = match (true) {
            $sub?->status === SubscriptionStatus::Active => ['active', null],
            $sub?->status === SubscriptionStatus::PastDue && $graceEnds?->isFuture() => ['past_due', $graceEnds],
            $sub?->status === SubscriptionStatus::Canceled && $paidEnds?->isFuture() => ['canceled', $paidEnds],
            (bool) $tenant->trial_ends_at?->isFuture() => ['trial', $tenant->trial_ends_at],
            default => ['read_only', null],
        };

        return new TenantAccessData(
            state: $state,
            canWrite: $state !== 'read_only',
            plan: $sub?->plan,
            subscriptionStatus: $sub?->status,
            trialEndsAt: $tenant->trial_ends_at,
            paidUntil: $sub?->paid_until,
            readOnlyFrom: $readOnlyFrom,
        );
    }
}
