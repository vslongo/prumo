<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Actions;

use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use Illuminate\Validation\ValidationException;

/** URL pré-assinada para o técnico subir foto de um item da OS direto no R2. */
final class RequestServiceOrderItemPhotoUpload
{
    public const OWNER_TYPE = 'service_order_item';

    public function __construct(private readonly RequestUploadUrl $requestUploadUrl) {}

    /** @return array{media_id: string, url: string, headers: array<string, mixed>, expires_at: string} */
    public function handle(ServiceOrder $order, ServiceOrderItem $item, string $mime, int $size): array
    {
        if (! $order->status->acceptsPhotos()) {
            throw ValidationException::withMessages(['status' => 'OS fechada ou cancelada não recebe fotos.']);
        }

        $upload = $this->requestUploadUrl->handle(self::OWNER_TYPE, $item->id, $mime, $size, RequestUploadUrl::PHOTO_MIMES);

        return ['media_id' => $upload['media']->id, 'url' => $upload['url'], 'headers' => $upload['headers'], 'expires_at' => $upload['expires_at']];
    }
}
