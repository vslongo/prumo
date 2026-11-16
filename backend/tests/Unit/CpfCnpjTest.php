<?php

declare(strict_types=1);

use App\Shared\Support\CpfCnpj;

it('valida CPF e CNPJ (numérico e alfanumérico)', function (string $value, bool $valid) {
    expect(CpfCnpj::isValid($value))->toBe($valid);
})->with([
    'CPF com máscara' => ['529.982.247-25', true],
    'CPF dígito errado' => ['529.982.247-24', false],
    'CPF repetido' => ['111.111.111-11', false],
    'CNPJ numérico' => ['11.222.333/0001-81', true],
    'CNPJ dígito errado' => ['11.222.333/0001-82', false],
    'CNPJ alfanumérico (exemplo da Receita)' => ['12.ABC.345/01DE-35', true],
    'CNPJ alfanumérico minúsculo' => ['12abc34501de35', true],
    'CNPJ alfanumérico dígito errado' => ['12ABC34501DE36', false],
    'letra no dígito verificador' => ['12ABC34501DE3A', false],
    'tamanho errado' => ['123', false],
]);
