<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Controllers;

use App\Modules\ServiceOrders\Actions\AddServiceOrderItem;
use App\Modules\ServiceOrders\Actions\CloseServiceOrder;
use App\Modules\ServiceOrders\Actions\ListServiceOrders;
use App\Modules\ServiceOrders\Actions\MarkItemDone;
use App\Modules\ServiceOrders\Actions\OpenServiceOrder;
use App\Modules\ServiceOrders\Actions\TransitionServiceOrder;
use App\Modules\ServiceOrders\Data\OpenServiceOrderData;
use App\Modules\ServiceOrders\Data\ServiceOrderItemData;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Http\Requests\CloseServiceOrderRequest;
use App\Modules\ServiceOrders\Http\Requests\ListServiceOrdersRequest;
use App\Modules\ServiceOrders\Http\Requests\StoreServiceOrderItemRequest;
use App\Modules\ServiceOrders\Http\Requests\StoreServiceOrderRequest;
use App\Modules\ServiceOrders\Http\Requests\TransitionServiceOrderRequest;
use App\Modules\ServiceOrders\Http\Resources\ServiceOrderResource;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ServiceOrderController
{
    public function index(ListServiceOrdersRequest $request, ListServiceOrders $list): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ServiceOrder::class);

        return ServiceOrderResource::collection($list->handle(
            $request->enum('status', ServiceOrderStatus::class),
            $request->validated('vehicle_id'),
            $request->validated('quote_id'),
        ));
    }

    public function store(StoreServiceOrderRequest $request, OpenServiceOrder $open): ServiceOrderResource
    {
        Gate::authorize('create', ServiceOrder::class);

        $order = $open->handle(OpenServiceOrderData::fromArray($request->validated(), (string) $request->user()?->id));

        return new ServiceOrderResource($order->load(['items', 'customer', 'vehicle']));
    }

    public function show(ServiceOrder $serviceOrder): ServiceOrderResource
    {
        Gate::authorize('view', $serviceOrder);

        return new ServiceOrderResource($serviceOrder->load(['items', 'customer', 'vehicle']));
    }

    public function addItem(StoreServiceOrderItemRequest $request, ServiceOrder $serviceOrder, AddServiceOrderItem $add): ServiceOrderResource
    {
        Gate::authorize('update', $serviceOrder);
        $add->handle($serviceOrder, ServiceOrderItemData::fromArray($request->validated()));

        return new ServiceOrderResource($serviceOrder->refresh()->load(['items', 'customer', 'vehicle']));
    }

    public function markItemDone(Request $request, ServiceOrder $serviceOrder, ServiceOrderItem $item, MarkItemDone $mark): ServiceOrderResource
    {
        Gate::authorize('update', $serviceOrder);
        $mark->handle($serviceOrder, $item, (string) $request->user()?->id);

        return new ServiceOrderResource($serviceOrder->refresh()->load(['items', 'customer', 'vehicle']));
    }

    public function transition(TransitionServiceOrderRequest $request, ServiceOrder $serviceOrder, TransitionServiceOrder $transition): ServiceOrderResource
    {
        Gate::authorize('update', $serviceOrder);
        $status = ServiceOrderStatus::from($request->string('status')->toString());

        return new ServiceOrderResource($transition->handle($serviceOrder, $status)->load(['items', 'customer', 'vehicle']));
    }

    public function close(CloseServiceOrderRequest $request, ServiceOrder $serviceOrder, CloseServiceOrder $close): ServiceOrderResource
    {
        Gate::authorize('update', $serviceOrder);

        return new ServiceOrderResource($close->handle($serviceOrder, $request->integer('mileage_out'))->load(['items', 'customer', 'vehicle']));
    }
}
