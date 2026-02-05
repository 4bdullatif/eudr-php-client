<?php

declare(strict_types=1);

namespace Eudr\Support;

use Eudr\Exceptions\XmlException;
use Eudr\Responses\ErrorResponse;

final readonly class SoapEnvelope
{
    private function __construct(
        private \SimpleXMLElement $body,
    ) {
    }

    /**
     * Parse a SOAP XML string once and extract the body element.
     *
     * @throws XmlException If XML parsing fails or SOAP Body is not found.
     */
    public static function parse(string $xml): self
    {
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $simpleXml = new \SimpleXMLElement($xml, LIBXML_NONET);
        } catch (\Exception $e) {
            libxml_use_internal_errors($previousErrors);
            throw new XmlException('Failed to parse XML response: ' . $e->getMessage(), 0, $e);
        }

        libxml_use_internal_errors($previousErrors);

        $body = self::findSoapBody($simpleXml);
        if ($body === null) {
            throw new XmlException('Could not find SOAP Body in response');
        }

        return new self($body);
    }

    public function getBody(): \SimpleXMLElement
    {
        return $this->body;
    }

    /**
     * Check if the SOAP body contains a fault and return it, or null if no fault.
     */
    public function getFault(): ?ErrorResponse
    {
        return ErrorResponse::fromSoapBody($this->body);
    }

    public static function findSoapBody(\SimpleXMLElement $xml): ?\SimpleXMLElement
    {
        $namespaces = $xml->getNamespaces(true);

        foreach ($namespaces as $uri) {
            if (
                str_contains($uri, 'schemas.xmlsoap.org/soap/envelope')
                || str_contains($uri, 'soap-envelope')
            ) {
                $children = $xml->children($uri);
                if (isset($children->Body)) {
                    return $children->Body;
                }
            }
        }

        return null;
    }
}
