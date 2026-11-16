<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Customers\Actions\AnonymizeCustomer;
use App\Modules\Customers\Events\CustomerAnonymized;
use App\Modules\Customers\Models\Customer;
use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Enums\MessageTemplate;
use App\Modules\Messaging\Models\Message;
use App\Modules\Quotes\Models\Quote;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

function customerWithHistory(): Customer
{
    $tech = auth()->user();
    $vehicle = makeVehicle();
    $customer = $vehicle->customer;
    $customer->update(['phone' => '11988887777', 'consent_whatsapp_at' => now(), 'consent_whatsapp_source' => 'balcao']);

    test()->postJson('/api/quotes', [
        'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'items' => [productItem($tech)],
    ])->assertCreated();
    test()->postJson('/api/service-orders', [
        'customer_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'mileage_in' => 1000, 'items' => [productItem($tech)],
    ])->assertCreated();
    Message::create([
        'customer_id' => $customer->id, 'template' => MessageTemplate::QuoteLink, 'status' => MessageStatus::Sent,
        'to' => '5511988887777', 'params' => [$customer->name, 'Oficina', 'https://x'], 'sender_phone_number_id' => 'P',
        'subject_type' => 'quote', 'subject_id' => (string) Str::uuid(), 'idempotency_key' => 'k-'.Str::uuid(),
    ]);

    return $customer->fresh();
}

it('edita o cliente e atualiza o hash de busca', function () {
    actingAsRole(UserRole::Technician);
    $customer = Customer::factory()->create();

    $this->patchJson("/api/customers/{$customer->id}", ['name' => 'Novo Nome', 'phone' => '(11) 95555-4444'])
        ->assertOk()->assertJsonPath('data.name', 'Novo Nome')->assertJsonPath('data.phone', '11955554444');

    $this->getJson('/api/customers?q=11955554444')->assertJsonPath('data.0.id', $customer->id);
});

it('registra consentimento com data e origem e revoga ao desmarcar', function () {
    actingAsRole();
    $customer = Customer::factory()->create(['consent_whatsapp_at' => null]);

    $this->patchJson("/api/customers/{$customer->id}", ['consent_whatsapp' => true, 'consent_source' => 'whatsapp'])
        ->assertOk()->assertJsonPath('data.consent_whatsapp_source', 'whatsapp');
    $first = $customer->fresh()->consent_whatsapp_at;
    expect($first)->not->toBeNull();

    $this->travel(1)->days();
    $this->patchJson("/api/customers/{$customer->id}", ['consent_whatsapp' => true, 'consent_source' => 'balcao']);
    expect($customer->fresh()->consent_whatsapp_at->equalTo($first))->toBeTrue()
        ->and($customer->fresh()->consent_whatsapp_source)->toBe('whatsapp');

    $this->patchJson("/api/customers/{$customer->id}", ['consent_whatsapp' => false])
        ->assertOk()->assertJsonPath('data.consent_whatsapp_at', null)->assertJsonPath('data.consent_whatsapp_source', null);
});

it('valida a edição', function () {
    actingAsRole();
    $customer = Customer::factory()->create();

    $this->patchJson("/api/customers/{$customer->id}", ['name' => '', 'phone' => 'abc'])
        ->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone']);
});

it('exporta tudo o que a oficina guarda do cliente', function () {
    actingAsRole();
    $customer = customerWithHistory();

    $response = $this->get("/api/customers/{$customer->id}/export")->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="cliente-'.$customer->id.'.json"');

    expect($response->json('customer.phone'))->toBe('11988887777')
        ->and($response->json('customer.consent_whatsapp_source'))->toBe('balcao')
        ->and($response->json('vehicles'))->toHaveCount(1)
        ->and($response->json('quotes'))->toHaveCount(1)
        ->and($response->json('quotes.0.items'))->toHaveCount(1)
        ->and($response->json('service_orders.0.number'))->toBe(1)
        ->and($response->json('whatsapp_messages.0.to'))->toBe('5511988887777');
});

it('anonimiza: apaga dados pessoais, mantém orçamento, OS e veículo e limpa as mensagens', function () {
    actingAsRole(UserRole::Admin);
    $customer = customerWithHistory();

    $this->postJson("/api/customers/{$customer->id}/anonymize")->assertOk()
        ->assertJsonPath('data.name', AnonymizeCustomer::ANONYMOUS_NAME)
        ->assertJsonPath('data.phone', null)
        ->assertJsonPath('data.consent_whatsapp_at', null);

    $row = DB::table('customers')->where('id', $customer->id)->first();
    expect($row->phone)->toBeNull()->and($row->phone_hash)->toBeNull()->and($row->document_hash)->toBeNull()
        ->and($row->anonymized_at)->not->toBeNull()->and($row->deleted_at)->toBeNull();
    expect(Quote::count())->toBe(1)->and(ServiceOrder::count())->toBe(1)
        ->and(DB::table('vehicles')->where('customer_id', $customer->id)->count())->toBe(1);

    $message = Message::firstOrFail();
    expect($message->to)->toBe('')->and($message->params)->toBe([])->and($message->status)->toBe(MessageStatus::Sent);

    $this->getJson('/api/customers?q=11988887777')->assertJsonCount(0, 'data');
});

it('anonimizar é irreversível e idempotente', function () {
    actingAsRole();
    $customer = Customer::factory()->create();
    Event::fake([CustomerAnonymized::class]);

    $this->postJson("/api/customers/{$customer->id}/anonymize")->assertOk();
    $this->postJson("/api/customers/{$customer->id}/anonymize")->assertOk();
    Event::assertDispatchedTimes(CustomerAnonymized::class, 1);

    $this->patchJson("/api/customers/{$customer->id}", ['name' => 'Voltou'])
        ->assertUnprocessable()->assertJsonValidationErrors('customer');
});

it('técnico e balcão não exportam nem anonimizam', function (UserRole $role) {
    actingAsRole($role);
    $customer = Customer::factory()->create();

    $this->getJson("/api/customers/{$customer->id}/export")->assertForbidden();
    $this->postJson("/api/customers/{$customer->id}/anonymize")->assertForbidden();
    expect($customer->fresh()->anonymized_at)->toBeNull();
})->with([UserRole::Technician, UserRole::Front]);
