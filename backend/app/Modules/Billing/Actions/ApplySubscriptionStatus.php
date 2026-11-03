<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\Actions\ChangeTenantStatus;
use LogicException;

/** Uso interno do módulo: muda o status (só transição válida) e espelha no tenant. */
final class ApplySubscriptionStatus
{
    public function __construct(private readonly ChangeTenantStatus $changeTenantStatus) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Subscription $sub, SubscriptionStatus $to, array $attributes = []): void
    {
        if ($sub->status !== $to && ! $sub->status->canTransitionTo($to)) {
            throw new LogicException("Assinatura não pode ir de {$sub->status->value} para {$to->value}.");
        }

        $sub->update(['status' => $to, ...$attributes]);

        if ($to->tenantStatus() !== null) {
            $this->changeTenantStatus->handle($to->tenantStatus());
        }
    }
}
