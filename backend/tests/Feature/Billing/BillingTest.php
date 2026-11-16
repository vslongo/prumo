<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Billing\Enums\Plan;
use App\Modules\Billing\Enums\SubscriptionStatus;
use App\Modules\Billing\Models\Subscription;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

const VALID_CPF = '529.982.247-25';

beforeEach(function () {
    config(['services.asaas.base_url' => 'https://asaas.test/v3', 'services.asaas.api_key' => 'test-key', 'services.asaas.webhook_token' => 'webhook-secret']);
});

function fakeAsaas(array $overrides = []): void
{
    Http::fake([
        'asaas.test/v3/customers' => Http::response(['id' => 'cus_1']),
        'asaas.test/v3/subscriptions' => Http::response(['id' => 'sub_1']),
        'asaas.test/v3/subscriptions/sub_1/payments*' => Http::response(['data' => [['invoiceUrl' => 'https://asaas.test/i/abc']]]),
        'asaas.test/v3/subscriptions/sub_1' => Http::response(['id' => 'sub_1']),
        ...$overrides,
    ]);
}

function activeSubscription(Plan $plan = Plan::Oficina, array $attributes = []): Subscription
{
    return Subscription::create(['plan' => $plan, 'status' => SubscriptionStatus::Active, 'asaas_subscription_id' => 'sub_1', 'paid_until' => now()->addDays(20), ...$attributes]);
}

function endTrial(): void
{
    Tenant::query()->whereKey(TenantContext::id())->update(['trial_ends_at' => now()->subMinute()]);
}

function asaasWebhook(string $event, array $payment = [], string $id = 'evt_1', string $token = 'webhook-secret')
{
    return test()->withHeaders(['asaas-access-token' => $token])->postJson('/api/webhooks/asaas', [
        'id' => $id,
        'event' => $event,
        'payment' => ['id' => 'pay_1', 'subscription' => 'sub_1', 'dueDate' => now()->toDateString(), 'invoiceUrl' => 'https://asaas.test/i/new', ...$payment],
    ]);
}

function customerPayload(): array
{
    return ['name' => 'João da Silva', 'phone' => '11999998888', 'consent_whatsapp' => false];
}

describe('acesso e somente leitura', function () {
    it('trial vigente escreve normalmente', function () {
        actingAsRole();

        $this->getJson('/api/billing/access')->assertOk()->assertJsonPath('data.state', 'trial')->assertJsonPath('data.can_write', true);
        $this->postJson('/api/customers', customerPayload())->assertCreated();
    });

    it('trial vencido sem assinatura: lê, mas não escreve (402); assinar continua liberado', function () {
        actingAsRole();
        endTrial();

        $this->getJson('/api/billing/access')->assertJsonPath('data.state', 'read_only')->assertJsonPath('data.can_write', false);
        $this->getJson('/api/customers')->assertOk();
        $this->postJson('/api/customers', customerPayload())->assertStatus(402)->assertJsonPath('code', 'read_only');

        fakeAsaas();
        $this->postJson('/api/billing/subscribe', ['plan' => 'solo', 'cpf_cnpj' => VALID_CPF])->assertAccepted();
    });

    it('todos os papéis veem a situação; só o dono gerencia a assinatura', function (UserRole $role) {
        actingAsRole($role);

        $this->getJson('/api/billing/access')->assertOk();
        $this->getJson('/api/billing')->assertForbidden();
        $this->postJson('/api/billing/subscribe', ['plan' => 'solo', 'cpf_cnpj' => VALID_CPF])->assertForbidden();
        $this->patchJson('/api/billing/plan', ['plan' => 'pro'])->assertForbidden();
        $this->postJson('/api/billing/cancel')->assertForbidden();
    })->with([UserRole::Admin, UserRole::Technician, UserRole::Front]);

    it('atrasada escreve durante a carência de 7 dias e depois fica somente leitura', function () {
        actingAsRole();
        endTrial();
        activeSubscription(attributes: ['status' => SubscriptionStatus::PastDue, 'past_due_since' => now()->subDays(6)]);

        $this->getJson('/api/billing/access')->assertJsonPath('data.state', 'past_due')->assertJsonPath('data.can_write', true);

        Subscription::query()->update(['past_due_since' => now()->subDays(8)]);
        $this->getJson('/api/billing/access')->assertJsonPath('data.state', 'read_only');
        $this->postJson('/api/customers', customerPayload())->assertStatus(402);
    });

    it('somente leitura nunca apaga dados', function () {
        actingAsRole();
        $id = $this->postJson('/api/customers', customerPayload())->json('data.id');
        endTrial();

        $this->getJson("/api/customers/{$id}")->assertOk();
    });
});

