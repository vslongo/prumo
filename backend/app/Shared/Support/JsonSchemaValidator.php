<?php

declare(strict_types=1);

namespace App\Shared\Support;

use DateTimeImmutable;

/**
 * Valida `fields` de um item contra o `fields_schema` do tipo de serviço.
 * Schema: lista de {key, label, type: text|number|select|boolean|date, required, options?, unit?}.
 */
final class JsonSchemaValidator
{
    public const TYPES = ['text', 'number', 'select', 'boolean', 'date'];

    /**
     * @param  list<array<string, mixed>>  $schema
     * @param  array<string, mixed>  $values
     * @return array<string, string> erros por chave do campo
     */
    public function validate(array $schema, array $values): array
    {
        $errors = [];
        $known = [];

        foreach ($schema as $field) {
            $key = (string) $field['key'];
            $label = (string) ($field['label'] ?? $key);
            $known[] = $key;
            $value = $values[$key] ?? null;

            if ($value === null || $value === '') {
                if (! empty($field['required'])) {
                    $errors[$key] = "O campo {$label} é obrigatório.";
                }

                continue;
            }

            $error = match ($field['type'] ?? null) {
                'text' => is_string($value) && mb_strlen($value) <= 500 ? null : "O campo {$label} deve ser um texto de até 500 caracteres.",
                'number' => is_numeric($value) && ! is_string($value) ? null : "O campo {$label} deve ser um número.",
                'boolean' => is_bool($value) ? null : "O campo {$label} deve ser sim ou não.",
                'date' => is_string($value) && self::isDate($value) ? null : "O campo {$label} deve ser uma data (AAAA-MM-DD).",
                'select' => in_array($value, (array) ($field['options'] ?? []), true) ? null : "Opção inválida para o campo {$label}.",
                default => "Tipo de campo desconhecido em {$label}.",
            };

            if ($error !== null) {
                $errors[$key] = $error;
            }
        }

        foreach (array_diff(array_keys($values), $known) as $unknown) {
            $errors[(string) $unknown] = "O campo {$unknown} não existe neste tipo de serviço.";
        }

        return $errors;
    }

    private static function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
