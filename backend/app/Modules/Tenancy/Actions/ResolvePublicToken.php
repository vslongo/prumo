<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Actions;

use App\Modules\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * Acha um registro pelo token de um link público, sem tenant no contexto.
 * O token é o segredo; o tenant vem do registro encontrado, nunca da URL.
 */
final class ResolvePublicToken
{
    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    public function handle(string $modelClass, string $rawToken, string $column = 'token_hash'): ?Model
    {
        if (strlen($rawToken) !== 64 || ! ctype_xdigit($rawToken)) {
            return null;
        }

        return $modelClass::query()
            ->withoutGlobalScope(TenantScope::class)
            ->where($column, hash('sha256', $rawToken))
            ->first();
    }
}
