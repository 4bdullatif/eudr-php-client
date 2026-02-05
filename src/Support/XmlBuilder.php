<?php

declare(strict_types=1);

namespace Eudr\Support;

use Eudr\Exceptions\XmlException;

final class XmlBuilder
{
    private \DOMDocument $doc;
    private \DOMElement $envelope;
    private \DOMElement $header;
    private \DOMElement $body;

    public function __construct()
    {
        $this->doc = new \DOMDocument('1.0', 'UTF-8');
        $this->doc->formatOutput = false;

        $this->envelope = $this->doc->createElementNS(Xml::SOAP_NS, 'soapenv:Envelope');
        $this->doc->appendChild($this->envelope);

        $this->header = $this->doc->createElementNS(Xml::SOAP_NS, 'soapenv:Header');
        $this->envelope->appendChild($this->header);

        $this->body = $this->doc->createElementNS(Xml::SOAP_NS, 'soapenv:Body');
        $this->envelope->appendChild($this->body);
    }

    public function addNamespace(string $prefix, string $uri): self
    {
        $this->envelope->setAttributeNS(Xml::XMLNS_NS, 'xmlns:' . $prefix, $uri);
        return $this;
    }

    public function getDocument(): \DOMDocument
    {
        return $this->doc;
    }

    public function getEnvelope(): \DOMElement
    {
        return $this->envelope;
    }

    public function getHeader(): \DOMElement
    {
        return $this->header;
    }

    public function getBody(): \DOMElement
    {
        return $this->body;
    }

    public function createElement(string $qualifiedName, ?string $value = null): \DOMElement
    {
        $el = $this->doc->createElement($qualifiedName);

        if ($value !== null) {
            $el->appendChild($this->doc->createTextNode($value));
        }

        return $el;
    }

    public function createElementNS(string $namespaceUri, string $qualifiedName, ?string $value = null): \DOMElement
    {
        $el = $this->doc->createElementNS($namespaceUri, $qualifiedName);

        if ($value !== null) {
            $el->appendChild($this->doc->createTextNode($value));
        }

        return $el;
    }

    public function toString(): string
    {
        $xml = $this->doc->saveXML();
        if ($xml === false) {
            throw new XmlException('Failed to serialize XML document');
        }

        return $xml;
    }

    public static function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = \chr((\ord($data[6]) & 0x0f) | 0x40);
        $data[8] = \chr((\ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
