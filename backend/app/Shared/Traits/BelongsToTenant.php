<?php

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Modules\Tenancy\Exceptions\MissingTenantContext;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use App\Modules\Tenancy\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Global scope de tenant + preenche `tenant_id` no creating.
 * Model que aceita linhas globais (tenant_id null) define `includesGlobalRows(): bool` retornando true.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $current = TenantContext::id();
            $explicit = $model->getAttribute('tenant_id');

            if ($current !== null) {
                if ($explicit !== null && $explicit !== $current) {
                    throw new LogicException('tenant_id diferente do tenant do contexto.');
                }
                $model->setAttribute('tenant_id', $current);

                return;
            }

            $allowsGlobal = method_exists($model, 'includesGlobalRows') && $model->includesGlobalRows();
            if ($explicit === null && ! $allowsGlobal) {
                throw new MissingTenantContext;
            }
        });
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
