<?php

declare(strict_types=1);

use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\TenantContext;

it('não enxerga a assinatura de outro tenant nem é afetado pelo webhook dela', function () {
    config(['services.asaas.webhook_token' => 'webhook-secret']);
    actingAsRole();
    $mine = Subscription::create(['plan' => Plan::Solo, 'status' => SubscriptionStatus::Active, 'asaas_subscription_id' => 'sub_mine']);
    $foreign = inOtherTenant(fn () => Subscription::create(['plan' => Plan::Pro, 'status' => SubscriptionStatus::Active, 'asaas_subscription_id' => 'sub_other']));

    expect(Subscription::query()->pluck('id')->all())->toBe([$mine->id]);
    $this->getJson('/api/billing')->assertJsonPath('data.subscription.plan', 'solo');

    $this->withHeaders(['asaas-access-token' => 'webhook-secret'])->postJson('/api/webhooks/asaas', [
        'id' => 'evt_iso', 'event' => 'PAYMENT_OVERDUE', 'payment' => ['subscription' => 'sub_other', 'dueDate' => now()->toDateString()],
    ])->assertOk();

    expect($mine->refresh()->status)->toBe(SubscriptionStatus::Active)
        ->and(TenantContext::run($foreign->tenant_id, fn () => $foreign->refresh()->status))->toBe(SubscriptionStatus::PastDue);
});