describe('assinar', function () {
    it('dono assina: cria cliente e assinatura no Asaas pela fila, primeira cobrança no fim do trial, CPF não fica no banco', function () {
        $owner = actingAsRole();
        fakeAsaas();
        $trialEnd = Tenant::query()->findOrFail(TenantContext::id())->trial_ends_at;

        $this->postJson('/api/billing/subscribe', ['plan' => 'oficina', 'cpf_cnpj' => VALID_CPF])
            ->assertAccepted()
            ->assertJsonPath('data.subscription.status', 'pending')
            ->assertJsonPath('data.subscription.invoice_url', 'https://asaas.test/i/abc')
            ->assertJsonPath('data.access.state', 'trial');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://asaas.test/v3/customers'
            && $r['cpfCnpj'] === '52998224725' && $r['email'] === $owner->email && $r->hasHeader('access_token', 'test-key'));
        Http::assertSent(fn (Request $r) => $r->url() === 'https://asaas.test/v3/subscriptions'
            && $r['billingType'] === 'UNDEFINED' && $r['value'] === 79 && $r['cycle'] === 'MONTHLY'
            && $r['nextDueDate'] === $trialEnd?->toDateString() && $r['customer'] === 'cus_1');

        $row = (array) DB::table('subscriptions')->first();
        expect(implode('|', array_map('strval', $row)))->not->toContain('52998224725')
            ->and($row['asaas_subscription_id'])->toBe('sub_1');
    });

    it('trial já vencido: primeira cobrança hoje', function () {
        actingAsRole();
        endTrial();
        fakeAsaas();

        $this->postJson('/api/billing/subscribe', ['plan' => 'solo', 'cpf_cnpj' => VALID_CPF])->assertAccepted();

        Http::assertSent(fn (Request $r) => $r->url() === 'https://asaas.test/v3/subscriptions' && $r['nextDueDate'] === now()->toDateString() && $r['value'] === 49);
    });

    it('recusa CPF/CNPJ inválido e plano desconhecido', function () {
        actingAsRole();
        Http::fake();

        $this->postJson('/api/billing/subscribe', ['plan' => 'solo', 'cpf_cnpj' => '111.111.111-11'])->assertUnprocessable()->assertJsonValidationErrors('cpf_cnpj');
        $this->postJson('/api/billing/subscribe', ['plan' => 'ouro', 'cpf_cnpj' => VALID_CPF])->assertUnprocessable()->assertJsonValidationErrors('plan');
        Http::assertNothingSent();
    });

    it('Asaas recusou os dados: guarda o erro e deixa tentar de novo', function () {
        actingAsRole();
        fakeAsaas(['asaas.test/v3/customers' => Http::sequence()
            ->push(['errors' => [['code' => 'invalid_cpfCnpj', 'description' => 'O CPF/CNPJ informado é inválido.']]], 400)
            ->push(['id' => 'cus_2'])]);

        $this->postJson('/api/billing/subscribe', ['plan' => 'solo', 'cpf_cnpj' => VALID_CPF])
            ->assertAccepted()
            ->assertJsonPath('data.subscription.error', 'O CPF/CNPJ informado é inválido.')
            ->assertJsonPath('data.subscription.provisioning', false);

        $this->postJson('/api/billing/subscribe', ['plan' => 'solo', 'cpf_cnpj' => '11.222.333/0001-81'])
            ->assertAccepted()
            ->assertJsonPath('data.subscription.error', null)
            ->assertJsonPath('data.subscription.invoice_url', 'https://asaas.test/i/abc');
    });

    it('não assina duas vezes; quem já tem assinatura troca o plano', function () {
        actingAsRole();
        activeSubscription();

        $this->postJson('/api/billing/subscribe', ['plan' => 'pro', 'cpf_cnpj' => VALID_CPF])->assertUnprocessable()->assertJsonValidationErrors('plan');
    });

    it('cancelada pode assinar de novo', function () {
        actingAsRole();
        activeSubscription(attributes: ['status' => SubscriptionStatus::Canceled, 'asaas_customer_id' => 'cus_1']);
        fakeAsaas(['asaas.test/v3/subscriptions' => Http::response(['id' => 'sub_1'])]);

        $this->postJson('/api/billing/subscribe', ['plan' => 'pro', 'cpf_cnpj' => VALID_CPF])->assertAccepted()->assertJsonPath('data.subscription.plan', 'pro');

        // Reaproveita o cliente do Asaas.
        Http::assertNotSent(fn (Request $r) => $r->url() === 'https://asaas.test/v3/customers');
    });
});

