<?php

declare(strict_types=1);

use App\Modules\Auth\Enums\UserRole;
use App\Modules\Auth\Models\User;
use App\Modules\Invoicing\Actions\TransmitCancellation;
use App\Modules\Invoicing\Enums\InvoiceStatus;
use App\Modules\Invoicing\Jobs\TransmitCancellationJob;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Support\CancelEventXmlBuilder;
use App\Modules\Invoicing\Support\SefinClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

const CANCEL_KEY = '42054072211222333000181000000000004211234567890';

const CANCEL_EVENT_URL = SEFIN.'/nfse/'.CANCEL_KEY.'/eventos';

/** Nota autorizada (gravada direto, sem Sefin), com certificado válido. Devolve [dono, nota]. */
function authorizedInvoice(): array
{
    $owner = actingAsRole();
    fiscalReady();
    $order = deliveredOrder($owner);
    $invoice = Invoice::create([
        'service_order_id' => $order->id, 'requested_by' => $owner->id, 'environment' => 'restricted', 'dps_series' => '1',
        'dps_number' => 1, 'dps_id' => str_repeat('0', 45), 'amount_cents' => 25996, 'include_products' => true,
    ])->refresh();
    Storage::disk('r2')->put('invoices/old.pdf', '%PDF-');
    $invoice->transitionTo(InvoiceStatus::Processing);
    $invoice->transitionTo(InvoiceStatus::Authorized, ['access_key' => CANCEL_KEY, 'nfse_number' => '42', 'authorized_at' => now(), 'pdf_key' => 'invoices/old.pdf']);

    return [$owner, $invoice];
}

const CANCEL_BODY = ['reason' => '1', 'text' => 'Valor lançado errado na OS.'];

beforeEach(function () {
    Storage::fake('r2');
    config(['services.nfse.restricted.sefin_url' => SEFIN]);
});

it('cancela a nota autorizada com evento assinado e refaz o PDF', function () {
    [, $invoice] = authorizedInvoice();
    Http::fake([
        CANCEL_EVENT_URL.'/101101/1' => Http::response([], 404),
        CANCEL_EVENT_URL => Http::response(['eventoXmlGZipB64' => SefinClient::pack('<evento/>')], 201),
    ]);

    $this->postJson("/api/invoices/{$invoice->id}/cancel", CANCEL_BODY)->assertStatus(202)->assertJsonPath('data.status', 'canceled');

    $invoice->refresh();
    expect($invoice->canceled_at)->not->toBeNull()
        ->and($invoice->cancel_reason)->toBe('1: Valor lançado errado na OS.')
        ->and($invoice->pdf_key)->not->toBeNull()->not->toBe('invoices/old.pdf')
        ->and(Storage::disk('r2')->exists('invoices/old.pdf'))->toBeFalse();

    Http::assertSent(function (Request $r) {
        $xml = (string) SefinClient::unpack($r['pedidoRegistroEventoXmlGZipB64'] ?? null);

        return str_contains($xml, 'Id="'.CancelEventXmlBuilder::requestId(CANCEL_KEY).'"')
            && str_contains($xml, '<cMotivo>1</cMotivo>')
            && str_contains($xml, '<SignatureValue>');
    });
});

it('cancelamento recusado pela Sefin deixa a nota autorizada com o motivo', function () {
    [, $invoice] = authorizedInvoice();
    Http::fake([
        CANCEL_EVENT_URL.'/101101/1' => Http::response([], 404),
        CANCEL_EVENT_URL => Http::response(['erros' => [['codigo' => 'E1235', 'descricao' => 'Prazo de cancelamento encerrado.']]], 400),
    ]);

    $this->postJson("/api/invoices/{$invoice->id}/cancel", CANCEL_BODY)->assertStatus(202)
        ->assertJsonPath('data.status', 'authorized')
        ->assertJsonPath('data.error_code', 'E1235')
        ->assertJsonPath('data.error_message', 'Cancelamento recusado: Prazo de cancelamento encerrado.');
});

it('evento já registrado na Sefin só marca como cancelada (não reenvia)', function () {
    [, $invoice] = authorizedInvoice();
    $invoice->transitionTo(InvoiceStatus::CancelRequested, ['cancel_reason' => '1: Valor lançado errado na OS.']);
    Http::fake([CANCEL_EVENT_URL.'/101101/1' => Http::response(['ok' => true])]);

    app(TransmitCancellation::class)->handle($invoice->id);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Canceled);
    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST');
});

it('Sefin fora do ar no cancelamento: esgotadas as tentativas, a nota volta a autorizada', function () {
    Queue::fake();
    [, $invoice] = authorizedInvoice();

    $this->postJson("/api/invoices/{$invoice->id}/cancel", CANCEL_BODY)->assertStatus(202)->assertJsonPath('data.status', 'cancel_requested');
    Queue::assertPushedOn('fiscal', TransmitCancellationJob::class);

    (new TransmitCancellationJob($invoice->tenant_id, $invoice->id))->failed(null);
    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Authorized)->and($invoice->error_code)->toBe('SEFIN');
});

it('recusa cancelamento inválido', function (array $body, string $field) {
    [, $invoice] = authorizedInvoice();
    Http::fake();

    $this->postJson("/api/invoices/{$invoice->id}/cancel", $body)->assertUnprocessable()->assertJsonValidationErrors($field);
    Http::assertNothingSent();
})->with([
    'motivo curto' => [['reason' => '1', 'text' => 'errado'], 'text'],
    'código inexistente' => [['reason' => '5', 'text' => 'Valor lançado errado na OS.'], 'reason'],
]);

it('não cancela nota que não está autorizada', function () {
    [, $invoice] = authorizedInvoice();
    $invoice->transitionTo(InvoiceStatus::CancelRequested);

    $this->postJson("/api/invoices/{$invoice->id}/cancel", CANCEL_BODY)->assertUnprocessable()->assertJsonValidationErrors('invoice');
});

it('técnico não cancela nota', function () {
    [$owner, $invoice] = authorizedInvoice();
    actingAsRole(UserRole::Technician, User::query()->findOrFail($owner->id)->tenant);

    $this->postJson("/api/invoices/{$invoice->id}/cancel", CANCEL_BODY)->assertForbidden();
});
