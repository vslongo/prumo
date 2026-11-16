<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Http\Controllers;

use App\Modules\ServiceOrders\Actions\ConfirmServiceOrderItemPhoto;
use App\Modules\ServiceOrders\Actions\ListServiceOrderPhotos;
use App\Modules\ServiceOrders\Actions\RemoveServiceOrderItemPhoto;
use App\Modules\ServiceOrders\Actions\RequestServiceOrderItemPhotoUpload;
use App\Modules\ServiceOrders\Http\Requests\ItemPhotoUploadRequest;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Fotos por item. O arquivo vai do navegador direto para o R2; aqui só URL, confirmação e lista. */
final class ServiceOrderPhotoController
{
    public function index(ServiceOrder $serviceOrder, ListServiceOrderPhotos $list): JsonResponse
    {
        Gate::authorize('view', $serviceOrder);

        return response()->json(['data' => $list->handle($serviceOrder)]);
    }

    public function store(ItemPhotoUploadRequest $request, ServiceOrder $serviceOrder, ServiceOrderItem $item, RequestServiceOrderItemPhotoUpload $requestUpload): JsonResponse
    {
        Gate::authorize('update', $serviceOrder);

        return response()->json(['data' => $requestUpload->handle($serviceOrder, $item, $request->string('mime')->toString(), $request->integer('size'))], 201);
    }

    public function confirm(ServiceOrder $serviceOrder, ServiceOrderItem $item, string $media, ConfirmServiceOrderItemPhoto $confirm): Response
    {
        Gate::authorize('update', $serviceOrder);
        $confirm->handle($item, $media);

        return response()->noContent();
    }

    public function destroy(ServiceOrder $serviceOrder, ServiceOrderItem $item, string $media, RemoveServiceOrderItemPhoto $remove): Response
    {
        Gate::authorize('update', $serviceOrder);
        $remove->handle($serviceOrder, $item, $media);

        return response()->noContent();
    }
}
