<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Actions;

use App\Modules\Messaging\Models\Message;

/** Mensagens de WhatsApp enviadas ao cliente, para a exportação LGPD (ExportCustomerData). */
final class ExportCustomerMessages
{
    /** @return array<int, array<string, mixed>> */
    public function handle(string $customerId): array
    {
        return Message::query()->where('customer_id', $customerId)->orderBy('created_at')->get()
            ->map(fn (Message $message) => [
                'template' => $message->template->value,
                'to' => $message->to,
                'params' => $message->params,
                'status' => $message->status->value,
                'created_at' => $message->created_at?->toIso8601String(),
            ])->values()->all();
    }
}
