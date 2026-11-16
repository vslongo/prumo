<?php

declare(strict_types=1);

namespace App\Modules\Billing\Actions;

use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\Actions\ResolveWebhookOwner;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Webhook do Asaas (token já validado no middleware). Idempotente pelo id do evento: registra e aplica na mesma
 * transação; se aplicar falhar, nada é registrado e o Asaas reenvia. Tenant vem da assinatura achada, nunca do corpo.
 *
 * - PAYMENT_CREATED: guarda o link da fatura nova.
 * - PAYMENT_CONFIRMED / PAYMENT_RECEIVED: ativa e estende `paid_until` (vencimento + 1 mês).
 * - PAYMENT_OVERDUE: atrasada (carência conta de `past_due_since`). Ignora atraso de fatura já coberta.
 * - SUBSCRIPTION_DELETED / SUBSCRIPTION_INACTIVATED: cancelada.
 */
final class HandleAsaasWebhook
{
    private const PAID = ['PAYMENT_CONFIRMED', 'PAYMENT_RECEIVED'];

    public function __construct(
        private readonly ResolveWebhookOwner $resolveOwner,
        private readonly ApplySubscriptionStatus $applyStatus,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(array $payload): void
    {
        $eventId = is_string($payload['id'] ?? null) ? $payload['id'] : '';
        $event = is_string($payload['event'] ?? null) ? $payload['event'] : '';
        if ($eventId === '' || $event === '') {
            return;
        }

        /** @var array<string, mixed> $payment */
        $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
        $asaasSubscriptionId = $payment['subscription'] ?? data_get($payload, 'subscription.id');
        $sub = is_string($asaasSubscriptionId) ? $this->resolveOwner->handle(Subscription::class, 'asaas_subscription_id', $asaasSubscriptionId) : null;

        try {
            DB::transaction(function () use ($eventId, $event, $payment, $sub) {
                DB::table('billing_webhook_events')->insert(['id' => $eventId, 'event' => mb_substr($event, 0, 60), 'processed_at' => now()]);

                if ($sub !== null) {
                    TenantContext::run($sub->tenant_id, fn () => $this->apply($sub, $event, $payment));
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Evento repetido: já processado.
        }
    }

    /** @param array<string, mixed> $payment */
    private function apply(Subscription $sub, string $event, array $payment): void
    {
        $sub = Subscription::query()->lockForUpdate()->findOrFail($sub->id);
        $invoiceUrl = is_string($payment['invoiceUrl'] ?? null) ? $payment['invoiceUrl'] : null;
        $coveredUntil = is_string($payment['dueDate'] ?? null) ? Carbon::parse($payment['dueDate'])->addMonthNoOverflow() : null;
        $alreadyCovered = $coveredUntil !== null && $sub->paid_until !== null && $sub->paid_until->gte($coveredUntil);

        if ($sub->status === SubscriptionStatus::Canceled) {
            return;
        }

        match (true) {
            $event === 'PAYMENT_CREATED' && $invoiceUrl !== null => $sub->update(['invoice_url' => $invoiceUrl]),

            in_array($event, self::PAID, true) => $this->applyStatus->handle($sub, SubscriptionStatus::Active, [
                'paid_until' => $alreadyCovered ? $sub->paid_until : $coveredUntil,
                'past_due_since' => null,
                'invoice_url' => null,
                'error' => null,
            ]),

            $event === 'PAYMENT_OVERDUE' && ! $alreadyCovered => $this->applyStatus->handle($sub, SubscriptionStatus::PastDue, [
                'past_due_since' => $sub->past_due_since ?? now(),
                'invoice_url' => $invoiceUrl ?? $sub->invoice_url,
            ]),

            in_array($event, ['SUBSCRIPTION_DELETED', 'SUBSCRIPTION_INACTIVATED'], true) => $this->applyStatus->handle($sub, SubscriptionStatus::Canceled, [
                'canceled_at' => now(),
                'invoice_url' => null,
            ]),

            default => null,
        };
    }
}
