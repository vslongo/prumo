<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

/** Motivo do evento de cancelamento da NFS-e (código `cMotivo`). */
enum CancelReason: string
{
    case IssueError = '1';
    case ServiceNotProvided = '2';
    case Other = '9';
}
