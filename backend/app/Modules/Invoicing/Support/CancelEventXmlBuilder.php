<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use App\Modules\Invoicing\Enums\CancelReason;
use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Carbon;

/**
 * Pedido de registro do evento de cancelamento (e101101) da NFS-e Nacional.
 * ponytail: como a DPS, montado sem os XSD oficiais; conferir com o Anexo II e na homologação.
 */
final class CancelEventXmlBuilder
{
    public const EVENT = '101101';

    /** Id: "PRE" + chave de acesso (50) + tipo do evento (6) + número do pedido (3). */
    public static function requestId(string $accessKey, int $sequence = 1): string
    {
        return 'PRE'.$accessKey.self::EVENT.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    public function build(FiscalProfile $profile, Invoice $invoice, CancelReason $reason, string $text, Carbon $now): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->appendChild($doc->createElementNS(DpsXmlBuilder::NS, 'pedRegEvento'));
        assert($root instanceof DOMElement);
        $root->setAttribute('versao', DpsXmlBuilder::VERSION);

        $inf = $this->add($root, 'infPedReg');
        $inf->setAttribute('Id', self::requestId((string) $invoice->access_key));
        $this->add($inf, 'tpAmb', $invoice->environment === FiscalEnvironment::Production ? '1' : '2');
        $this->add($inf, 'verAplic', 'Prumo_1.0');
        $this->add($inf, 'dhEvento', $now->copy()->timezone('America/Sao_Paulo')->format('Y-m-d\TH:i:sP'));
        $this->add($inf, 'CNPJAutor', $profile->cnpj);
        $this->add($inf, 'chNFSe', (string) $invoice->access_key);
        $this->add($inf, 'nPedRegEvento', '1');
        $event = $this->add($inf, 'e'.self::EVENT);
        $this->add($event, 'xDesc', 'Cancelamento de NFS-e');
        $this->add($event, 'cMotivo', $reason->value);
        $this->add($event, 'xMotivo', mb_substr($text, 0, 255));

        return $doc;
    }

    private function add(DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        $el = $parent->ownerDocument?->createElementNS(DpsXmlBuilder::NS, $name);
        assert($el instanceof DOMElement);
        if ($value !== null) {
            $el->appendChild($parent->ownerDocument->createTextNode($value));
        }
        $parent->appendChild($el);

        return $el;
    }
}
