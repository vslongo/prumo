<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Http\Controllers;

use App\Modules\Invoicing\Actions\CancelServiceInvoice;
use App\Modules\Invoicing\Actions\IssueServiceInvoice;
use App\Modules\Invoicing\Enums\CancelReason;
use App\Modules\Invoicing\Http\Resources\InvoiceResource;
use App\Modules\Invoicing\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

    public function cancel(Request $request, Invoice $invoice, CancelServiceInvoice $cancel): JsonResponse
    {
        Gate::authorize('issue-invoice');
        $data = $request->validate([
            'reason' => ['required', Rule::enum(CancelReason::class)],
            'text' => ['required', 'string', 'min:15', 'max:255'],
        ]);

        return (new InvoiceResource($cancel->handle($invoice->id, CancelReason::from($data['reason']), $data['text'])))
            ->response()->setStatusCode(202);
    }

    /** URL assinada curta para baixar o XML ou o PDF (bucket privado). */
    public function file(Invoice $invoice, string $type): JsonResponse
    {
        // XML/PDF têm CPF do cliente e valores: técnico não baixa (não acessa financeiro).
        Gate::authorize('issue-invoice');
        $key = $type === 'pdf' ? $invoice->pdf_key : $invoice->xml_key;
        abort_if($key === null, 404);

        return response()->json(['data' => ['url' => Storage::disk('r2')->temporaryUrl($key, now()->addMinutes(5))]]);
    }
}
