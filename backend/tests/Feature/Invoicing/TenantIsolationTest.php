<?php

declare(strict_types=1);

use App\Modules\Invoicing\Actions\RequestCertificateUpload;
use App\Modules\Invoicing\Actions\UpdateFiscalProfile;
use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Enums\TaxRegime;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Tenancy\TenantContext;
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
