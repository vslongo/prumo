<?php

declare(strict_types=1);

namespace App\Modules\Billing\Data;

use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Enums\SubscriptionStatus;
use Illuminate\Support\Carbon;

/**
 * Situação de acesso da oficina. `state`:
 * trial | active | past_due (ainda dentro da carência) | canceled (pago até paid_until) | read_only.
 */
final readonly class TenantAccessData
{
    public function __construct(
        public string $state,
        public bool $canWrite,
        public ?Plan $plan,
        public ?SubscriptionStatus $subscriptionStatus,
        public ?Carbon $trialEndsAt,
        public ?Carbon $paidUntil,
        public ?Carbon $readOnlyFrom,
    ) {}

    /** Durante o trial não há limite; depois, o do plano assinado. */
    public function maxUsers(): ?int
    {
        return $this->state === 'trial' || $this->plan === null ? null : $this->plan->maxUsers();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'state' => $this->state,
            'can_write' => $this->canWrite,
            'plan' => $this->plan?->value,
            'subscription_status' => $this->subscriptionStatus?->value,
            'trial_ends_at' => $this->trialEndsAt?->toIso8601String(),
            'paid_until' => $this->paidUntil?->toDateString(),
            'read_only_from' => $this->readOnlyFrom?->toIso8601String(),
            'max_users' => $this->maxUsers(),
        ];
    }
}
