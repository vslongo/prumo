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
}
