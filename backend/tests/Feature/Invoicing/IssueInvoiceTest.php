<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Invoicing\Actions\TransmitDps;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Events\InvoiceAuthorized;
use App\Modules\Invoicing\Events\InvoiceFailed;
use App\Modules\Invoicing\Jobs\TransmitDpsJob;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Support\DpsXmlBuilder;
use App\Modules\Invoicing\Support\SefinClient;
use App\Modules\ServiceOrders\Actions\OpenServiceOrder;
use App\Modules\ServiceOrders\Data\OpenServiceOrderData;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Shared\ValueObjects\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

const SEFIN = 'https://sefin.test/restrita';

const NFSE_XML = '<NFSe xmlns="http://www.sped.fazenda.gov.br/nfse"><infNFSe><nNFSe>42</nNFSe></infNFSe></NFSe>';

/** OS entregue com um serviço (R$ 80,00) e uma peça (R$ 179,96), cliente com CPF. */
function deliveredOrder(User $tech, string $status = 'delivered'): ServiceOrder
{
    $vehicle = makeVehicle();
    $vehicle->customer->update(['document' => '529.982.247-25']);
    $order = app(OpenServiceOrder::class)->handle(new OpenServiceOrderData($vehicle->customer_id, $vehicle->id, $tech->id));
    foreach ([['service', 'Troca de óleo', '1', 8000], ['product', 'Óleo 5W30 (litro)', '4.5', 3999]] as $i => [$kind, $desc, $qty, $unit]) {
        $order->items()->create([
            'position' => $i + 1, 'kind' => $kind, 'description' => $desc, 'technician_id' => $tech->id, 'quantity' => $qty,
            'unit_price_cents' => $unit, 'total_cents' => (new Money($unit))->times($qty)->cents, 'fields' => [], 'fields_schema_snapshot' => [],
        ]);
    }
    $order->forceFill(['status' => ServiceOrderStatus::from($status)])->save();

    return $order;
}

function fakeSefin(array $routes): void
{
    Http::fake(array_merge($routes, [SEFIN.'/*' => Http::response(['erros' => [['codigo' => 'X', 'descricao' => 'rota não simulada']]], 400)]));
}

function sefinAccepted(string $key = '42054072211222333000181000000000004211234567890'): array
{
    return [SEFIN.'/nfse' => Http::response(['chaveAcesso' => $key, 'nfseXmlGZipB64' => SefinClient::pack(NFSE_XML)], 201)];
}

beforeEach(function () {
    Storage::fake('r2');
    config(['services.nfse.restricted.sefin_url' => SEFIN]);
});

it('emite a NFS-e da OS entregue com serviços e peças e guarda o XML', function () {
    Event::fake([InvoiceAuthorized::class]);
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    fakeSefin(sefinAccepted());

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202);

    $invoice = Invoice::query()->sole();
    expect($invoice->status)->toBe(InvoiceStatus::Authorized)
        ->and($invoice->amount_cents)->toBe(8000 + 17996)
        ->and($invoice->dps_number)->toBe(1)
        ->and($invoice->dps_id)->toHaveLength(45)
        ->and($invoice->nfse_number)->toBe('42')
        ->and(Storage::disk('r2')->get((string) $invoice->xml_key))->toBe(NFSE_XML);
    Event::assertDispatched(InvoiceAuthorized::class);

    Http::assertSent(function (Request $r) {
        $xml = (string) SefinClient::unpack($r['dpsXmlGZipB64'] ?? null);

        return $r->url() === SEFIN.'/nfse'
            && str_contains($xml, '<vServ>259.96</vServ>')
            && str_contains($xml, '<CPF>52998224725</CPF>')
            && str_contains($xml, '<opSimpNac>3</opSimpNac>')
            && str_contains($xml, '<pAliq>3.00</pAliq>')
            && str_contains($xml, 'Óleo 5W30')
            && str_contains($xml, '<SignatureValue>');
    });

    $this->getJson("/api/service-orders/{$order->id}/invoices")->assertOk()
        ->assertJsonPath('data.0.status', 'authorized')->assertJsonPath('data.0.has_xml', true);
});

