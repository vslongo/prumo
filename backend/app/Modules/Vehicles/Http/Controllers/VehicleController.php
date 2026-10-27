<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Http\Controllers;

use App\Modules\Vehicles\Actions\CreateVehicle;
use App\Modules\Vehicles\Actions\FindVehicleByPlate;
use App\Modules\Vehicles\Actions\ListCustomerVehicles;
use App\Modules\Vehicles\Data\VehicleData;
use App\Modules\Vehicles\Http\Requests\FindVehicleByPlateRequest;
use App\Modules\Vehicles\Http\Requests\StoreVehicleRequest;
use App\Modules\Vehicles\Http\Resources\VehicleResource;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class VehicleController
{
    /** GET /vehicles?plate=ABC1D23 — busca pela placa (fluxo principal do balcão). */
    public function index(FindVehicleByPlateRequest $request, FindVehicleByPlate $find): VehicleResource
    {
        Gate::authorize('viewAny', Vehicle::class);

        $vehicle = $find->handle($request->string('plate')->toString());
        abort_if($vehicle === null, 404, 'Veículo não encontrado.');

        return new VehicleResource($vehicle);
    }

    /** GET /customers/{customerId}/vehicles — veículos do cliente (o scope já isola o tenant). */
    public function forCustomer(string $customerId, ListCustomerVehicles $list): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Vehicle::class);
        abort_unless(Str::isUuid($customerId), 404);

        return VehicleResource::collection($list->handle($customerId));
    }

    public function store(StoreVehicleRequest $request, CreateVehicle $create): VehicleResource
    {
        Gate::authorize('create', Vehicle::class);

        return new VehicleResource($create->handle(VehicleData::fromArray($request->validated())));
    }

    public function show(Vehicle $vehicle): VehicleResource
    {
        Gate::authorize('view', $vehicle);

        return new VehicleResource($vehicle);
    }
}
