<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Support;

use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * XMLDSig enveloped, RSA-SHA256, C14N inclusiva: o padrão dos documentos fiscais eletrônicos.
 * A assinatura entra como irmã do elemento assinado (ex.: `infDPS`), referenciando o atributo `Id` dele.
 */
final class XmlSigner
{
    private const DSIG = 'http://www.w3.org/2000/09/xmldsig#';

    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';

    public function sign(DOMDocument $doc, string $elementName, string $certPem, #[\SensitiveParameter] string $keyPem): DOMDocument
    {
        $target = $doc->getElementsByTagName($elementName)->item(0);
        if (! $target instanceof DOMElement || ! $target->parentNode instanceof DOMElement) {
            throw new RuntimeException("Elemento {$elementName} não encontrado para assinar.");
        }
        $id = $target->getAttribute('Id');
        $digest = base64_encode(hash('sha256', (string) $target->C14N(), true));

        $signature = $doc->createElementNS(self::DSIG, 'Signature');
        $target->parentNode->appendChild($signature);

        $signedInfo = $this->el($signature, 'SignedInfo');
        $this->el($signedInfo, 'CanonicalizationMethod')->setAttribute('Algorithm', self::C14N);
        $this->el($signedInfo, 'SignatureMethod')->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');
        $reference = $this->el($signedInfo, 'Reference');
        $reference->setAttribute('URI', '#'.$id);
        $transforms = $this->el($reference, 'Transforms');
        $this->el($transforms, 'Transform')->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        $this->el($transforms, 'Transform')->setAttribute('Algorithm', self::C14N);
        $this->el($reference, 'DigestMethod')->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');
        $this->el($reference, 'DigestValue', $digest);

        $key = openssl_pkey_get_private($keyPem);
        if ($key === false || ! openssl_sign((string) $signedInfo->C14N(), $raw, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Não foi possível assinar a DPS com o certificado.');
        }
        $this->el($signature, 'SignatureValue', base64_encode($raw));

        $x509 = (string) preg_replace('/-----[A-Z ]+-----|\s+/', '', $certPem);
        $this->el($this->el($this->el($signature, 'KeyInfo'), 'X509Data'), 'X509Certificate', $x509);

        return $doc;
    }

    private function el(DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        $el = $parent->ownerDocument?->createElementNS(self::DSIG, $name);
        assert($el instanceof DOMElement);
        if ($value !== null) {
            $el->appendChild($parent->ownerDocument->createTextNode($value));
        }
        $parent->appendChild($el);

        return $el;
    }
}
