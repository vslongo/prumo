<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Http\Requests;

use App\Shared\Support\JsonSchemaValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Valida o próprio `fields_schema` (o "schema do schema"). */
final class ServiceTypeRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'default_price_cents' => ['required', 'integer', 'min:0'],
            'fields_schema' => ['present', 'array', 'list', 'max:30'],
            'fields_schema.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]{0,49}$/', 'distinct'],
            'fields_schema.*.label' => ['required', 'string', 'max:100'],
            'fields_schema.*.type' => ['required', Rule::in(JsonSchemaValidator::TYPES)],
            'fields_schema.*.required' => ['required', 'boolean'],
            'fields_schema.*.options' => ['required_if:fields_schema.*.type,select', 'array', 'list', 'min:1'],
            'fields_schema.*.options.*' => ['string', 'max:100'],
            'fields_schema.*.unit' => ['nullable', 'string', 'max:20'],
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('fields_schema', []) as $i => $field) {
                if (is_array($field) && isset($field['options']) && ($field['type'] ?? null) !== 'select') {
                    $validator->errors()->add("fields_schema.{$i}.options", 'Opções só valem para campos do tipo seleção.');
                }
            }
        }];
    }
}
