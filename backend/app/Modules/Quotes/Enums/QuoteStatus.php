<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Viewed = 'viewed';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    /** Editável: rascunho direto; enviado/visto vira nova versão e invalida o link. */
    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Viewed], true);
    }

    public function canBeSent(): bool
    {
        return in_array($this, [self::Draft, self::Sent, self::Viewed, self::Expired], true);
    }

    public function awaitsCustomer(): bool
    {
        return in_array($this, [self::Sent, self::Viewed], true);
    }
}
