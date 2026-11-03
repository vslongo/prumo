<?php

declare(strict_types=1);

namespace App\Modules\Billing\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Chamadas à API v3 do Asaas. Só usado em jobs (nunca na request do usuário). */
final class AsaasClient
{
    public function createCustomer(string $name, string $cpfCnpj, string $email, string $externalReference): string
    {
        return (string) $this->http()->post('customers', [
            'name' => $name,
            'cpfCnpj' => $cpfCnpj,
            'email' => $email,
            'externalReference' => $externalReference,
            'notificationDisabled' => false,
        ])->json('id');
    }

    /** Assinatura mensal com forma de pagamento "a escolher" (Pix, boleto ou cartão na fatura do Asaas). */
    public function createSubscription(string $customerId, int $valueReais, string $nextDueDate, string $description, string $externalReference): string
    {
        return (string) $this->http()->post('subscriptions', [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED',
            'cycle' => 'MONTHLY',
            'value' => $valueReais,
            'nextDueDate' => $nextDueDate,
            'description' => $description,
            'externalReference' => $externalReference,
        ])->json('id');
    }

    public function updateSubscriptionValue(string $subscriptionId, int $valueReais, string $description): void
    {
        $this->http()->post("subscriptions/{$subscriptionId}", [
            'value' => $valueReais,
            'description' => $description,
            'updatePendingPayments' => true,
        ]);
    }

    public function deleteSubscription(string $subscriptionId): void
    {
        $this->http()->delete("subscriptions/{$subscriptionId}");
    }

    /** Link da fatura em aberto mais antiga (onde a oficina paga). */
    public function pendingInvoiceUrl(string $subscriptionId): ?string
    {
        $url = $this->http()->get("subscriptions/{$subscriptionId}/payments", ['status' => 'PENDING'])->json('data.0.invoiceUrl');

        return is_string($url) ? $url : null;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.asaas.base_url'), '/'))
            ->withHeaders(['access_token' => (string) config('services.asaas.api_key'), 'User-Agent' => 'Prumo'])
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->throw();
    }
}
