<?php

declare(strict_types=1);

namespace Eudr\Responses\Concerns;

use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;

/**
 * Shared parsing logic for V1 and V2 StatusResponse classes.
 *
 * Using classes must define:
 * - A constructor matching: __construct(string $status)
 * - A static method `getSubmissionNamespaces(): array` returning namespace URIs ordered by priority.
 */
trait ParsesStatusResponse
{
    /**
     * @return list<string> Submission namespace URIs ordered by priority (own version first).
     */
    abstract protected static function getSubmissionNamespaces(): array;

    public static function fromXml(string $xml): self
    {
        return self::fromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * @throws XmlException
     */
    public static function fromSoapBody(\SimpleXMLElement $body): self
    {
        $responseNames = [
            'AmendStatementResponse',
            'RetractStatementResponse',
        ];
        $namespaces = static::getSubmissionNamespaces();

        foreach ($namespaces as $ns) {
            $children = $body->children($ns);
            foreach ($responseNames as $name) {
                if (isset($children->$name)) {
                    $response = $children->$name;
                    $status = (string) ($response->children($ns)->status ?? '');
                    if ($status === '') {
                        $status = (string) ($response->children('')->status ?? '');
                    }
                    return new self(status: $status);
                }
            }
        }

        // Fallback without namespaces
        foreach ($body->children() as $child) {
            $childName = $child->getName();
            if (str_contains($childName, 'AmendStatementResponse') || str_contains($childName, 'RetractStatementResponse')) {
                return new self(status: (string) ($child->status ?? ''));
            }
        }

        throw new XmlException('Could not find amend/retract response in SOAP body');
    }
}
