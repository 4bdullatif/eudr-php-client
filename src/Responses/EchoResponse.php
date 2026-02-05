<?php

declare(strict_types=1);

namespace Eudr\Responses;

use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;
use Eudr\Support\Xml;

final readonly class EchoResponse implements Response
{
    public function __construct(
        public string $result,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->result !== '';
    }

    public static function fromXml(string $xml): self
    {
        return self::fromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * @throws XmlException
     */
    public static function fromSoapBody(\SimpleXMLElement $body): self
    {
        $children = $body->children(Xml::EUDR_ECHO_NS);
        if (isset($children->EudrEchoResponse)) {
            return new self(result: (string) $children->EudrEchoResponse);
        }

        // Try without namespace
        foreach ($body->children() as $child) {
            if (str_contains($child->getName(), 'EudrEchoResponse')) {
                return new self(result: (string) $child);
            }
        }

        throw new XmlException('Could not find echo response in SOAP body');
    }
}
