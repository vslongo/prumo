<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Jobs;

use App\Modules\Messaging\Enums\MessageStatus;
use App\Modules\Messaging\Models\Message;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;

/**
 * Entrega a mensagem para o worker Node (lista Redis `whatsapp:outbound`). O worker só envia e devolve o status
 * pelo callback assinado; regra de negócio fica aqui no Laravel.
 */
final class PublishWhatsappMessageJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const QUEUE_KEY = 'whatsapp:outbound';

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public readonly string $tenantId, public readonly string $messageId) {}

    public function uniqueId(): string
    {
        return $this->messageId;
    }

    public function handle(): void
    {
        TenantContext::run($this->tenantId, function () {
            $message = Message::query()->find($this->messageId);
            if ($message === null || $message->status !== MessageStatus::Queued) {
                return;
            }

            Redis::rpush(self::QUEUE_KEY, (string) json_encode([
                'tenant_id' => $message->tenant_id,
                'message_id' => $message->id,
                'template' => $message->template->value,
                'language' => 'pt_BR',
                'to' => $message->to,
                'params' => $message->params,
                'sender_phone_number_id' => $message->sender_phone_number_id,
            ]));
        });
    }
}
