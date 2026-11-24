<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Invoicing\Enums\CancelReason;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Support\CancelEventXmlBuilder;
use App\Modules\Invoicing\Support\FiscalCertificate;
use App\Modules\Invoicing\Support\InvalidCertificate;
use App\Modules\Invoicing\Support\SefinClient;
use App\Modules\Invoicing\Support\XmlSigner;

/**
 * Uso interno (TransmitCancellationJob). Registra o evento de cancelamento na Sefin. Idempotente: consulta antes
 * se o evento já existe. Recusado → a nota volta a `authorized` com o motivo da recusa.
 */
final class TransmitCancellation
{
    public function __construct(
        private readonly CancelEventXmlBuilder $builder,
        private readonly XmlSigner $signer,
        private readonly GenerateDanfse $danfse,
    ) {}

    public function handle(string $invoiceId): void
    {
        $invoice = Invoice::query()->find($invoiceId);
        if ($invoice === null || $invoice->status !== InvoiceStatus::CancelRequested || $invoice->access_key === null) {
            return;
        }

        $profile = FiscalProfile::query()->firstOrFail();
        try {
            $certificate = FiscalCertificate::load($profile);
        } catch (InvalidCertificate $e) {
            $this->refuse($invoice, 'CERT', $e->getMessage());

            return;
        }

        $sefin = new SefinClient($invoice->environment, $certificate);
        $key = (string) $invoice->access_key;

        if ($sefin->hasEvent($key, CancelEventXmlBuilder::EVENT)) {
            $this->cancel($invoice);

            return;
        }

        [$code, $text] = explode(': ', (string) $invoice->cancel_reason, 2) + [1 => ''];
        $doc = $this->builder->build($profile, $invoice, CancelReason::from($code), $text, now());
        $xml = (string) $this->signer->sign($doc, 'infPedReg', $certificate->certPem, $certificate->keyPem)->saveXML();
        $result = $sefin->registerEvent($key, $xml);

        if ($result->accepted) {
            $this->cancel($invoice);
        } else {
            $this->refuse($invoice, (string) $result->errorCode, (string) $result->errorMessage);
        }
    }

    private function cancel(Invoice $invoice): void
    {
        $invoice->transitionTo(InvoiceStatus::Canceled, ['canceled_at' => now()]);
        // PDF de novo, agora com a marca de cancelada; o antigo só sai depois que o novo existe.
        $this->danfse->handle($invoice->id, replace: true);
    }

    private function refuse(Invoice $invoice, string $code, string $message): void
    {
        $invoice->transitionTo(InvoiceStatus::Authorized, ['error_code' => $code, 'error_message' => 'Cancelamento recusado: '.$message]);
    }
}
