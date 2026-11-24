<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Customers\Models\Customer;
use App\Modules\Media\Models\Media;
use App\Modules\ServiceTypes\Models\ServiceType;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature');

/** Cria tenant + usuário, autentica e coloca o tenant no contexto (para factories). */
function actingAsRole(UserRole $role = UserRole::Owner, ?Tenant $tenant = null): User
{
    $tenant ??= Tenant::factory()->create();
    $user = User::factory()->for($tenant)->role($role)->create();
    Sanctum::actingAs($user);
    TenantContext::set($tenant->id);

    return $user;
}

/** Cria dados em outro tenant sem trocar o tenant do teste. */
function inOtherTenant(Closure $callback, ?Tenant $tenant = null): mixed
{
    $tenant ??= Tenant::factory()->create();

    return TenantContext::run($tenant->id, $callback);
}

function makeVehicle(): Vehicle
{
    return Vehicle::factory()->create();
}

function oilChangeType(): ServiceType
{
    return ServiceType::factory()->create();
}

function oilItem(User $technician, ServiceType $type, array $overrides = []): array
{
    return [
        'kind' => 'service',
        'description' => 'Troca de óleo',
        'service_type_id' => $type->id,
        'technician_id' => $technician->id,
        'quantity' => '1',
        'unit_price_cents' => 8000,
        'fields' => ['oil_brand' => 'Mobil', 'viscosity' => '5W30', 'liters' => 4, 'filter_changed' => true],
        ...$overrides,
    ];
}

function productItem(User $technician, array $overrides = []): array
{
    return [
        'kind' => 'product',
        'description' => 'Óleo 5W30 (litro)',
        'technician_id' => $technician->id,
        'quantity' => '4.5',
        'unit_price_cents' => 3999,
        ...$overrides,
    ];
}

function customerOf(Vehicle $vehicle): Customer
{
    return $vehicle->customer;
}

// --- NFS-e (Invoicing) ---

const FISCAL_CNPJ = '11222333000181';

/** Perfil válido de Simples ME/EPP (sobrescreva o que quiser). */
function fiscalPayload(array $override = []): array
{
    return array_merge([
        'cnpj' => '11.222.333/0001-81',
        'municipal_registration' => '12345',
        'city_ibge_code' => '4205407',
        'tax_regime' => 'simples_me_epp',
        'national_tax_code' => '140101',
        'iss_rate_bp' => 300,
        'dps_series' => '1',
        'include_products' => true,
        'environment' => 'restricted',
    ], $override);
}

/** .pfx de teste (autoassinado) com o CN no formato do e-CNPJ ICP-Brasil. */
function testPfx(string $cnpj = FISCAL_CNPJ, string $password = 'segredo', int $days = 365): string
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new(['commonName' => "OFICINA TESTE LTDA:{$cnpj}"], $key);
    $cert = openssl_csr_sign($csr, null, $key, $days);
    openssl_pkcs12_export($cert, $pfx, $key, $password);

    return $pfx;
}

/** Pede a URL, simula o PUT do navegador no R2 e devolve o id da mídia. */
function uploadPfx(string $pfx): string
{
    $mediaId = test()->postJson('/api/fiscal-profile/certificate/upload-url', ['size' => strlen($pfx)])
        ->assertOk()->json('data.media_id');
    Storage::disk('r2')->put(Media::query()->findOrFail($mediaId)->r2_key, $pfx);

    return $mediaId;
}

/** Perfil fiscal + certificado A1 aceito, pela API (usuário já autenticado). */
function fiscalReady(array $override = []): void
{
    test()->putJson('/api/fiscal-profile', fiscalPayload($override))->assertOk();
    $mediaId = uploadPfx(testPfx());
    test()->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => 'segredo'])->assertOk();
}