it('sem peças na nota soma só os serviços', function () {
    $owner = actingAsRole();
    fiscalReady(['include_products' => false]);
    $order = deliveredOrder($owner);
    fakeSefin(sefinAccepted());

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202);

    expect(Invoice::query()->sole()->amount_cents)->toBe(8000);
    Http::assertSent(fn (Request $r) => ! str_contains((string) SefinClient::unpack($r['dpsXmlGZipB64'] ?? null), 'Óleo 5W30'));
});

it('a assinatura da DPS confere com o certificado', function () {
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    fakeSefin(sefinAccepted());
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202);

    $sent = Http::recorded()->first()[0];
    $doc = new DOMDocument;
    $doc->loadXML((string) SefinClient::unpack($sent['dpsXmlGZipB64']));
    $inf = $doc->getElementsByTagName('infDPS')->item(0);
    $signedInfo = $doc->getElementsByTagName('SignedInfo')->item(0);
    $cert = "-----BEGIN CERTIFICATE-----\n".$doc->getElementsByTagName('X509Certificate')->item(0)->textContent."\n-----END CERTIFICATE-----";

    expect($doc->getElementsByTagName('DigestValue')->item(0)->textContent)->toBe(base64_encode(hash('sha256', $inf->C14N(), true)))
        ->and(openssl_verify($signedInfo->C14N(), base64_decode($doc->getElementsByTagName('SignatureValue')->item(0)->textContent), $cert, OPENSSL_ALGO_SHA256))->toBe(1)
        ->and($inf->getAttribute('Id'))->toBe(Invoice::query()->sole()->dps_id);
});

it('rejeição da Sefin deixa a nota em erro e nova tentativa usa o mesmo número', function () {
    Event::fake([InvoiceFailed::class, InvoiceAuthorized::class]);
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    fakeSefin([
        SEFIN.'/nfse' => Http::sequence()
            ->push(['erros' => [['Codigo' => 'E0312', 'Descricao' => 'Código de tributação inválido.']]], 400)
            ->push(['chaveAcesso' => '42054072211222333000181000000000004211234567890', 'nfseXmlGZipB64' => SefinClient::pack(NFSE_XML)], 201),
        SEFIN.'/dps/*' => Http::response([], 404),
    ]);

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202)
        ->assertJsonPath('data.status', 'error')->assertJsonPath('data.error_code', 'E0312');
    Event::assertDispatched(InvoiceFailed::class);

    // Já enviada antes: consulta a DPS; a Sefin não recebeu → reenvia com o mesmo número.
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202)->assertJsonPath('data.status', 'authorized');

    $invoice = Invoice::query()->sole();
    expect($invoice->dps_number)->toBe(1)->and($invoice->error_code)->toBeNull();
});

it('DPS já recebida pela Sefin é recuperada, não emitida de novo', function () {
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    $key = '42054072211222333000181000000000004211234567890';
    fakeSefin([
        SEFIN.'/nfse' => Http::response(['erros' => [['codigo' => SefinClient::DUPLICATE_DPS, 'descricao' => 'DPS já existe.']]], 400),
        SEFIN.'/dps/*' => Http::response(['chaveAcesso' => $key]),
        SEFIN.'/nfse/'.$key => Http::response(['nfseXmlGZipB64' => SefinClient::pack(NFSE_XML)]),
    ]);

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202)->assertJsonPath('data.status', 'authorized');
    expect(Invoice::query()->sole()->access_key)->toBe($key);
});

