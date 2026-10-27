<?php

declare(strict_types=1);

use App\Shared\Support\JsonSchemaValidator;

$schema = [
    ['key' => 'oil_brand', 'label' => 'Marca', 'type' => 'text', 'required' => true],
    ['key' => 'viscosity', 'label' => 'Viscosidade', 'type' => 'select', 'required' => true, 'options' => ['5W30', '10W40']],
    ['key' => 'liters', 'label' => 'Litros', 'type' => 'number', 'required' => true],
    ['key' => 'filter_changed', 'label' => 'Filtro', 'type' => 'boolean', 'required' => false],
    ['key' => 'next_change_on', 'label' => 'Próxima troca', 'type' => 'date', 'required' => false],
];

it('aceita valores válidos', function () use ($schema) {
    $errors = (new JsonSchemaValidator)->validate($schema, [
        'oil_brand' => 'Mobil', 'viscosity' => '5W30', 'liters' => 4.5, 'filter_changed' => false, 'next_change_on' => '2027-01-31',
    ]);

    expect($errors)->toBe([]);
});

it('aponta obrigatório faltando, tipo errado, opção inválida e campo desconhecido', function () use ($schema) {
    $errors = (new JsonSchemaValidator)->validate($schema, [
        'viscosity' => '0W99', 'liters' => '4', 'filter_changed' => 'sim', 'next_change_on' => '2027-02-30', 'color' => 'azul',
    ]);

    expect($errors)->toHaveKeys(['oil_brand', 'viscosity', 'liters', 'filter_changed', 'next_change_on', 'color'])
        ->and($errors['oil_brand'])->toContain('obrigatório')
        ->and($errors['viscosity'])->toContain('Opção inválida');
});
