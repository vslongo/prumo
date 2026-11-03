<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\Media\Actions\ConfirmUpload;
use App\Modules\Media\Actions\FindOwnerMedia;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;

/** Confirma a foto depois do PUT no R2. Sem checar estado: o upload em segundo plano pode terminar depois de fechar. */
final class ConfirmServiceOrderItemPhoto
{
    public function __construct(
        private readonly FindOwnerMedia $findMedia,
        private readonly ConfirmUpload $confirmUpload,
    ) {}

    public function handle(ServiceOrderItem $item, string $mediaId): void
    {
        $this->confirmUpload->handle($this->findMedia->handle(RequestServiceOrderItemPhotoUpload::OWNER_TYPE, $item->id, $mediaId));
    }
}
