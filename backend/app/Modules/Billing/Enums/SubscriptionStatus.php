<?php

declare(strict_types=1);

namespace App\Modules\Billing\Enums;

use App\Modules\Tenancy\Enums\TenantStatus;

/**
 * pending (pediu para assinar, aguarda o primeiro pagamento) → active ⇄ past_due → canceled.
 * Quem muda: Subscribe (pending), webhook do Asaas (active, past_due, canceled) e CancelSubscription (canceled).
 */
enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Pending => [self::Active, self::PastDue, self::Canceled],
            self::Active => [self::PastDue, self::Canceled],
            self::PastDue => [self::Active, self::Canceled],
            // Assinar de novo depois de cancelar volta para pending.
            self::Canceled => [self::Pending],
        }, true);
    }

    /** Status espelhado em `tenants.status`. Pendente não muda o tenant (segue no trial até pagar). */
    public function tenantStatus(): ?TenantStatus
    {
        return match ($this) {
            self::Pending => null,
            self::Active => TenantStatus::Active,
            self::PastDue => TenantStatus::PastDue,
            self::Canceled => TenantStatus::Canceled,
        };
    }
}
