<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Listeners;

use App\Modules\Customers\Events\CustomerAnonymized;
use App\Modules\Messaging\Models\Message;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/** Cliente anonimizado → apaga telefone e parâmetros (que levam o nome) das mensagens dele. A linha e o status ficam. Idempotente. */
final class ScrubAnonymizedCustomerMessages implements ShouldQueueAfterCommit
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function handle(CustomerAnonymized $event): void
    {
        TenantContext::run($event->tenantId, function () use ($event) {
            Message::query()->where('customer_id', $event->customerId)->each(function (Message $message) {
                $message->forceFill(['to' => '', 'params' => []])->save();
            });
        });
    }
}
