<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;
use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Enums\MessageTemplate;
use App\Modules\Messaging\Jobs\PublishWhatsappMessageJob;
use App\Modules\Messaging\Models\Message;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Models\Quote;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Events\ServiceOrderStatusChanged;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config(['services.whatsapp.phone_number_id' => 'PRUMO-123', 'services.whatsapp.worker_secret' => 'segredo-teste']);
    Queue::fake([PublishWhatsappMessageJob::class]);
});

function sendQuoteForMessaging(): array
{
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    $id = test()->postJson('/api/quotes', [
        'customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'items' => [productItem($tech)],
    ])->assertCreated()->json('data.id');
    $link = test()->postJson("/api/quotes/{$id}/send")->assertOk()->json('link');

    return [Quote::findOrFail($id), (string) $link];
}

function workerStatus(array $payload, string $secret = 'segredo-teste'): TestResponse
{
    $body = (string) json_encode($payload);

    return test()->call('POST', '/api/internal/whatsapp/status', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_SIGNATURE' => 'sha256='.hash_hmac('sha256', $body, $secret),
    ], $body);
}

it('orçamento enviado com consentimento enfileira o WhatsApp do número do Prumo', function () {
    [$quote, $link] = sendQuoteForMessaging();

    $message = Message::sole();
    expect($message->template)->toBe(MessageTemplate::QuoteLink)
        ->and($message->status)->toBe(MessageStatus::Queued)
        ->and($message->sender_phone_number_id)->toBe('PRUMO-123')
        ->and($message->to)->toStartWith('55')
        ->and($message->params[2])->toBe($link)
        ->and($message->subject_id)->toBe($quote->id);
    // Link (token cru) e telefone não ficam legíveis no banco.
    $raw = DB::table('messages')->first(['params', 'to']);
    expect($raw->params)->not->toContain(basename($link))
        ->and($raw->to)->not->toContain($message->to);
    Queue::assertPushed(PublishWhatsappMessageJob::class, fn ($job) => $job->messageId === $message->id);
});

it('o mesmo evento repetido não manda duas mensagens', function () {
    [$quote, $link] = sendQuoteForMessaging();

    event(new QuoteSent($quote->tenant_id, $quote->id, $link));

    expect(Message::count())->toBe(1);
    Queue::assertPushed(PublishWhatsappMessageJob::class, 1);
});

