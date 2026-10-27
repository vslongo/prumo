<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Uso interno. Número humano sequencial por tenant. Precisa rodar dentro de transação:
 * o advisory lock do Postgres serializa aberturas concorrentes do mesmo tenant.
 */
final class NextServiceOrderNumber
{
    public function handle(): int
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('NextServiceOrderNumber exige transação.');
        }

        DB::select('select pg_advisory_xact_lock(hashtext(?))', ['service_orders:'.TenantContext::idOrFail()]);

        return (int) ServiceOrder::withTrashed()->max('number') + 1;
    }
}
