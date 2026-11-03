<?php

declare(strict_types=1);

namespace App\Modules\Billing\Jobs;

use App\Modules\Billing\Support\AsaasClient;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;

/** Remove a assinatura no Asaas (para as cobranças futuras). Já removida (404) conta como feito. */
final class CancelAsaasSubscriptionJob implements ShouldBeUnique, ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(public readonly string $asaasSubscriptionId) {}

    public function uniqueId(): string
    {
        return $this->asaasSubscriptionId;
    }

    public function handle(AsaasClient $asaas): void
    {
        try {
            $asaas->deleteSubscription($this->asaasSubscriptionId);
        } catch (RequestException $e) {
            if ($e->response->status() !== 404) {
                throw $e;
            }
        }
    }
}
