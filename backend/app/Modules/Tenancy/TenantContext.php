<?php

declare(strict_types=1);

namespace App\Modules\Tenancy;

use App\Modules\Tenancy\Exceptions\MissingTenantContext;
use Closure;

/** Tenant atual da request/job. Registrado como `scoped` (zera a cada request e job). */
final class TenantContext
{
    private ?string $tenantId = null;

    public static function id(): ?string
    {
        return app(self::class)->tenantId;
    }

    public static function idOrFail(): string
    {
        return self::id() ?? throw new MissingTenantContext;
    }

    public static function set(?string $tenantId): void
    {
        app(self::class)->tenantId = $tenantId;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function run(string $tenantId, Closure $callback): mixed
    {
        $previous = self::id();
        self::set($tenantId);

        try {
            return $callback();
        } finally {
            self::set($previous);
        }
    }
}
