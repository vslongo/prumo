<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Actions;

use App\Modules\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Acha o registro dono de um webhook de integração já autenticado (ex.: assinatura do Asaas pelo id de lá),
 * sem tenant no contexto. O tenant vem do registro encontrado, nunca do corpo do webhook.
 */
final class ResolveWebhookOwner
{
    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    public function handle(string $modelClass, string $column, string $externalId): ?Model
    {
        if ($externalId === '') {
            return null;
        }

        return $modelClass::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where($column, $externalId)
            ->first();
    }
}
