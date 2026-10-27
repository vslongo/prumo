<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Http\Controllers;

use App\Modules\Quotes\Actions\ApproveQuote;
use App\Modules\Quotes\Actions\OpenPublicQuote;
use App\Modules\Quotes\Actions\RejectQuote;
use App\Modules\Quotes\Actions\RequestSignatureUpload;
use App\Modules\Quotes\Data\ApproveQuoteData;
use App\Modules\Quotes\Http\Requests\ApproveQuoteRequest;
use App\Modules\Quotes\Http\Requests\RejectQuoteRequest;
use App\Modules\Quotes\Http\Requests\SignatureUploadRequest;
use App\Modules\Quotes\Http\Resources\PublicQuoteResource;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Rotas do link enviado ao cliente. Sem login; o token resolve orçamento e tenant. */
final class PublicQuoteController
{
    public function show(Request $request, OpenPublicQuote $open): PublicQuoteResource
    {
        return new PublicQuoteResource($open->handle($this->quote($request))->load(['items', 'customer', 'vehicle', 'tenant']));
    }

    public function signatureUploadUrl(SignatureUploadRequest $request, RequestSignatureUpload $requestUpload): JsonResponse
    {
        return response()->json(['data' => $requestUpload->handle($this->quote($request), $request->integer('size'))]);
    }

    public function approve(ApproveQuoteRequest $request, ApproveQuote $approve): JsonResponse
    {
        $approve->handle($this->quote($request), new ApproveQuoteData(
            signerName: $request->string('signer_name')->toString(),
            itemPositions: array_values(array_map('intval', (array) $request->input('items'))),
            ip: (string) $request->ip(),
            userAgent: $request->userAgent(),
        ));

        return response()->json(['message' => 'Orçamento aprovado. A oficina já foi avisada.']);
    }

    public function reject(RejectQuoteRequest $request, RejectQuote $reject): JsonResponse
    {
        $reject->handle($this->quote($request), $request->validated('reason'));

        return response()->json(['message' => 'Orçamento recusado.']);
    }

    private function quote(Request $request): Quote
    {
        return $request->attributes->get('quote');
    }
}
