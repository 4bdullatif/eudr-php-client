<?php

declare(strict_types=1);

namespace Eudr\Responses;

use Eudr\Enums\DdsStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;
use Eudr\Support\Xml;

final readonly class SupplierStatementResponse implements Response
{
    /**
     * @param list<array{referenceNumber: string}> $associatedStatements
     */
    public function __construct(
        public ?string $referenceNumber = null,
        public ?string $activityType = null,
        public DdsStatus $status = DdsStatus::UNKNOWN,
        public string $rawStatusValue = '',
        public ?string $statusDate = null,
        public ?string $operatorName = null,
        public ?string $operatorCountry = null,
        public array $associatedStatements = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->referenceNumber !== null;
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
        $responseNames = [
            'GetStatementByIdentifiersResponse',
            'GetReferencedDdsResponse',
        ];
        $namespaces = [
            Xml::EUDR_RETRIEVAL_NS_V2,
            Xml::EUDR_RETRIEVAL_NS_V1,
        ];

        foreach ($namespaces as $ns) {
            $children = $body->children($ns);
            foreach ($responseNames as $name) {
                if (isset($children->$name)) {
                    $response = $children->$name;
                    // Look for statement or referenceDds child
                    $statement = $response->children($ns)->statement
                        ?? $response->children($ns)->referenceDds
                        ?? null;

                    if ($statement !== null) {
                        return self::parseStatement($statement, $ns);
                    }
                }
            }
        }

        // Fallback without namespace
        foreach ($body->children() as $child) {
            $childName = $child->getName();
            if (str_contains($childName, 'GetStatementByIdentifiersResponse') || str_contains($childName, 'GetReferencedDdsResponse')) {
                $statement = $child->statement ?? $child->referenceDds ?? null;
                if ($statement !== null) {
                    return self::parseStatement($statement, '');
                }
            }
        }

        throw new XmlException('Could not find supplier statement response in SOAP body');
    }

    private static function parseStatement(\SimpleXMLElement $statement, string $retrievalNs): self
    {
        // Try multiple namespaces for child elements
        $namespacesToTry = array_unique(array_merge(
            [$retrievalNs],
            array_values($statement->getNamespaces(true)),
            [''],
        ));

        $refNumber = null;
        $activityType = null;
        $rawStatus = '';
        $statusDate = null;
        $operatorName = null;
        $operatorCountry = null;
        /** @var list<array{referenceNumber: string}> $assocStatements */
        $assocStatements = [];

        foreach ($namespacesToTry as $ns) {
            $children = $statement->children($ns);
            $foundRef = (string) ($children->referenceNumber ?? '');
            if ($foundRef !== '') {
                $refNumber = $foundRef;
                $activityType = (string) ($children->activityType ?? '') ?: null;

                // Status may be nested (contains status + date sub-elements)
                if (isset($children->status)) {
                    $statusEl = $children->status;
                    // Try to find nested status value
                    foreach ($namespacesToTry as $sNs) {
                        $statusChildren = $statusEl->children($sNs);
                        $statusVal = (string) ($statusChildren->status ?? '');
                        if ($statusVal !== '') {
                            $rawStatus = $statusVal;
                            $statusDate = (string) ($statusChildren->date ?? '') ?: null;
                            break;
                        }
                    }
                    // If no nested status found, use direct value
                    if ($rawStatus === '') {
                        $rawStatus = (string) $statusEl;
                    }
                }

                // Operator info
                if (isset($children->operator)) {
                    $opEl = $children->operator;
                    foreach ($namespacesToTry as $oNs) {
                        $opChildren = $opEl->children($oNs);
                        $name = (string) ($opChildren->name ?? '');
                        if ($name !== '') {
                            $operatorName = $name;
                            $operatorCountry = (string) ($opChildren->country ?? '') ?: null;
                            break;
                        }
                    }
                }

                // Associated statements
                if (isset($children->associateStatement)) {
                    foreach ($children->associateStatement as $assoc) {
                        $assocRef = (string) ($assoc->children($ns)->referenceNumber ?? '');
                        if ($assocRef !== '') {
                            $assocStatements[] = ['referenceNumber' => $assocRef];
                        }
                    }
                }

                break;
            }
        }

        return new self(
            referenceNumber: $refNumber,
            activityType: $activityType,
            status: DdsStatus::fromString($rawStatus),
            rawStatusValue: $rawStatus,
            statusDate: $statusDate,
            operatorName: $operatorName,
            operatorCountry: $operatorCountry,
            associatedStatements: $assocStatements,
        );
    }
}
