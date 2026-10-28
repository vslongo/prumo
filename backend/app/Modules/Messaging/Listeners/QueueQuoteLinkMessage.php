<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Listeners;

use App\Modules\Messaging\Actions\FindConsentingRecipient;
use App\Modules\Messaging\Actions\QueueWhatsappMessage;
use App\Modules\Messaging\Data\OutboundMessageData;
use App\Modules\Messaging\Enums\MessageTemplate;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/** QuoteSent → WhatsApp com o link. Reenviar gera link novo, então a chave inclui o link: um envio por link. */
final class QueueQuoteLinkMessage implements ShouldQueueAfterCommit
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(
        private readonly FindConsentingRecipient $recipient,
        private readonly QueueWhatsappMessage $queue,
    ) {}

    public function handle(QuoteSent $event): void
    {
        TenantContext::run($event->tenantId, function () use ($event) {
            $quote = Quote::query()->with('customer')->find($event->quoteId);
            $to = $this->recipient->handle($quote?->customer);
            if ($quote === null || $to === null) {
                return;
            }

            $this->queue->handle(new OutboundMessageData(
                tenantId: $event->tenantId,
                template: MessageTemplate::QuoteLink,
                to: $to['to'],
                params: [$to['name'], (string) Tenant::query()->whereKey($event->tenantId)->value('name'), $event->link],
                subjectType: 'quote',
                subjectId: $quote->id,
                idempotencyKey: 'quote_link:'.$quote->id.':'.hash('sha256', $event->link),
                customerId: $to['customer_id'],
            ));
        });
    }
}
