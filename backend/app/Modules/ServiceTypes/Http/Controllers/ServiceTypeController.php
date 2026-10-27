<?php

declare(strict_types=1);

namespace App\Modules\ServiceTypes\Http\Controllers;

use App\Modules\ServiceTypes\Actions\CreateServiceType;
use App\Modules\ServiceTypes\Actions\ListServiceTypes;
use App\Modules\ServiceTypes\Actions\UpdateServiceType;
use App\Modules\ServiceTypes\Data\ServiceTypeData;
use App\Modules\ServiceTypes\Http\Requests\ServiceTypeRequest;
use App\Modules\ServiceTypes\Http\Resources\ServiceTypeResource;
use App\Modules\ServiceTypes\Models\ServiceType;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ServiceTypeController
{
    public function index(ListServiceTypes $list): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ServiceType::class);

        return ServiceTypeResource::collection($list->handle());
    }

    public function store(ServiceTypeRequest $request, CreateServiceType $create): ServiceTypeResource
    {
        Gate::authorize('create', ServiceType::class);

        return new ServiceTypeResource($create->handle(ServiceTypeData::fromArray($request->validated())));
    }

    public function update(ServiceTypeRequest $request, ServiceType $serviceType, UpdateServiceType $update): ServiceTypeResource
    {
        Gate::authorize('update', $serviceType);

        return new ServiceTypeResource($update->handle($serviceType, ServiceTypeData::fromArray($request->validated())));
    }
}
