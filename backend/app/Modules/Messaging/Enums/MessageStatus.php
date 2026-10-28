<?php

declare(strict_types=1);

namespace App\Modules\Messaging\Enums;

/**
 * queued → sent → delivered → read; failed a partir de queued ou sent.
 * Os status da Meta chegam fora de ordem (read antes de delivered): só se avança, nunca se volta.
 */
enum MessageStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';

    public function canMoveTo(self $next): bool
    {
        if ($next === self::Failed) {
            return in_array($this, [self::Queued, self::Sent], true);
        }

        return $this !== self::Failed && $next->rank() > $this->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Queued => 0,
            self::Sent => 1,
            self::Delivered => 2,
            self::Read => 3,
            self::Failed => 4,
        };
    }
}
