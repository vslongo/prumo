<?php

declare(strict_types=1);

namespace App\Modules\ServiceOrders\Enums;

/** open → in_progress → ready → delivered → closed; canceled a partir de qualquer estado antes de delivered. */
enum ServiceOrderStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Closed = 'closed';
    case Canceled = 'canceled';

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, match ($this) {
            self::Open => [self::InProgress, self::Canceled],
            self::InProgress => [self::Ready, self::Canceled],
            self::Ready => [self::Delivered, self::Canceled],
            self::Delivered => [self::Closed],
            self::Closed, self::Canceled => [],
        }, true);
    }

    /** Itens podem ser adicionados e marcados como feitos enquanto o serviço não ficou pronto. */
    public function acceptsItemChanges(): bool
    {
        return in_array($this, [self::Open, self::InProgress], true);
    }
}
