<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use App\Modules\Invoicing\Actions\RequestCertificateUpload;
use App\Modules\Invoicing\Actions\TransmitDps;
use App\Modules\Invoicing\Actions\UpdateFiscalProfile;
use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Enums\TaxRegime;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\ServiceOrders\Actions\OpenServiceOrder;
use App\Modules\ServiceOrders\Data\OpenServiceOrderData;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('não lê, altera nem usa o certificado do perfil fiscal de outro tenant', function () {
    Storage::fake('r2');
    actingAsRole();

    [$foreign, $foreignMediaId] = inOtherTenant(function () {
        $profile = app(UpdateFiscalProfile::class)->handle([
            'cnpj' => '11444777000161', 'city_ibge_code' => '3550308', 'tax_regime' => TaxRegime::Mei,
            'national_tax_code' => '140101', 'dps_series' => '1', 'include_products' => true,
            'environment' => FiscalEnvironment::Restricted,
        ]);

        return [$profile, app(RequestCertificateUpload::class)->handle(100)['media_id']];
    });

    $this->getJson('/api/fiscal-profile')->assertOk()->assertJsonPath('data', null);

    // Sem perfil próprio, não alcança a mídia do outro tenant.
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $foreignMediaId, 'password' => 'x'])->assertNotFound();

    // Com perfil próprio, cria o seu, sem tocar no do outro.
    $this->putJson('/api/fiscal-profile', [
        'cnpj' => '11222333000181', 'city_ibge_code' => '4205407', 'tax_regime' => 'mei',
        'national_tax_code' => '140101', 'dps_series' => '1', 'include_products' => true, 'environment' => 'restricted',
    ])->assertOk();
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $foreignMediaId, 'password' => 'x'])->assertNotFound();

    $cnpj = TenantContext::run($foreign->tenant_id, fn () => FiscalProfile::query()->firstOrFail()->cnpj);
    expect($cnpj)->toBe('11444777000161');
});

it('não lê nem emite nota de OS de outro tenant', function () {
    Storage::fake('r2');
    config(['services.nfse.restricted.sefin_url' => 'https://sefin.test/restrita']);
    Http::fake();
    $owner = actingAsRole();
    fiscalReady();

    [$order, $invoiceId] = inOtherTenant(function () {
        $vehicle = makeVehicle();
        $user = User::factory()->create(['tenant_id' => TenantContext::id()]);
        $order = app(OpenServiceOrder::class)
            ->handle(new OpenServiceOrderData($vehicle->customer_id, $vehicle->id, $user->id));
        $invoice = Invoice::create([
            'service_order_id' => $order->id, 'requested_by' => $user->id, 'environment' => 'restricted', 'dps_series' => '1',
            'dps_number' => 1, 'dps_id' => str_repeat('0', 45), 'amount_cents' => 100, 'include_products' => true,
        ]);

        return [$order, $invoice->id];
    });

    $this->getJson("/api/invoices/{$invoiceId}")->assertNotFound();
    $this->getJson("/api/service-orders/{$order->id}/invoices")->assertOk()->assertJsonCount(0, 'data');
    $this->postJson("/api/service-orders/{$order->id}/invoices")->assertNotFound();
    $this->postJson("/api/invoices/{$invoiceId}/cancel", ['reason' => '1', 'text' => 'Valor lançado errado na OS.'])->assertNotFound();
    $this->getJson("/api/invoices/{$invoiceId}/files/xml")->assertNotFound();
    Http::assertNothingSent();

    // Job com o tenant de A não acha a nota de B.
    app(TransmitDps::class)->handle($invoiceId);
    expect(TenantContext::run($order->tenant_id, fn () => Invoice::query()->findOrFail($invoiceId)->status->value))->toBe('pending');
    expect($owner)->not->toBeNull();
});
