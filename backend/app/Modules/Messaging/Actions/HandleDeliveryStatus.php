<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Actions;

use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Models\Message;
use Illuminate\Support\Facades\DB;

/**
 * Status vindo do worker (envio aceito pela Meta ou webhook de entrega/leitura/falha).
 * Só avança: status repetido ou atrasado (delivered depois de read) é ignorado, então o callback pode chegar quantas vezes for.
 */
final class HandleDeliveryStatus
{
    public function handle(string $messageId, MessageStatus $status, ?string $providerMessageId = null, ?string $error = null): ?Message
    {
        return DB::transaction(function () use ($messageId, $status, $providerMessageId, $error) {
            $message = Message::query()->lockForUpdate()->find($messageId);
            if ($message === null) {
                return null;
            }

            if ($providerMessageId !== null && $message->provider_message_id === null) {
                $message->provider_message_id = $providerMessageId;
            }

            if ($message->status->canMoveTo($status)) {
                $message->status = $status;
                $timestamp = match ($status) {
                    MessageStatus::Sent => 'sent_at',
                    MessageStatus::Delivered => 'delivered_at',
                    MessageStatus::Read => 'read_at',
                    MessageStatus::Failed => 'failed_at',
                    MessageStatus::Queued => null,
                };
                if ($timestamp !== null) {
                    $message->setAttribute($timestamp, now());
                }
                if ($status === MessageStatus::Failed && $error !== null) {
                    $message->error = mb_substr($error, 0, 500);
                }
            }

            $message->save();

            return $message;
        });
    }
}
