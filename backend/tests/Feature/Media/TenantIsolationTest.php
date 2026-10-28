<?php

declare(strict_types=1);

use App\Modules\Media\Actions\FindLatestOwnerMedia;
use App\Modules\Media\Actions\RequestUploadUrl;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\Storage;

it('não enxerga mídia de outro tenant, nem pelo dono', function () {
    Storage::fake('r2');
    actingAsRole();
    $ownerId = (string) str()->uuid();
    $foreign = inOtherTenant(fn () => app(RequestUploadUrl::class)->handle('service_order', $ownerId, 'image/jpeg', 1000)['media']);

    expect(Media::query()->find($foreign->id))->toBeNull()
        ->and(app(FindLatestOwnerMedia::class)->handle('service_order', $ownerId))->toBeNull();
});
