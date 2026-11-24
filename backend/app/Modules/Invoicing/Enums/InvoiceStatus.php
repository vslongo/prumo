<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

/**
 * pending → processing → authorized → (cancel_requested →) canceled
 * processing → error → processing (nova tentativa, mesmo número de DPS).
 */
enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Authorized = 'authorized';
    case Error = 'error';
    case CancelRequested = 'cancel_requested';
    case Canceled = 'canceled';

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Pending => [self::Processing],
            self::Processing => [self::Authorized, self::Error],
            self::Error => [self::Processing],
            self::Authorized => [self::CancelRequested],
            // Cancelamento recusado pela Sefin: a nota continua autorizada.
            self::CancelRequested => [self::Canceled, self::Authorized],
            self::Canceled => [],
        }, true);
    }

    /** Ainda conta como "a nota da OS" (bloqueia emitir outra). */
    public function isActive(): bool
    {
        return $this !== self::Canceled;
    }
}
