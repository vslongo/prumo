<?php

declare(strict_types=1);

namespace App\Modules\Billing\Http\Controllers;

use App\Modules\Billing\Actions\HandleAsaasWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Responde 200 rápido; evento desconhecido ou de assinatura que não é nossa também é 200 (o Asaas não reenvia). */
final class AsaasWebhookController
{
    public function __invoke(Request $request, HandleAsaasWebhook $handle): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->json()->all();
        $handle->handle($payload);

        return response()->json(['received' => true]);
    }
}
