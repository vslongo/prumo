<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Listeners;

use App\Modules\Messaging\Actions\FindConsentingRecipient;
use App\Modules\Messaging\Actions\QueueWhatsappMessage;
use App\Modules\Messaging\Data\OutboundMessageData;
use App\Modules\Messaging\Enums\MessageTemplate;
use App\Modules\ServiceOrders\Enums\ServiceOrderStatus;
use App\Modules\ServiceOrders\Events\ServiceOrderStatusChanged;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/** OS foi para `ready` → avisa o cliente. Um aviso por OS, mesmo que ela volte e fique pronta de novo. */
final class QueueServiceOrderReadyMessage implements ShouldQueueAfterCommit
{
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(
        private readonly FindConsentingRecipient $recipient,
        private readonly QueueWhatsappMessage $queue,
    ) {}

    public function handle(ServiceOrderStatusChanged $event): void
    {
        if ($event->to !== ServiceOrderStatus::Ready) {
            return;
        }

        TenantContext::run($event->tenantId, function () use ($event) {
            $order = ServiceOrder::query()->with(['customer', 'vehicle'])->find($event->serviceOrderId);
            $to = $this->recipient->handle($order?->customer);
            if ($order === null || $to === null) {
                return;
            }

            $vehicle = $order->vehicle;
            $label = $vehicle === null ? 'seu veículo' : implode(' ', array_filter([$vehicle->brand, $vehicle->model, $vehicle->plate_normalized]));

            $this->queue->handle(new OutboundMessageData(
                tenantId: $event->tenantId,
                template: MessageTemplate::ServiceOrderReady,
                to: $to['to'],
                params: [$to['name'], $label, (string) Tenant::query()->whereKey($event->tenantId)->value('name')],
                subjectType: 'service_order',
                subjectId: $order->id,
                idempotencyKey: 'service_order_ready:'.$order->id,
                customerId: $to['customer_id'],
            ));
        });
    }
}