describe('webhook do Asaas', function () {
    it('recusa sem token ou com token errado', function () {
        $this->postJson('/api/webhooks/asaas', ['id' => 'evt_x', 'event' => 'PAYMENT_CONFIRMED'])->assertUnauthorized();
        asaasWebhook('PAYMENT_CONFIRMED', token: 'errado')->assertUnauthorized();
    });

    it('pagamento confirmado ativa, estende o período e marca o tenant como active', function () {
        actingAsRole();
        $sub = activeSubscription(attributes: ['status' => SubscriptionStatus::Pending, 'paid_until' => null, 'invoice_url' => 'https://asaas.test/i/abc']);
        app('auth')->forgetGuards();
        TenantContext::set(null);

        asaasWebhook('PAYMENT_CONFIRMED', ['dueDate' => '2026-11-10'])->assertOk();

        $sub = TenantContext::run($sub->tenant_id, fn () => $sub->refresh());
        expect($sub->status)->toBe(SubscriptionStatus::Active)
            ->and($sub->paid_until?->toDateString())->toBe('2026-12-10')
            ->and($sub->invoice_url)->toBeNull()
            ->and(Tenant::query()->findOrFail($sub->tenant_id)->status->value)->toBe('active');
    });

    it('evento repetido é processado uma vez só', function () {
        actingAsRole();
        $sub = activeSubscription();
        asaasWebhook('PAYMENT_OVERDUE', id: 'evt_dup')->assertOk();
        Subscription::query()->update(['status' => SubscriptionStatus::Active->value, 'past_due_since' => null]);

        asaasWebhook('PAYMENT_OVERDUE', id: 'evt_dup')->assertOk();

        expect($sub->refresh()->status)->toBe(SubscriptionStatus::Active)
            ->and(DB::table('billing_webhook_events')->where('id', 'evt_dup')->count())->toBe(1);
    });

    it('atraso marca past_due com a data do primeiro atraso e guarda o link da fatura', function () {
        actingAsRole();
        $sub = activeSubscription(attributes: ['paid_until' => now()->subDay()]);

        asaasWebhook('PAYMENT_OVERDUE', ['dueDate' => now()->subMonth()->toDateString()], 'evt_a')->assertOk();
        $first = $sub->refresh()->past_due_since;
        $this->travel(2)->days();
        asaasWebhook('PAYMENT_OVERDUE', ['dueDate' => now()->subMonth()->toDateString()], 'evt_b')->assertOk();

        expect($sub->refresh()->status)->toBe(SubscriptionStatus::PastDue)
            ->and($sub->past_due_since?->equalTo($first))->toBeTrue()
            ->and($sub->invoice_url)->toBe('https://asaas.test/i/new');
    });

    it('atraso que chega depois do pagamento da mesma fatura é ignorado', function () {
        actingAsRole();
        $sub = activeSubscription(attributes: ['paid_until' => '2026-12-10']);

        asaasWebhook('PAYMENT_OVERDUE', ['dueDate' => '2026-11-10'], 'evt_late')->assertOk();

        expect($sub->refresh()->status)->toBe(SubscriptionStatus::Active);
    });

    it('fatura nova guarda o link; assinatura removida no Asaas cancela', function () {
        actingAsRole();
        $sub = activeSubscription();

        asaasWebhook('PAYMENT_CREATED', ['invoiceUrl' => 'https://asaas.test/i/dez'], 'evt_c')->assertOk();
        expect($sub->refresh()->invoice_url)->toBe('https://asaas.test/i/dez');

        $this->withHeaders(['asaas-access-token' => 'webhook-secret'])
            ->postJson('/api/webhooks/asaas', ['id' => 'evt_d', 'event' => 'SUBSCRIPTION_DELETED', 'subscription' => ['id' => 'sub_1']])
            ->assertOk();
        expect($sub->refresh()->status)->toBe(SubscriptionStatus::Canceled);
    });

    it('evento de assinatura que não é nossa responde 200 e não mexe em nada', function () {
        actingAsRole();
        $sub = activeSubscription();

        asaasWebhook('PAYMENT_OVERDUE', ['subscription' => 'sub_desconhecida'], 'evt_e')->assertOk();

        expect($sub->refresh()->status)->toBe(SubscriptionStatus::Active);
    });
});

