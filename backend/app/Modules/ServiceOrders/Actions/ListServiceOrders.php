<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/** Filtro por veículo = histórico do carro no core. */
final class ListServiceOrders
{
    /** @return LengthAwarePaginator<int, ServiceOrder> */
    public function handle(?ServiceOrderStatus $status = null, ?string $vehicleId = null, ?string $quoteId = null): LengthAwarePaginator
    {
        return ServiceOrder::query()
            ->with(['customer:id,name', 'vehicle:id,plate_normalized,brand,model'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($quoteId, fn ($q) => $q->where('quote_id', $quoteId))
            ->orderByDesc('number')
            ->paginate(20);
    }
}
