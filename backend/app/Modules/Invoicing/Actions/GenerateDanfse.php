<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\Tenancy\Models\Tenant;
use App\Shared\Support\CpfCnpj;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Uso interno (listener de InvoiceAuthorized). PDF da nota (DANFSe) gerado pelo Prumo, com QR da consulta pública,
 * salvo no R2. Idempotente. ponytail: leiaute simples; conferir com o leiaute oficial do DANFSe quando houver.
 */
final class GenerateDanfse
{
    public const PUBLIC_QUERY_URL = 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=';

    /** `$replace`: gera de novo (ex.: nota cancelada) e só então apaga o PDF anterior. */
    public function handle(string $invoiceId, bool $replace = false): void
    {
        $invoice = Invoice::query()->find($invoiceId);
        if ($invoice === null || ($invoice->pdf_key !== null && ! $replace) || $invoice->access_key === null
            || ! in_array($invoice->status, [InvoiceStatus::Authorized, InvoiceStatus::CancelRequested, InvoiceStatus::Canceled], true)) {
            return;
        }

        $order = ServiceOrder::query()->with(['items', 'customer', 'vehicle'])->findOrFail($invoice->service_order_id);
        $profile = FiscalProfile::query()->firstOrFail();
        $url = self::PUBLIC_QUERY_URL.$invoice->access_key;

        $html = view('invoicing.danfse', [
            'invoice' => $invoice,
            'provider' => ['name' => Tenant::query()->findOrFail($invoice->tenant_id)->name, 'cnpj' => $profile->cnpj, 'im' => $profile->municipal_registration],
            'taker' => ['name' => $order->customer->name ?? '', 'document' => CpfCnpj::normalize((string) $order->customer?->document)],
            'description' => TransmitDps::description($order, $invoice->include_products),
            'queryUrl' => $url,
            'qr' => (new QRCode(new QROptions(['outputBase64' => true, 'scale' => 4])))->render($url),
        ])->render();

        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        $key = 'invoices/'.Str::uuid7().'.pdf';
        Storage::disk('r2')->put($key, (string) $dompdf->output(), ['ContentType' => 'application/pdf']);
        $old = $invoice->pdf_key;
        $invoice->forceFill(['pdf_key' => $key])->save();
        if ($old !== null) {
            Storage::disk('r2')->delete($old);
        }
    }
}