describe('trocar de plano e cancelar', function () {
    it('troca de plano e atualiza o valor no Asaas', function () {
        actingAsRole();
        activeSubscription(Plan::Solo);
        fakeAsaas();

        $this->patchJson('/api/billing/plan', ['plan' => 'pro'])->assertOk()->assertJsonPath('data.subscription.plan', 'pro');

        Http::assertSent(fn (Request $r) => $r->url() === 'https://asaas.test/v3/subscriptions/sub_1' && $r->method() === 'POST' && $r['value'] === 149 && $r['updatePendingPayments'] === true);
    });

    it('não desce para plano que não comporta os usuários ativos', function () {
        $owner = actingAsRole();
        User::factory()->for(Tenant::query()->findOrFail($owner->tenant_id))->role(UserRole::Technician)->create();
        activeSubscription(Plan::Oficina);
        Http::fake();

        $this->patchJson('/api/billing/plan', ['plan' => 'solo'])->assertUnprocessable()->assertJsonValidationErrors('plan');
        Http::assertNothingSent();
    });

    it('cancela: para a cobrança no Asaas e mantém o acesso até o fim do período pago', function () {
        // Viaja além do prazo do 2FA do dono: aqui ele já ativou, para o teste olhar só o somente leitura.
        actingAsRole()->forceFill(['two_factor_confirmed_at' => now()])->save();
        endTrial();
        activeSubscription(attributes: ['paid_until' => now()->addDays(10)]);
        fakeAsaas();

        $this->postJson('/api/billing/cancel')->assertOk()->assertJsonPath('data.subscription.status', 'canceled')->assertJsonPath('data.access.state', 'canceled');
        Http::assertSent(fn (Request $r) => $r->url() === 'https://asaas.test/v3/subscriptions/sub_1' && $r->method() === 'DELETE');
        $this->postJson('/api/customers', customerPayload())->assertCreated();

        $this->travel(11)->days();
        $this->getJson('/api/billing/access')->assertJsonPath('data.state', 'read_only');
        $this->postJson('/api/customers', customerPayload())->assertStatus(402);
    });

    it('não cancela o que não existe', function () {
        actingAsRole();

        $this->postJson('/api/billing/cancel')->assertUnprocessable();
    });
});

describe('limite de usuários do plano', function () {
    it('Solo: só o dono; não convida ninguém', function () {
        actingAsRole();
        activeSubscription(Plan::Solo);

        $this->postJson('/api/user-invitations', ['name' => 'Rafa', 'role' => 'technician'])
            ->assertUnprocessable()->assertJsonValidationErrors('plan');
    });

    it('Oficina: convites pendentes também ocupam lugar', function () {
        actingAsRole();
        activeSubscription(Plan::Oficina);

        foreach (range(1, 4) as $i) {
            $this->postJson('/api/user-invitations', ['name' => "Técnico {$i}", 'role' => 'technician'])->assertCreated();
        }
        $this->postJson('/api/user-invitations', ['name' => 'Sexto', 'role' => 'technician'])->assertUnprocessable();
    });

    it('não reativa acima do limite', function () {
        $owner = actingAsRole();
        $off = User::factory()->for(Tenant::query()->findOrFail($owner->tenant_id))->role(UserRole::Technician)->create(['is_active' => false]);
        activeSubscription(Plan::Solo);

        $this->postJson("/api/users/{$off->id}/reactivate")->assertUnprocessable()->assertJsonValidationErrors('plan');
    });

    it('no trial não há limite', function () {
        actingAsRole();

        foreach (range(1, 6) as $i) {
            $this->postJson('/api/user-invitations', ['name' => "Técnico {$i}", 'role' => 'technician'])->assertCreated();
        }
    });
});

it('transições da assinatura: válidas e inválidas', function (SubscriptionStatus $from, SubscriptionStatus $to, bool $valid) {
    expect($from->canTransitionTo($to))->toBe($valid);
})->with(function () {
    $valid = [
        'pending' => ['active', 'past_due', 'canceled'],
        'active' => ['past_due', 'canceled'],
        'past_due' => ['active', 'canceled'],
        'canceled' => ['pending'],
    ];
    foreach (SubscriptionStatus::cases() as $from) {
        foreach (SubscriptionStatus::cases() as $to) {
            if ($from !== $to) {
                yield "{$from->value} → {$to->value}" => [$from, $to, in_array($to->value, $valid[$from->value], true)];
            }
        }
    }
});
