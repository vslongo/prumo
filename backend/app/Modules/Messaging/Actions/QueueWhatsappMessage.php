<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Actions;

use App\Modules\Messaging\Data\OutboundMessageData;
use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Jobs\PublishWhatsappMessageJob;
use App\Modules\Messaging\Models\Message;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Registra a mensagem (`queued`) e entrega para o worker por fila. Idempotente pela chave:
 * o mesmo evento chegando duas vezes não manda duas mensagens. Sem número configurado, não faz nada (só wa.me).
 */
final class QueueWhatsappMessage
{
    public function __construct(private readonly ResolveWhatsappSender $sender) {}

    public function handle(OutboundMessageData $data): ?Message
    {
        $sender = $this->sender->handle($data->tenantId);
        if ($sender === null) {
            return null;
        }

        if ($existing = Message::query()->where('idempotency_key', $data->idempotencyKey)->first()) {
            return $existing;
        }

        try {
            $message = Message::create([
                'customer_id' => $data->customerId,
                'template' => $data->template,
                'status' => MessageStatus::Queued,
                'to' => $data->to,
                'params' => $data->params,
                'sender_phone_number_id' => $sender,
                'subject_type' => $data->subjectType,
                'subject_id' => $data->subjectId,
                'idempotency_key' => $data->idempotencyKey,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Duas entregas do mesmo evento ao mesmo tempo: a outra já registrou.
            return Message::query()->where('idempotency_key', $data->idempotencyKey)->first();
        }

        PublishWhatsappMessageJob::dispatch($message->tenant_id, $message->id);

        return $message;
    }
}
