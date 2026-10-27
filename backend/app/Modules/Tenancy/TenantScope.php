<?php

declare(strict_types=1);

namespace App\Modules\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/** Filtra pelo tenant atual. Sem tenant no contexto não devolve nada (falha fechada). */
final class TenantScope implements Scope
{
    /** @param Builder<Model> $builder */
    public function apply(Builder $builder, Model $model): void
    {
        $column = $model->qualifyColumn('tenant_id');
        $tenantId = TenantContext::id();
        $includesGlobal = method_exists($model, 'includesGlobalRows') && $model->includesGlobalRows();

        if ($tenantId === null) {
            $includesGlobal ? $builder->whereNull($column) : $builder->whereRaw('1 = 0');

            return;
        }

        $includesGlobal
            ? $builder->where(fn (Builder $q) => $q->where($column, $tenantId)->orWhereNull($column))
            : $builder->where($column, $tenantId);
    }
}