it('sem consentimento ou sem número configurado, nada é enfileirado (fica o wa.me)', function () {
    $tech = actingAsRole();
    $vehicle = makeVehicle();
    Customer::query()->whereKey($vehicle->customer_id)->update(['consent_whatsapp_at' => null]);
    $id = $this->postJson('/api/quotes', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id, 'items' => [productItem($tech)]])->json('data.id');
    $this->postJson("/api/quotes/{$id}/send")->assertOk()->assertJsonPath('whatsapp_url', fn ($url) => str_starts_with((string) $url, 'https://wa.me/55'));
    expect(Message::count())->toBe(0);

    config(['services.whatsapp.phone_number_id' => null]);
    sendQuoteForMessaging();
    expect(Message::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('OS pronta avisa o cliente uma vez só', function () {
    actingAsRole();
    $vehicle = makeVehicle();
    $id = $this->postJson('/api/service-orders', ['customer_id' => $vehicle->customer_id, 'vehicle_id' => $vehicle->id])->json('data.id');

    $this->postJson("/api/service-orders/{$id}/transition", ['status' => 'in_progress'])->assertOk();
    expect(Message::count())->toBe(0);

    $this->postJson("/api/service-orders/{$id}/transition", ['status' => 'ready'])->assertOk();
    $message = Message::sole();
    expect($message->template)->toBe(MessageTemplate::ServiceOrderReady)
        ->and($message->params[1])->toContain($vehicle->plate_normalized);

    // Evento repetido (retry da fila) não manda de novo.
    event(new ServiceOrderStatusChanged($message->tenant_id, $id, ServiceOrderStatus::InProgress, ServiceOrderStatus::Ready));
    expect(Message::count())->toBe(1);
});

it('o job entrega para o worker pela fila Redis com o remetente', function () {
    sendQuoteForMessaging();
    $message = Message::sole();

    Redis::shouldReceive('rpush')->once()->withArgs(function (string $key, string $json) use ($message) {
        $payload = json_decode($json, true);

        return $key === 'whatsapp:outbound'
            && $payload['message_id'] === $message->id
            && $payload['tenant_id'] === $message->tenant_id
            && $payload['template'] === 'quote_link'
            && $payload['sender_phone_number_id'] === 'PRUMO-123';
    });

    (new PublishWhatsappMessageJob($message->tenant_id, $message->id))->handle();
});

it('callback assinado atualiza o status e só avança', function () {
    sendQuoteForMessaging();
    $message = Message::sole();
    $base = ['tenant_id' => $message->tenant_id, 'message_id' => $message->id];
    TenantContext::set(null);

    workerStatus([...$base, 'status' => 'sent', 'provider_message_id' => 'wamid.1'])->assertNoContent();
    workerStatus([...$base, 'status' => 'read'])->assertNoContent();
    workerStatus([...$base, 'status' => 'delivered'])->assertNoContent();
    workerStatus([...$base, 'status' => 'failed', 'error' => 'tarde demais'])->assertNoContent();

    TenantContext::set($message->tenant_id);
    $message->refresh();
    expect($message->status)->toBe(MessageStatus::Read)
        ->and($message->provider_message_id)->toBe('wamid.1')
        ->and($message->sent_at)->not->toBeNull()
        ->and($message->failed_at)->toBeNull();
});

it('falha registra o erro', function () {
    sendQuoteForMessaging();
    $message = Message::sole();

    workerStatus(['tenant_id' => $message->tenant_id, 'message_id' => $message->id, 'status' => 'failed', 'error' => 'Número sem WhatsApp'])->assertNoContent();

    $message->refresh();
    expect($message->status)->toBe(MessageStatus::Failed)->and($message->error)->toBe('Número sem WhatsApp');
});

it('recusa callback sem assinatura válida ou sem segredo configurado', function () {
    sendQuoteForMessaging();
    $message = Message::sole();
    $payload = ['tenant_id' => $message->tenant_id, 'message_id' => $message->id, 'status' => 'sent'];

    workerStatus($payload, 'outro-segredo')->assertUnauthorized();
    config(['services.whatsapp.worker_secret' => '']);
    workerStatus($payload, '')->assertUnauthorized();
    expect($message->refresh()->status)->toBe(MessageStatus::Queued);
});

it('status com tenant errado não acha a mensagem (404)', function () {
    sendQuoteForMessaging();
    $message = Message::sole();
    $other = inOtherTenant(fn () => TenantContext::id());

    workerStatus(['tenant_id' => $other, 'message_id' => $message->id, 'status' => 'sent'])->assertNotFound();
    expect($message->refresh()->status)->toBe(MessageStatus::Queued);
});

it('transições de status', function (MessageStatus $from, MessageStatus $to, bool $allowed) {
    expect($from->canMoveTo($to))->toBe($allowed);
})->with([
    [MessageStatus::Queued, MessageStatus::Sent, true],
    [MessageStatus::Queued, MessageStatus::Delivered, true],
    [MessageStatus::Sent, MessageStatus::Delivered, true],
    [MessageStatus::Delivered, MessageStatus::Read, true],
    [MessageStatus::Queued, MessageStatus::Failed, true],
    [MessageStatus::Sent, MessageStatus::Failed, true],
    [MessageStatus::Read, MessageStatus::Delivered, false],
    [MessageStatus::Delivered, MessageStatus::Failed, false],
    [MessageStatus::Failed, MessageStatus::Sent, false],
    [MessageStatus::Sent, MessageStatus::Sent, false],
]);
