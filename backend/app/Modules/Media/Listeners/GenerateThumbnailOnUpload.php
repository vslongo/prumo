<?php

declare(strict_types=1);

namespace App\Modules\Media\Listeners;

use App\Modules\Media\Actions\GenerateThumbnail;
use App\Modules\Media\Events\MediaUploaded;
use App\Modules\Tenancy\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

/** MediaUploaded → miniatura. Em fila, restaura o tenant; a Action é idempotente. */
final class GenerateThumbnailOnUpload implements ShouldQueueAfterCommit
{
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(private readonly GenerateThumbnail $generate) {}

    public function handle(MediaUploaded $event): void
    {
        TenantContext::run($event->tenantId, fn () => $this->generate->handle($event->mediaId));
    }
}
