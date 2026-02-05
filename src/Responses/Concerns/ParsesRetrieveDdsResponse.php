<?php

declare(strict_types=1);

namespace Eudr\Responses\Concerns;

use Eudr\Enums\DdsStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;

/**
 * Shared parsing logic for V1 and V2 RetrieveDdsResponse classes.
 *
 * Using classes must define:
 * - A constructor matching: __construct(string $identifier, ...) (same as both V1/V2)
 * - A static method `getRetrievalNamespaces(): array` returning [primaryNs, fallbackNs]
 * - A static method `getModelNamespaces(): array` returning [primaryNs, fallbackNs]
 * - A static method `getFallbackModelNamespace(): string`
 */
trait ParsesRetrieveDdsResponse
{
    /**
     * @return list<string> Retrieval namespace URIs ordered by priority (own version first).
     */
    abstract protected static function getRetrievalNamespaces(): array;

    /**
     * @return list<string> Model namespace URIs ordered by priority (own version first).
     */
    abstract protected static function getModelNamespaces(): array;

    /**
     * @return string The model namespace to use in the no-namespace fallback path.
     */
    abstract protected static function getFallbackModelNamespace(): string;

    /**
     * Parse from a full SOAP XML string. Returns the first result.
     */
    public static function fromXml(string $xml): self
    {
        return self::fromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * Parse all results from a full SOAP XML string.
     *
     * @return list<self>
     */
    public static function allFromXml(string $xml): array
    {
        return self::allFromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * Parse the first result from a pre-parsed SOAP Body element.
     *
     * @throws XmlException
     */
    public static function fromSoapBody(\SimpleXMLElement $body): self
    {
        $results = self::allFromSoapBody($body);

        if ($results === []) {
            throw new XmlException('Could not find retrieval response in SOAP body');
        }

        return $results[0];
    }

    /**
     * Parse all results from a pre-parsed SOAP Body element.
     *
     * @return list<self>
     * @throws XmlException
     */
    public static function allFromSoapBody(\SimpleXMLElement $body): array
    {
        $retrievalNs = static::getRetrievalNamespaces();
        $modelNs = static::getModelNamespaces();
        $responseElementNames = ['GetStatementInfoResponse', 'GetDdsInfoByInternalReferenceNumberResponse'];

        foreach ($retrievalNs as $rIndex => $rNs) {
            $responseChildren = $body->children($rNs);
            $correspondingModelNs = $modelNs[$rIndex];

            foreach ($responseElementNames as $responseElementName) {
                if (isset($responseChildren->$responseElementName)) {
                    $response = $responseChildren->$responseElementName;

                    return self::parseAllStatementInfo($response, $rNs, $correspondingModelNs);
                }
            }
        }

        // Fallback: try without namespaces
        foreach ($body->children() as $child) {
            $childName = $child->getName();
            if (str_contains($childName, 'GetStatementInfoResponse') || str_contains($childName, 'GetDdsInfoByInternalReferenceNumberResponse')) {
                return self::parseAllStatementInfo($child, '', static::getFallbackModelNamespace());
            }
        }

        return [];
    }

    /**
     * @return list<self>
     */
    private static function parseAllStatementInfo(
        \SimpleXMLElement $response,
        string $retrievalNs,
        string $modelNs,
    ): array {
        $results = [];

        // Try retrieval namespace first, then model, then default
        $namespacesToTry = array_unique(array_filter([$retrievalNs, $modelNs, '']));

        foreach ($namespacesToTry as $ns) {
            $children = $response->children($ns);
            if (isset($children->statementInfo)) {
                foreach ($children->statementInfo as $info) {
                    $results[] = self::parseStatementInfo($info, $modelNs);
                }
                break;
            }
        }

        return $results;
    }

    private static function parseStatementInfo(\SimpleXMLElement $info, string $modelNs): self
    {
        $identifier = '';
        $internalRef = null;
        $refNumber = null;
        $verificationNumber = null;
        $rawStatus = '';
        $updatedBy = null;
        $rejectionReason = null;
        $date = null;
        $commDate = null;
        $commMessage = null;

        // Try namespaces registered on the element
        $namespacesToTry = array_unique(array_merge(
            [$modelNs],
            array_values($info->getNamespaces(true)),
            [''],
        ));

        foreach ($namespacesToTry as $ns) {
            $children = $info->children($ns);
            $foundId = (string) ($children->identifier ?? '');
            if ($foundId !== '') {
                $identifier = $foundId;
                $internalRef = (string) ($children->internalReferenceNumber ?? '') ?: null;
                $refNumber = (string) ($children->referenceNumber ?? '') ?: null;
                $verificationNumber = (string) ($children->verificationNumber ?? '') ?: null;
                $rawStatus = (string) ($children->status ?? '');
                $updatedBy = (string) ($children->updatedBy ?? '') ?: null;
                $rejectionReason = (string) ($children->rejectionReason ?? '') ?: null;
                $date = (string) ($children->date ?? '') ?: null;

                // Parse communicationToOperator if present
                if (isset($children->communicationToOperator)) {
                    $comm = $children->communicationToOperator;
                    $commChildren = $comm->children($ns);
                    $commDate = (string) ($commChildren->date ?? '') ?: null;
                    $commMessage = (string) ($commChildren->message ?? '') ?: null;
                }

                break;
            }
        }

        return new self(
            identifier: $identifier,
            internalReferenceNumber: $internalRef,
            referenceNumber: $refNumber,
            verificationNumber: $verificationNumber,
            status: DdsStatus::fromString($rawStatus),
            rawStatusValue: $rawStatus,
            updatedBy: $updatedBy,
            rejectionReason: $rejectionReason,
            date: $date,
            communicationToOperatorDate: $commDate,
            communicationToOperatorMessage: $commMessage,
        );
    }
}
