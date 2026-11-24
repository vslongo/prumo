<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Http\Controllers;

use App\Modules\Invoicing\Actions\ConfirmFiscalCertificate;
use App\Modules\Invoicing\Actions\RemoveFiscalCertificate;
use App\Modules\Invoicing\Actions\RequestCertificateUpload;
use App\Modules\Invoicing\Actions\UpdateFiscalProfile;
use App\Modules\Invoicing\Http\Requests\UpdateFiscalProfileRequest;
use App\Modules\Invoicing\Http\Resources\FiscalProfileResource;
use App\Modules\Invoicing\Models\FiscalProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Perfil fiscal e certificado A1 da oficina. Só owner/admin. */
final class FiscalProfileController
{
    public function show(): JsonResponse
    {
        Gate::authorize('manage-fiscal');
        $profile = FiscalProfile::query()->first();

        return response()->json(['data' => $profile === null ? null : new FiscalProfileResource($profile)]);
    }

    public function update(UpdateFiscalProfileRequest $request, UpdateFiscalProfile $update): JsonResponse
    {
        Gate::authorize('manage-fiscal');

        return (new FiscalProfileResource($update->handle($request->validated())))->response()->setStatusCode(200);
    }

    public function requestCertificateUpload(Request $request, RequestCertificateUpload $requestUpload): JsonResponse
    {
        Gate::authorize('manage-fiscal');
        $data = $request->validate(['size' => ['required', 'integer', 'min:1']]);

        return response()->json(['data' => $requestUpload->handle((int) $data['size'])]);
    }

    public function confirmCertificate(Request $request, ConfirmFiscalCertificate $confirm): FiscalProfileResource
    {
        Gate::authorize('manage-fiscal');
        $data = $request->validate([
            'media_id' => ['required', 'uuid'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        return new FiscalProfileResource($confirm->handle($data['media_id'], $data['password']));
    }

    public function removeCertificate(RemoveFiscalCertificate $remove): JsonResponse
    {
        Gate::authorize('manage-fiscal');
        $profile = $remove->handle();

        return response()->json(['data' => $profile === null ? null : new FiscalProfileResource($profile)]);
    }
}
