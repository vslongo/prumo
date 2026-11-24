<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Media\Actions\DeleteMedia;
use App\Modules\Media\Models\Media;
use Illuminate\Support\Facades\DB;

/** Cria ou atualiza o perfil fiscal da oficina. Trocar o CNPJ descarta o certificado, que era do CNPJ antigo. */
final class UpdateFiscalProfile
{
    public function __construct(private readonly DeleteMedia $deleteMedia) {}

    /** @param  array<string, mixed>  $data  já validado pelo FormRequest */
    public function handle(array $data): FiscalProfile
    {
        $stale = null;

        $profile = DB::transaction(function () use ($data, &$stale) {
            $profile = FiscalProfile::query()->lockForUpdate()->first() ?? new FiscalProfile;

            if ($profile->exists && $profile->cnpj !== $data['cnpj'] && $profile->certificate_media_id !== null) {
                $stale = Media::query()->find($profile->certificate_media_id);
                $profile->forceFill(FiscalProfile::NO_CERTIFICATE);
            }

            $profile->fill($data)->save();

            return $profile;
        });

        if ($stale !== null) {
            $this->deleteMedia->handle($stale);
        }

        return $profile;
    }
}
