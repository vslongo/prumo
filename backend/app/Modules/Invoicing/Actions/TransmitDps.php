<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Events\InvoiceAuthorized;
use App\Modules\Invoicing\Events\InvoiceFailed;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Support\DpsXmlBuilder;
use App\Modules\Invoicing\Support\FiscalCertificate;
use App\Modules\Invoicing\Support\InvalidCertificate;
use App\Modules\Invoicing\Support\SefinClient;
use App\Modules\Invoicing\Support\Taker;
use App\Modules\Invoicing\Support\XmlSigner;
use App\Modules\ServiceOrders\Models\ServiceOrder;
use App\Modules\ServiceOrders\Models\ServiceOrderItem;
use App\Shared\Support\CpfCnpj;
use DOMDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Uso interno (TransmitDpsJob). Envia a DPS à Sefin e grava o resultado. Idempotente:
 * se a DPS já foi enviada antes (`submitted_at`), consulta a Sefin primeiro e só reenvia se ela não recebeu.
 * Falha de rede/servidor sobe como exceção para a fila tentar de novo.
 */
final class TransmitDps
{
    public function __construct(
        private readonly DpsXmlBuilder $builder,
        private readonly XmlSigner $signer,
    ) {}

    public function handle(string $invoiceId): void
    {
        $invoice = Invoice::query()->find($invoiceId);
        if ($invoice === null || ! in_array($invoice->status, [InvoiceStatus::Pending, InvoiceStatus::Processing, InvoiceStatus::Error], true)) {
            return;
        }
        if ($invoice->status !== InvoiceStatus::Processing) {
            $invoice->transitionTo(InvoiceStatus::Processing, ['error_code' => null, 'error_message' => null]);
        }

        $profile = FiscalProfile::query()->firstOrFail();
        try {
            $certificate = FiscalCertificate::load($profile);
        } catch (InvalidCertificate $e) {
            $this->fail($invoice, 'CERT', $e->getMessage());

            return;
        }

        $order = ServiceOrder::query()->with(['items', 'customer', 'vehicle'])->findOrFail($invoice->service_order_id);
        $customer = $order->customer;
        if ($customer === null || $customer->anonymized_at !== null) {
            $this->fail($invoice, 'CLIENTE', 'O cliente da OS foi removido ou anonimizado.');

            return;
        }
        $taker = new Taker(CpfCnpj::normalize((string) $customer->document), $customer->name, $customer->email);
        $sefin = new SefinClient($invoice->environment, $certificate);

        if ($invoice->submitted_at !== null && ($key = $sefin->findAccessKey($invoice->dps_id)) !== null) {
            $this->recover($invoice, $taker, $key, $sefin);

            return;
        }

        $doc = $this->builder->build($profile, $invoice, $taker, self::description($order, $invoice->include_products), now());
        $xml = (string) $this->signer->sign($doc, 'infDPS', $certificate->certPem, $certificate->keyPem)->saveXML();

        $invoice->forceFill(['submitted_at' => now()])->save();
        $result = $sefin->submit($xml);

        if ($result->accepted && $result->accessKey !== null) {
            $this->authorize($invoice, $result->accessKey, $result->xml ?? $sefin->fetchNfse($result->accessKey));
        } elseif ($result->errorCode === SefinClient::DUPLICATE_DPS && ($key = $sefin->findAccessKey($invoice->dps_id)) !== null) {
            $this->recover($invoice, $taker, $key, $sefin);
        } else {
            $this->fail($invoice, (string) $result->errorCode, (string) $result->errorMessage);
        }
    }

    /**
     * A Sefin já tem uma NFS-e com este número de DPS. Só é desta OS se tomador e valor batem: a oficina pode ter
     * usado o mesmo número em outro sistema (ou o Emissor Nacional) antes do Prumo.
     */
    private function recover(Invoice $invoice, Taker $taker, string $accessKey, SefinClient $sefin): void
    {
        $xml = $sefin->fetchNfse($accessKey);
        $doc = new DOMDocument;
        $toma = @$doc->loadXML($xml) ? $doc->getElementsByTagName('toma')->item(0) : null;
        $value = $doc->getElementsByTagName('vServ')->item(0)?->textContent;

        if ($toma === null || ! str_contains($toma->textContent, $taker->document) || $value !== DpsXmlBuilder::decimal($invoice->amount_cents)) {
            $this->fail($invoice, 'NUMERO_EM_USO', "O número {$invoice->dps_number} da série {$invoice->dps_series} já foi usado em outra nota. "
                .'Ajuste o próximo número da DPS em Configurações → Nota fiscal e emita de novo.');

            return;
        }

        $this->authorize($invoice, $accessKey, $xml);
    }

    private function authorize(Invoice $invoice, string $accessKey, string $nfseXml): void
    {
        if (preg_match('/^\d{50}$/', $accessKey) !== 1) {
            throw new RuntimeException('Sefin devolveu chave de acesso inválida.');
        }
        $xmlKey = 'invoices/'.Str::uuid7().'.xml';
        Storage::disk('r2')->put($xmlKey, $nfseXml, ['ContentType' => 'application/xml']);

        $invoice->transitionTo(InvoiceStatus::Authorized, [
            'access_key' => $accessKey,
            'nfse_number' => self::nfseNumber($nfseXml),
            'xml_key' => $xmlKey,
            'authorized_at' => now(),
            'error_code' => null,
            'error_message' => null,
        ]);
        InvoiceAuthorized::dispatch($invoice->tenant_id, $invoice->id);
    }

    private function fail(Invoice $invoice, string $code, string $message): void
    {
        $invoice->transitionTo(InvoiceStatus::Error, ['error_code' => $code, 'error_message' => $message]);
        InvoiceFailed::dispatch($invoice->tenant_id, $invoice->id);
    }

    /** Discriminação: OS, placa e um item por linha. */
    public static function description(ServiceOrder $order, bool $includeProducts): string
    {
        $lines = ['OS nº '.$order->number.($order->vehicle !== null ? ' · Placa '.$order->vehicle->plate_normalized : '')];
        foreach (IssueServiceInvoice::billableItems($order, $includeProducts) as $item) {
            /** @var ServiceOrderItem $item */
            $qty = str_replace('.', ',', rtrim(rtrim($item->quantity, '0'), '.'));
            $lines[] = "{$qty} x {$item->description}: R$ ".number_format(intdiv($item->total_cents, 100), 0, ',', '.').','.sprintf('%02d', $item->total_cents % 100);
        }

        return implode("\n", $lines);
    }

    private static function nfseNumber(string $xml): ?string
    {
        $doc = new DOMDocument;

        return @$doc->loadXML($xml) ? ($doc->getElementsByTagName('nNFSe')->item(0)?->textContent ?: null) : null;
    }
}