it('Sefin fora do ar: a fila tenta de novo e, esgotadas as tentativas, a nota vai para erro', function () {
    Queue::fake();
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    fakeSefin([SEFIN.'/nfse' => Http::response('', 503)]);
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202)->assertJsonPath('data.status', 'pending');
    $invoice = Invoice::query()->sole();
    Queue::assertPushedOn('fiscal', TransmitDpsJob::class);

    expect(fn () => app(TransmitDps::class)->handle($invoice->id))->toThrow(RuntimeException::class);
    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Processing);

    (new TransmitDpsJob($invoice->tenant_id, $invoice->id))->failed(null);
    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Error)->and($invoice->error_code)->toBe('SEFIN');
});

it('recusa emitir quando a regra não deixa', function (Closure $setup, string $field) {
    $owner = actingAsRole();
    fiscalReady();
    $order = $setup($owner);
    Http::fake();

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(Invoice::query()->count())->toBe(0);
    Http::assertNothingSent();
})->with([
    'OS ainda aberta' => [fn (User $u) => deliveredOrder($u, 'in_progress'), 'service_order'],
    'cliente sem CPF/CNPJ' => [fn (User $u) => tap(deliveredOrder($u), fn ($o) => $o->customer->update(['document' => null])), 'customer'],
    'sem peças e OS só com peças' => [function (User $u) {
        test()->putJson('/api/fiscal-profile', fiscalPayload(['include_products' => false]))->assertOk();

        return tap(deliveredOrder($u), fn ($o) => $o->items()->where('kind', 'service')->delete());
    }, 'items'],
]);

it('não emite segunda nota para a mesma OS', function () {
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    fakeSefin(sefinAccepted());

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202);
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertUnprocessable()->assertJsonValidationErrors('service_order');
});

it('exige perfil fiscal e certificado', function () {
    $owner = actingAsRole();
    $order = deliveredOrder($owner);

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertUnprocessable()->assertJsonValidationErrors('fiscal_profile');
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertUnprocessable()->assertJsonValidationErrors('fiscal_profile');
});

it('técnico não emite nota; balcão emite', function () {
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    fakeSefin(sefinAccepted());

    actingAsRole(UserRole::Technician, $owner->tenant);
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertForbidden();
    $this->getJson("/api/service-orders/{$order->id}/invoices")->assertOk();

    actingAsRole(UserRole::Front, $owner->tenant);
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202);
});

it('certificado trocado no R2 depois do aceite não é usado', function () {
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    foreach (Storage::disk('r2')->allFiles('fiscal_certificate') as $file) {
        Storage::disk('r2')->put($file, testPfx());
    }
    Http::fake();

    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertStatus(202)
        ->assertJsonPath('data.status', 'error')->assertJsonPath('data.error_code', 'CERT');
    Http::assertNothingSent();
});

it('monta o Id da DPS com 45 posições', function () {
    actingAsRole();
    fiscalReady();
    $profile = FiscalProfile::query()->sole();

    expect(DpsXmlBuilder::dpsId($profile, '1', 42))->toBe('DPS'.'4205407'.'2'.'11222333000181'.'00001'.'000000000000042');
});

it('transições de estado da nota', function (InvoiceStatus $from, InvoiceStatus $to, bool $ok) {
    expect($from->canTransitionTo($to))->toBe($ok);
})->with([
    [InvoiceStatus::Pending, InvoiceStatus::Processing, true],
    [InvoiceStatus::Pending, InvoiceStatus::Authorized, false],
    [InvoiceStatus::Processing, InvoiceStatus::Authorized, true],
    [InvoiceStatus::Processing, InvoiceStatus::Error, true],
    [InvoiceStatus::Error, InvoiceStatus::Processing, true],
    [InvoiceStatus::Error, InvoiceStatus::Authorized, false],
    [InvoiceStatus::Authorized, InvoiceStatus::CancelRequested, true],
    [InvoiceStatus::Authorized, InvoiceStatus::Canceled, false],
    [InvoiceStatus::CancelRequested, InvoiceStatus::Canceled, true],
    [InvoiceStatus::CancelRequested, InvoiceStatus::Authorized, true],
    [InvoiceStatus::Canceled, InvoiceStatus::Processing, false],
]);
