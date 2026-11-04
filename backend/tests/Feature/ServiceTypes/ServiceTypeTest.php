<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\ServiceTypes\Actions\UpdateServiceType;
use App\Modules\ServiceTypes\Data\ServiceTypeData;
use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Auth\Access\AuthorizationException;

$gasket = [
    'name' => 'Troca de junta',
    'default_price_cents' => 25000,
    'fields_schema' => [
        ['key' => 'gasket', 'label' => 'Junta', 'type' => 'select', 'required' => true, 'options' => ['cabeçote', 'cárter']],
        ['key' => 'resurfacing', 'label' => 'Retífica', 'type' => 'boolean', 'required' => false],
    ],
];

it('owner cria tipo de serviço com schema de campos', function () use ($gasket) {
    actingAsRole(UserRole::Owner);

    $this->postJson('/api/service-types', $gasket)
        ->assertCreated()
        ->assertJsonPath('data.fields_schema.0.key', 'gasket')
        ->assertJsonPath('data.is_system_default', false);
});

it('técnico não acessa configurações', function () use ($gasket) {
    actingAsRole(UserRole::Technician);

    $this->postJson('/api/service-types', $gasket)->assertForbidden();
});

it('valida o schema dos campos', function () {
    actingAsRole();

    $this->postJson('/api/service-types', [
        'name' => 'Ruim',
        'default_price_cents' => 0,
        'fields_schema' => [
            ['key' => 'Lado', 'label' => 'Lado', 'type' => 'select', 'required' => true],
            ['key' => 'x', 'label' => 'X', 'type' => 'cor', 'required' => true],
        ],
    ])->assertUnprocessable()->assertJsonValidationErrors(['fields_schema.0.key', 'fields_schema.0.options', 'fields_schema.1.type']);
});

it('tipo padrão do sistema é somente leitura', function () use ($gasket) {
    $system = ServiceType::factory()->create(['name' => 'Padrão']); // sem tenant no contexto = padrão do sistema
    expect($system->tenant_id)->toBeNull();
    actingAsRole();

    $this->putJson("/api/service-types/{$system->id}", $gasket)->assertForbidden();
    $this->getJson('/api/service-types')->assertJsonFragment(['name' => 'Padrão', 'is_system_default' => true]);
});

it('owner edita tipo da oficina; técnico não edita', function () use ($gasket) {
    actingAsRole(UserRole::Owner);
    $id = $this->postJson('/api/service-types', $gasket)->assertCreated()->json('data.id');

    $this->putJson("/api/service-types/{$id}", [...$gasket, 'name' => 'Junta do cabeçote', 'default_price_cents' => 30000])
        ->assertOk()
        ->assertJsonPath('data.name', 'Junta do cabeçote')
        ->assertJsonPath('data.default_price_cents', 30000);

    actingAsRole(UserRole::Technician, ServiceType::findOrFail($id)->tenant);
    $this->putJson("/api/service-types/{$id}", $gasket)->assertForbidden();
});

it('a Action recusa alterar tipo padrão mesmo sem passar pela policy', function () use ($gasket) {
    $system = ServiceType::factory()->create();

    expect(fn () => app(UpdateServiceType::class)->handle($system, ServiceTypeData::fromArray($gasket)))
        ->toThrow(AuthorizationException::class);
});
