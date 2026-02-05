<?php

declare(strict_types=1);

namespace Eudr\Responses;

use Eudr\Data\ErrorDetail;
use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;
use Eudr\Support\Xml;

final readonly class ErrorResponse implements Response
{
    /**
     * @param list<ErrorDetail> $errors
     */
    public function __construct(
        public string $faultCode,
        public string $faultString,
        public ?string $detail = null,
        public array $errors = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return false;
    }

    /**
     * Parse a full SOAP XML string and extract a fault if present.
     */
    public static function fromXml(string $xml): ?self
    {
        $previousErrors = libxml_use_internal_errors(true);

        try {
            $simpleXml = new \SimpleXMLElement($xml, LIBXML_NONET);
        } catch (\Exception $e) {
            libxml_use_internal_errors($previousErrors);
            throw new XmlException('Failed to parse XML response: ' . $e->getMessage(), 0, $e);
        }

        libxml_use_internal_errors($previousErrors);

        $body = SoapEnvelope::findSoapBody($simpleXml);

        if ($body === null) {
            return null;
        }

        return self::fromSoapBody($body);
    }

    /**
     * Extract a SOAP fault from a pre-parsed SOAP Body element.
     */
    public static function fromSoapBody(\SimpleXMLElement $body): ?self
    {
        // Look for Fault in the SOAP body
        $fault = null;
        foreach ($body->children(Xml::SOAP_NS) as $child) {
            if ($child->getName() === 'Fault') {
                $fault = $child;
                break;
            }
        }

        // Also try without namespace
        if ($fault === null) {
            foreach ($body->children() as $child) {
                if ($child->getName() === 'Fault') {
                    $fault = $child;
                    break;
                }
            }
        }

        if ($fault === null) {
            return null;
        }

        // Fault children (faultcode, faultstring, detail) may be in default namespace
        $faultDefault = $fault->children('');
        $faultCode = (string) ($faultDefault->faultcode ?? '');
        $faultString = (string) ($faultDefault->faultstring ?? '');

        // Fallback: SOAP 1.2 style (Code/Value, Reason/Text in SOAP namespace)
        if ($faultCode === '') {
            $faultSoap = $fault->children(Xml::SOAP_NS);
            $faultCode = (string) ($faultSoap->Code->Value ?? '');
            $faultString = (string) ($faultSoap->Reason->Text ?? '');
        }

        $detail = null;

        if (isset($faultDefault->detail)) {
            $detail = $faultDefault->detail->asXML();
        } elseif (isset($faultDefault->Detail)) {
            $detail = $faultDefault->Detail->asXML();
        }

        if ($detail === false) {
            $detail = null;
        }

        // Parse structured error details from the error namespace
        $errors = self::parseErrorDetails($faultDefault);

        return new self(
            faultCode: $faultCode,
            faultString: $faultString,
            detail: $detail,
            errors: $errors,
        );
    }

    /**
     * @return list<ErrorDetail>
     */
    private static function parseErrorDetails(\SimpleXMLElement $faultDefault): array
    {
        $errors = [];

        $detailElement = $faultDefault->detail ?? $faultDefault->Detail ?? null;
        if ($detailElement === null) {
            return [];
        }

        // Search all namespaces for Error elements
        $namespaces = array_merge(
            [Xml::TRACESNT_ERROR_NS],
            array_values($detailElement->getNamespaces(true)),
            [''],
        );

        foreach ($namespaces as $ns) {
            // Look for Error elements at any depth within the detail
            self::findErrors($detailElement, $ns, $errors);
            if ($errors !== []) {
                break;
            }
        }

        return $errors;
    }

    /**
     * @param list<ErrorDetail> $errors
     */
    private static function findErrors(\SimpleXMLElement $element, string $ns, array &$errors): void
    {
        $children = $element->children($ns);

        foreach ($children as $child) {
            if ($child->getName() === 'Error') {
                $errorChildren = $child->children($ns);
                $id = (string) ($errorChildren->ID ?? '');

                if ($id !== '') {
                    $errors[] = new ErrorDetail(
                        id: $id,
                        message: (string) ($errorChildren->Message ?? '') ?: null,
                        field: (string) ($errorChildren->Field ?? '') ?: null,
                    );
                }
            } else {
                // Recurse into child elements to find Error elements at deeper levels
                self::findErrors($child, $ns, $errors);
            }
        }
    }
}
