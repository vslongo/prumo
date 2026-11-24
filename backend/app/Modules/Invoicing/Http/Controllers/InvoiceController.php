<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Http\Controllers;

use App\Modules\Invoicing\Actions\IssueServiceInvoice;
use App\Modules\Invoicing\Http\Resources\InvoiceResource;
use App\Modules\Invoicing\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/** Notas fiscais de uma OS. Todos veem; emitir é de quem cuida do financeiro (não técnico). */
final class InvoiceController
{
    public function index(string $serviceOrder): AnonymousResourceCollection
    {
        return InvoiceResource::collection(Invoice::query()->where('service_order_id', $serviceOrder)->latest()->get());
    }

    public function store(Request $request, string $serviceOrder, IssueServiceInvoice $issue): JsonResponse
    {
        Gate::authorize('issue-invoice');

        return (new InvoiceResource($issue->handle($serviceOrder, (string) $request->user()?->getAuthIdentifier())))
            ->response()->setStatusCode(202);
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice);
    }

    /** URL assinada curta para baixar o XML ou o PDF (bucket privado). */
    public function file(Invoice $invoice, string $type): JsonResponse
    {
        $key = $type === 'pdf' ? $invoice->pdf_key : $invoice->xml_key;
        abort_if($key === null, 404);

        return response()->json(['data' => ['url' => Storage::disk('r2')->temporaryUrl($key, now()->addMinutes(5))]]);
    }
}
