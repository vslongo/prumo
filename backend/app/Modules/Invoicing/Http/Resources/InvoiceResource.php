<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Http\Resources;

use App\Modules\Invoicing\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invoice */
final class InvoiceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_order_id' => $this->service_order_id,
            'status' => $this->status->value,
            'environment' => $this->environment->value,
            'dps_series' => $this->dps_series,
            'dps_number' => $this->dps_number,
            'access_key' => $this->access_key,
            'nfse_number' => $this->nfse_number,
            'amount_cents' => $this->amount_cents,
            'include_products' => $this->include_products,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'has_xml' => $this->xml_key !== null,
            'has_pdf' => $this->pdf_key !== null,
            'authorized_at' => $this->authorized_at?->toIso8601String(),
            'canceled_at' => $this->canceled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
