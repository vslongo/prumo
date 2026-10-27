<?php

declare(strict_types=1);

namespace App\Modules\Media\Enums;

enum MediaStatus: string
{
    case Pending = 'pending';
    case Uploaded = 'uploaded';
    case Failed = 'failed';
}
