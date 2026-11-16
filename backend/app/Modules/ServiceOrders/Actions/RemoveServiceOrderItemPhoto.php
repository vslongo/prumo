<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Actions\FindOwnerMedia;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use Illuminate\Validation\ValidationException;

/** Só fotos da própria OS; as que vieram do orçamento não são removidas por aqui (404). */
final class RemoveServiceOrderItemPhoto
{
    public function __construct(
        private readonly FindOwnerMedia $findMedia,
        private readonly DeleteMedia $deleteMedia,
    ) {}

    public function handle(ServiceOrder $order, ServiceOrderItem $item, string $mediaId): void
    {
        $media = $this->findMedia->handle(RequestServiceOrderItemPhotoUpload::OWNER_TYPE, $item->id, $mediaId);

        if (! $order->status->acceptsPhotos()) {
            throw ValidationException::withMessages(['status' => 'OS fechada ou cancelada: as fotos não podem ser removidas.']);
        }

        $this->deleteMedia->handle($media);
    }
}
