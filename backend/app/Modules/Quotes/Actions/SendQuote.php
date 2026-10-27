<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gera link público novo (token ≥ 32 bytes, guardado só o hash) e marca como enviado.
 * Reenviar gera token novo e invalida o anterior. O envio por WhatsApp é feito pelo Messaging ao ouvir QuoteSent;
 * o retorno traz o link e o fallback wa.me.
 *
 * @phpstan-type SentQuote array{quote: Quote, link: string, whatsapp_url: string|null, expires_at: string}
 */
final class SendQuote
{
    public const LINK_TTL_DAYS = 7;

    /** @return SentQuote */
    public function handle(Quote $quote): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addDays(self::LINK_TTL_DAYS);
        $link = rtrim((string) config('app.frontend_url'), '/').'/o/'.$token;

        $quote = DB::transaction(function () use ($quote, $token, $expiresAt, $link) {
            // Relê com lock: o cliente pode estar aprovando o link anterior agora.
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);

            if (! $quote->status->canBeSent()) {
                throw ValidationException::withMessages(['quote' => 'Esse orçamento não pode mais ser enviado.']);
            }
            if (! $quote->items()->exists()) {
                throw ValidationException::withMessages(['quote' => 'Adicione ao menos um item antes de enviar.']);
            }

            $quote->update([
                'status' => QuoteStatus::Sent,
                'token_hash' => hash('sha256', $token),
                'token_expires_at' => $expiresAt,
                'sent_at' => now(),
                'viewed_at' => null,
            ]);

            QuoteSent::dispatch($quote->tenant_id, $quote->id, $link);

            return $quote;
        });

        return [
            'quote' => $quote,
            'link' => $link,
            'whatsapp_url' => $this->whatsappUrl($quote, $link),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    /** Fallback sem API: o técnico abre o WhatsApp com o texto pronto. */
    private function whatsappUrl(Quote $quote, string $link): ?string
    {
        $customer = $quote->customer;
        $phone = $customer?->phone;
        if ($phone === null || $phone === '') {
            return null;
        }

        $phone = strlen($phone) <= 11 ? '55'.$phone : $phone;
        $text = sprintf('Olá, %s! Segue o orçamento do seu veículo. Para ver e aprovar, acesse: %s', $customer->name, $link);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($text);
    }
}
