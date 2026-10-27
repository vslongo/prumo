<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Actions;

use App\Modules\ServiceTypes\Models\ServiceType;
use App\Shared\Support\JsonSchemaValidator;
use Illuminate\Validation\ValidationException;

/**
 * API pública usada por Quotes e ServiceOrders: valida `fields` de um item contra o tipo de serviço
 * e devolve o snapshot do schema para gravar no item.
 */
final class ValidateItemFields
{
    public function __construct(private readonly JsonSchemaValidator $validator) {}

    /**
     * @param  array<string, mixed>  $fields
     * @return list<array<string, mixed>> fields_schema_snapshot
     *
     * @throws ValidationException
     */
    public function handle(?string $serviceTypeId, array $fields, string $errorPrefix = 'fields'): array
    {
        if ($serviceTypeId === null) {
            if ($fields !== []) {
                throw ValidationException::withMessages([$errorPrefix => 'Campos específicos exigem um tipo de serviço.']);
            }

            return [];
        }

        $serviceType = ServiceType::query()->find($serviceTypeId)
            ?? throw ValidationException::withMessages([$errorPrefix => 'Tipo de serviço não encontrado.']);

        $errors = $this->validator->validate($serviceType->fields_schema, $fields);

        if ($errors !== []) {
            throw ValidationException::withMessages(
                collect($errors)->mapWithKeys(fn (string $message, string $key) => ["{$errorPrefix}.{$key}" => $message])->all()
            );
        }

        return $serviceType->fields_schema;
    }
}
