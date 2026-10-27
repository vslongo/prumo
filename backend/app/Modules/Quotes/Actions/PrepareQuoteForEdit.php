<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Actions;

use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * Uso interno do módulo, sempre dentro de transação. Relê o orçamento com lock (o cliente pode estar
 * aprovando agora) e só então decide. Rascunho edita direto; enviado/visto ganha nova versão,
 * volta para rascunho e o link anterior deixa de valer.
 */
final class PrepareQuoteForEdit
{
    public function handle(Quote $quote): void
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('PrepareQuoteForEdit exige transação.');
        }

        $fresh = Quote::query()->lockForUpdate()->findOrFail($quote->id);
        $quote->setRawAttributes($fresh->getAttributes(), true);

        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages(['quote' => 'Orçamento aprovado, recusado ou expirado não pode ser editado.']);
        }

        if ($quote->status === QuoteStatus::Draft) {
            return;
        }

        $quote->update([
            'version' => $quote->version + 1,
            'status' => QuoteStatus::Draft,
            'token_hash' => null,
            'token_expires_at' => null,
            'sent_at' => null,
            'viewed_at' => null,
        ]);
    }
}
