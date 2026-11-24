<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use App\Modules\Invoicing\Enums\FiscalEnvironment;
use App\Modules\Invoicing\Enums\TaxRegime;
use App\Modules\Invoicing\Models\FiscalProfile;
use App\Modules\Invoicing\Models\Invoice;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Carbon;

/**
 * DPS (Declaração de Prestação de Serviço) da NFS-e Nacional, leiaute 1.0x.
 * Todo o leiaute do governo fica aqui.
 * ponytail: montado sem os XSD oficiais em mãos (gov.br bloqueia download automático). Conferir contra
 * `docs/nfse/` (XSD + Anexo I) e na homologação; candidatos a ajuste: pAliq/totTrib do Simples, cNBS, IBSCBS.
 */
final class DpsXmlBuilder
{
    public const NS = 'http://www.sped.fazenda.gov.br/nfse';

    public const VERSION = '1.01';

    /** Id da DPS: "DPS" + município (7) + tipo de inscrição (1 = CPF, 2 = CNPJ) + inscrição (14) + série (5) + número (15). */
    public static function dpsId(FiscalProfile $profile, string $series, int $number): string
    {
        return 'DPS'.$profile->city_ibge_code.'2'.str_pad($profile->cnpj, 14, '0', STR_PAD_LEFT)
            .str_pad($series, 5, '0', STR_PAD_LEFT).str_pad((string) $number, 15, '0', STR_PAD_LEFT);
    }

    public function build(FiscalProfile $profile, Invoice $invoice, Taker $taker, string $description, Carbon $now): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $dps = $doc->appendChild($doc->createElementNS(self::NS, 'DPS'));
        assert($dps instanceof DOMElement);
        $dps->setAttribute('versao', self::VERSION);

        $inf = $this->add($dps, 'infDPS');
        $inf->setAttribute('Id', $invoice->dps_id);
        $local = $now->copy()->timezone('America/Sao_Paulo');
        $this->add($inf, 'tpAmb', $invoice->environment === FiscalEnvironment::Production ? '1' : '2');
        $this->add($inf, 'dhEmi', $local->format('Y-m-d\TH:i:sP'));
        $this->add($inf, 'verAplic', 'Prumo_1.0');
        $this->add($inf, 'serie', $invoice->dps_series);
        $this->add($inf, 'nDPS', (string) $invoice->dps_number);
        $this->add($inf, 'dCompet', $local->format('Y-m-d'));
        $this->add($inf, 'tpEmit', '1');
        $this->add($inf, 'cLocEmi', $profile->city_ibge_code);

        $prest = $this->add($inf, 'prest');
        $this->add($prest, 'CNPJ', $profile->cnpj);
        if ($profile->municipal_registration !== null) {
            $this->add($prest, 'IM', $profile->municipal_registration);
        }
        $reg = $this->add($prest, 'regTrib');
        // opSimpNac: 2 = MEI, 3 = ME/EPP. regApTribSN 1 = tributos federais e municipal pelo Simples.
        $this->add($reg, 'opSimpNac', $profile->tax_regime === TaxRegime::Mei ? '2' : '3');
        if ($profile->tax_regime === TaxRegime::SimplesMeEpp) {
            $this->add($reg, 'regApTribSN', '1');
        }
        $this->add($reg, 'regEspTrib', '0');

        $toma = $this->add($inf, 'toma');
        $this->add($toma, strlen($taker->document) === 11 ? 'CPF' : 'CNPJ', $taker->document);
        $this->add($toma, 'xNome', mb_substr($taker->name, 0, 300));
        if ($taker->email !== null) {
            $this->add($toma, 'email', mb_substr($taker->email, 0, 80));
        }

        $serv = $this->add($inf, 'serv');
        $this->add($this->add($serv, 'locPrest'), 'cLocPrestacao', $profile->city_ibge_code);
        $cServ = $this->add($serv, 'cServ');
        $this->add($cServ, 'cTribNac', $profile->national_tax_code);
        if ($profile->municipal_tax_code !== null) {
            $this->add($cServ, 'cTribMun', $profile->municipal_tax_code);
        }
        $this->add($cServ, 'xDescServ', mb_substr($description, 0, 2000));

        $valores = $this->add($inf, 'valores');
        $this->add($this->add($valores, 'vServPrest'), 'vServ', self::decimal($invoice->amount_cents));
        $trib = $this->add($valores, 'trib');
        $mun = $this->add($trib, 'tribMun');
        $this->add($mun, 'tribISSQN', '1'); // operação tributável
        if ($profile->tax_regime === TaxRegime::SimplesMeEpp && $profile->iss_rate_bp !== null) {
            $this->add($mun, 'pAliq', self::decimal($profile->iss_rate_bp));
        }
        $this->add($mun, 'tpRetISSQN', '1'); // não retido
        $this->add($this->add($trib, 'totTrib'), 'indTotTrib', '0');

        return $doc;
    }

    /** Centavos (ou pontos-base) → "1234.56", só com inteiros. */
    public static function decimal(int $hundredths): string
    {
        return sprintf('%d.%02d', intdiv($hundredths, 100), $hundredths % 100);
    }

    private function add(DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        $el = $parent->ownerDocument?->createElementNS(self::NS, $name);
        assert($el instanceof DOMElement);
        if ($value !== null) {
            $el->appendChild($parent->ownerDocument->createTextNode($value));
        }
        $parent->appendChild($el);

        return $el;
    }
}
