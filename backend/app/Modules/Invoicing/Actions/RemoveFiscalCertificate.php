<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\DB;

/** Apaga o certificado A1 e a senha (revogação/LGPD). Sem certificado, a oficina não emite nota. Idempotente. */
final class RemoveFiscalCertificate
{
    public function __construct(private readonly DeleteMedia $deleteMedia) {}

    public function handle(): ?FiscalProfile
    {
        $mediaId = null;
        $profile = DB::transaction(function () use (&$mediaId) {
            $profile = FiscalProfile::query()->lockForUpdate()->first();
            if ($profile === null || $profile->certificate_media_id === null) {
                return $profile;
            }
            $mediaId = $profile->certificate_media_id;
            $profile->forceFill(FiscalProfile::NO_CERTIFICATE)->save();

            return $profile;
        });

        $media = $mediaId !== null ? Media::query()->find($mediaId) : null;
        if ($media !== null) {
            $this->deleteMedia->handle($media);
        }

        return $profile;
    }
}
