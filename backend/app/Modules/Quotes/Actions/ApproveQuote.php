<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Media\Actions\ConfirmUpload;
use App\Modules\Media\Actions\FindLatestOwnerMedia;
use App\Modules\Quotes\Data\ApprovedQuoteData;
use App\Modules\Quotes\Data\ApproveQuoteData;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Events\QuoteApproved;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Cliente aprova (total ou parcial) e assina. Grava `quote_approvals` imutável e dispara QuoteApproved,
 * que gera a OS automaticamente.
 */
final class ApproveQuote
{
    public function __construct(
        private readonly OpenPublicQuote $open,
        private readonly FindLatestOwnerMedia $findMedia,
        private readonly ConfirmUpload $confirmUpload,
    ) {}

    public function handle(Quote $quote, ApproveQuoteData $data): QuoteApproval
    {
        $this->open->handle($quote);
        $this->ensureAwaitsCustomer($quote);

        // Conferência no R2 fica fora da transação para não segurar o lock esperando rede.
        $signature = $this->findMedia->handle(RequestSignatureUpload::OWNER_TYPE, $quote->id, $quote->sent_at);
        if ($signature === null) {
            throw ValidationException::withMessages(['signature' => 'A assinatura é obrigatória.']);
        }
        $this->confirmUpload->handle($signature);

        return DB::transaction(function () use ($quote, $data, $signature) {
            $sentAt = $quote->sent_at;
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            $this->ensureAwaitsCustomer($quote);

            // O técnico reenviou/editou enquanto o cliente assinava: a assinatura era de outro envio.
            if ($quote->sent_at?->getTimestamp() !== $sentAt?->getTimestamp()) {
                throw ValidationException::withMessages(['quote' => 'O orçamento mudou. Abra o link novo enviado pela oficina.']);
            }

            $positions = array_values(array_unique($data->itemPositions));
            $items = $quote->items()->whereIn('position', $positions)->get();
            if ($positions === [] || $items->count() !== count($positions)) {
                throw ValidationException::withMessages(['items' => 'Selecione ao menos um item válido para aprovar.']);
            }

            $approval = QuoteApproval::create([
                'quote_id' => $quote->id,
                'quote_version' => $quote->version,
                'signer_name' => $data->signerName,
                'signature_media_id' => $signature->id,
                'signature_key' => $signature->r2_key,
                'approved_item_ids' => $items->pluck('id')->all(),
                'ip' => $data->ip,
                'user_agent' => $data->userAgent !== null ? Str::limit($data->userAgent, 500, '') : null,
                'approved_at' => now(),
            ]);

            $quote->update(['status' => QuoteStatus::Approved, 'decided_at' => now()]);

            QuoteApproved::dispatch(ApprovedQuoteData::fromQuote($quote, $items));

            return $approval;
        });
    }

    private function ensureAwaitsCustomer(Quote $quote): void
    {
        if (! $quote->status->awaitsCustomer()) {
            throw ValidationException::withMessages(['quote' => 'Este orçamento não está aguardando aprovação.']);
        }
    }
}
