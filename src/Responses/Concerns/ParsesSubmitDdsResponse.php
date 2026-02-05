<?php

declare(strict_types=1);

namespace Eudr\Responses\Concerns;

use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;

/**
 * Shared parsing logic for V1 and V2 SubmitDdsResponse classes.
 *
 * Using classes must define:
 * - A constructor matching: __construct(string $ddsIdentifier, ?string $timestamp = null)
 * - A static method `getSubmissionNamespaces(): array` returning namespace URIs ordered by priority.
 */
trait ParsesSubmitDdsResponse
{
    /**
     * @return list<string> Submission namespace URIs ordered by priority (own version first).
     */
    abstract protected static function getSubmissionNamespaces(): array;

    /**
     * Parse from a full SOAP XML string. Parses XML and finds the body internally.
     */
    public static function fromXml(string $xml): self
    {
        return self::fromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * Parse from a pre-parsed SOAP Body element. Avoids double-parsing when
     * the envelope has already been parsed (e.g., after fault checking).
     */
    public static function fromSoapBody(\SimpleXMLElement $body): self
    {
        // Look for SubmitStatementResponse in submission namespace
        $submissionNs = static::getSubmissionNamespaces();

        foreach ($submissionNs as $ns) {
            $responseChildren = $body->children($ns);
            if (isset($responseChildren->SubmitStatementResponse)) {
                $response = $responseChildren->SubmitStatementResponse;

                // Child elements may be in the same namespace or in the default (empty) namespace
                $ddsIdentifier = (string) ($response->children($ns)->ddsIdentifier ?? '');
                $timestamp = (string) ($response->children($ns)->timestamp ?? '') ?: null;

                // Fallback: try default namespace
                if ($ddsIdentifier === '') {
                    $defaultChildren = $response->children('');
                    $ddsIdentifier = (string) ($defaultChildren->ddsIdentifier ?? '');
                    $timestamp = (string) ($defaultChildren->timestamp ?? '') ?: null;
                }

                return new self(
                    ddsIdentifier: $ddsIdentifier,
                    timestamp: $timestamp,
                );
            }
        }

        throw new XmlException('Could not find SubmitStatementResponse in SOAP body');
    }
}
