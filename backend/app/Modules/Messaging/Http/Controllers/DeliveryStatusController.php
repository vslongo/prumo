<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Http\Controllers;

use App\Modules\Messaging\Actions\HandleDeliveryStatus;
use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Http\Requests\DeliveryStatusRequest;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

/**
 * Worker → API. A assinatura (VerifyWorkerSignature) prova que veio do nosso worker, então o tenant_id do corpo é confiável;
 * a mensagem é buscada dentro do tenant (global scope).
 */
final class DeliveryStatusController
{
    public function __invoke(DeliveryStatusRequest $request, HandleDeliveryStatus $handle): JsonResponse
    {
        /** @var array{tenant_id: string, message_id: string, status: string, provider_message_id?: string|null, error?: string|null} $data */
        $data = $request->validated();

        $message = TenantContext::run($data['tenant_id'], fn () => $handle->handle(
            $data['message_id'],
            MessageStatus::from($data['status']),
            $data['provider_message_id'] ?? null,
            $data['error'] ?? null,
        ));

        return response()->json(status: $message === null ? 404 : 204);
    }
}
