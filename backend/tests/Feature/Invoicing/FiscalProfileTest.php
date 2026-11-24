<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

beforeEach(fn () => Storage::fake('r2'));

it('salva o perfil fiscal com CNPJ sem máscara e não expõe certificado', function () {
    actingAsRole(UserRole::Admin);

    $this->getJson('/api/fiscal-profile')->assertOk()->assertJsonPath('data', null);
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk()
        ->assertJsonPath('data.cnpj', FISCAL_CNPJ)
        ->assertJsonPath('data.include_products', true)
        ->assertJsonPath('data.certificate', null);

    $this->putJson('/api/fiscal-profile', fiscalPayload(['include_products' => false]))->assertOk();
    expect(FiscalProfile::query()->count())->toBe(1)
        ->and(FiscalProfile::query()->first()?->include_products)->toBeFalse();
});

it('recusa perfil inválido', function (array $override, string $field) {
    actingAsRole();

    $this->putJson('/api/fiscal-profile', fiscalPayload($override))->assertUnprocessable()->assertJsonValidationErrors($field);
})->with([
    'CNPJ inválido' => [['cnpj' => '11222333000100'], 'cnpj'],
    'CPF no lugar de CNPJ' => [['cnpj' => '52998224725'], 'cnpj'],
    'regime não atendido' => [['tax_regime' => 'lucro_presumido'], 'tax_regime'],
    'Simples sem alíquota' => [['iss_rate_bp' => null], 'iss_rate_bp'],
    'alíquota acima de 5%' => [['iss_rate_bp' => 600], 'iss_rate_bp'],
    'município sem 7 dígitos' => [['city_ibge_code' => '42054'], 'city_ibge_code'],
]);

it('aceita MEI sem alíquota de ISS', function () {
    actingAsRole();

    $this->putJson('/api/fiscal-profile', fiscalPayload(['tax_regime' => 'mei', 'iss_rate_bp' => null]))->assertOk();
});

it('técnico e balcão não mexem no perfil fiscal', function (UserRole $role) {
    actingAsRole($role);

    $this->deleteJson('/api/fiscal-profile/certificate')->assertForbidden();

    $this->getJson('/api/fiscal-profile')->assertForbidden();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertForbidden();
    $this->postJson('/api/fiscal-profile/certificate/upload-url', ['size' => 100])->assertForbidden();
})->with([UserRole::Technician, UserRole::Front]);

it('aceita o certificado A1 do CNPJ da oficina e guarda a senha criptografada', function () {
    actingAsRole();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();

    $mediaId = uploadPfx(testPfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => 'segredo'])->assertOk()
        ->assertJsonPath('data.certificate.valid', true)
        ->assertJsonMissingPath('data.certificate_password');

    $profile = FiscalProfile::query()->firstOrFail();
    expect($profile->certificate_media_id)->toBe($mediaId)
        ->and($profile->certificate_password)->toBe('segredo')
        ->and($profile->getRawOriginal('certificate_password'))->not->toContain('segredo')
        ->and($profile->certificate_expires_at?->isFuture())->toBeTrue();
});

it('troca de certificado apaga o anterior', function () {
    actingAsRole();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();
    $first = uploadPfx(testPfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $first, 'password' => 'segredo'])->assertOk();

    $second = uploadPfx(testPfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $second, 'password' => 'segredo'])->assertOk();

    expect(Media::query()->pluck('id')->all())->toBe([$second]);
});

it('recusa certificado e apaga o arquivo', function (Closure $pfx, string $password, string $field) {
    actingAsRole();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();

    $mediaId = uploadPfx($pfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => $password])
        ->assertUnprocessable()->assertJsonValidationErrors($field);

    expect(Media::query()->count())->toBe(0)
        ->and(Storage::disk('r2')->allFiles())->toBe([])
        ->and(FiscalProfile::query()->firstOrFail()->certificate_media_id)->toBeNull();
})->with([
    'senha errada' => [fn () => testPfx(), 'outra', 'password'],
    'CNPJ de outra empresa' => [fn () => testPfx('11444777000161'), 'segredo', 'certificate'],
    'vencido' => [fn () => tap(testPfx(days: 1), fn () => test()->travel(2)->days()), 'segredo', 'certificate'],
    'não é PKCS#12' => [fn () => "\x30não é certificado", 'segredo', 'password'],
]);

it('não pede upload de certificado sem perfil fiscal', function () {
    actingAsRole();

    $this->postJson('/api/fiscal-profile/certificate/upload-url', ['size' => 100])->assertUnprocessable()->assertJsonValidationErrors('profile');
});

it('trocar o CNPJ descarta o certificado', function () {
    actingAsRole();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();
    $mediaId = uploadPfx(testPfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => 'segredo'])->assertOk();

    $this->putJson('/api/fiscal-profile', fiscalPayload(['cnpj' => '11444777000161']))->assertOk()
        ->assertJsonPath('data.certificate', null);
    expect(Media::query()->count())->toBe(0);
});

it('upload genérico de mídia não aceita certificado', function () {
    actingAsRole();

    expect(fn () => app(RequestUploadUrl::class)->handle('x', (string) str()->uuid(), 'application/x-pkcs12', 100))
        ->toThrow(ValidationException::class);
});

it('remove o certificado e a senha', function () {
    actingAsRole();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();
    $mediaId = uploadPfx(testPfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => 'segredo'])->assertOk();

    $this->deleteJson('/api/fiscal-profile/certificate')->assertOk()->assertJsonPath('data.certificate', null);

    $profile = FiscalProfile::query()->firstOrFail();
    expect($profile->certificate_password)->toBeNull()
        ->and($profile->certificate_sha256)->toBeNull()
        ->and(Media::query()->count())->toBe(0)
        ->and(Storage::disk('r2')->allFiles())->toBe([]);
});

it('confirmar de novo o certificado em uso não relê o arquivo', function () {
    actingAsRole();
    $this->putJson('/api/fiscal-profile', fiscalPayload())->assertOk();
    $mediaId = uploadPfx(testPfx());
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => 'segredo'])->assertOk();
    $hash = FiscalProfile::query()->firstOrFail()->certificate_sha256;

    // Objeto trocado depois do aceite (URL pré-assinada ainda válida): não vira o certificado.
    Storage::disk('r2')->put(Media::query()->findOrFail($mediaId)->r2_key, 'outra coisa');
    $this->postJson('/api/fiscal-profile/certificate', ['media_id' => $mediaId, 'password' => 'segredo'])->assertOk();

    expect($hash)->toHaveLength(64)->and(FiscalProfile::query()->firstOrFail()->certificate_sha256)->toBe($hash);
});
