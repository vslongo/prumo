<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('cria cliente com telefone e CPF criptografados e consentimento registrado', function () {
    actingAsRole();

    $response = $this->postJson('/api/customers', [
        'name' => 'João da Silva',
        'phone' => '(11) 98888-7777',
        'document' => '123.456.789-09',
        'consent_whatsapp' => true,
        'consent_source' => 'balcao',
    ])->assertCreated()->assertJsonPath('data.phone', '11988887777');

    $row = DB::table('customers')->where('id', $response->json('data.id'))->first();
    expect($row->phone)->not->toContain('988887777')
        ->and($row->document)->not->toContain('456')
        ->and($row->phone_hash)->toHaveLength(64)
        ->and($row->consent_whatsapp_at)->not->toBeNull()
        ->and($row->consent_whatsapp_source)->toBe('balcao');
});

it('busca cliente por telefone exato via hash e por nome', function () {
    actingAsRole();
    $this->postJson('/api/customers', ['name' => 'Maria Souza', 'phone' => '11977776666'])->assertCreated();

    $this->getJson('/api/customers?q='.urlencode('(11) 97777-6666'))->assertJsonPath('data.0.name', 'Maria Souza');
    $this->getJson('/api/customers?q=souza')->assertJsonCount(1, 'data');
});

it('valida o cadastro', function () {
    actingAsRole();

    $this->postJson('/api/customers', ['phone' => 'abc'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'phone']);
});
