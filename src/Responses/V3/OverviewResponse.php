<?php

declare(strict_types=1);

namespace Eudr\Responses\V3;

use Eudr\Enums\V3\EudrStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Responses\Response;
use Eudr\Responses\V3\Concerns\ReadsV3Xml;
use Eudr\Support\SoapEnvelope;

/**
 * Overview of a DDS or Simplified Declaration (common OverviewType), returned by
 * getDds, getDdsByInternalReference, getSd and getSdByInternalReference.
 */
final readonly class OverviewResponse implements Response
{
    use ReadsV3Xml;

    private const RESPONSE_ELEMENTS = [
        'GetDdsResponse',
        'GetDdsByInternalReferenceResponse',
        'GetSdResponse',
        'GetSdByInternalReferenceResponse',
    ];

    private const LIST_ELEMENTS = ['ddsOverviewList', 'sdOverviewList'];

    public function __construct(
        public string $uuid,
        public ?string $internalReferenceNumber = null,
        public ?string $referenceNumber = null,
        public ?string $verificationNumber = null,
        public EudrStatus $status = EudrStatus::UNKNOWN,
        public string $rawStatusValue = '',
        public ?string $rejectionReason = null,
        public ?string $communicationToOperatorDate = null,
        public ?string $communicationToOperatorContent = null,
        public ?string $date = null,
        public ?string $updatedBy = null,
        public ?int $version = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->uuid !== '';
    }

    /**
     * Parse the first overview from a full SOAP XML string.
     *
     * @throws XmlException
     */
    public static function fromXml(string $xml): self
    {
        return self::fromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * Parse all overviews from a full SOAP XML string.
     *
     * @return list<self>
     *
     * @throws XmlException
     */
    public static function allFromXml(string $xml): array
    {
        return self::allFromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * Parse the first overview from a pre-parsed SOAP Body element.
     *
     * @throws XmlException If the response is missing or contains no overview.
     */
    public static function fromSoapBody(\SimpleXMLElement $body): self
    {
        $results = self::allFromSoapBody($body);

        if ($results === []) {
            throw new XmlException('Retrieval response contains no declaration overview');
        }

        return $results[0];
    }

    /**
     * Parse all overviews from a pre-parsed SOAP Body element.
     *
     * @return list<self>
     *
     * @throws XmlException If the retrieval response element is missing.
     */
    public static function allFromSoapBody(\SimpleXMLElement $body): array
    {
        $response = self::findResponseElement($body, self::RESPONSE_ELEMENTS);

        if ($response === null) {
            throw new XmlException('Could not find retrieval response in SOAP body');
        }

        $results = [];

        foreach (self::LIST_ELEMENTS as $listElement) {
            foreach (self::childElements($response, $listElement) as $overview) {
                $results[] = self::parseOverview($overview);
            }
        }

        return $results;
    }

    private static function parseOverview(\SimpleXMLElement $overview): self
    {
        $rawStatus = self::childText($overview, 'status') ?? '';

        $commDate = null;
        $commContent = null;
        $communication = self::childElements($overview, 'communicationToOperator');
        if ($communication !== []) {
            $commDate = self::childNonEmptyText($communication[0], 'date');
            $commContent = self::childNonEmptyText($communication[0], 'content');
        }

        return new self(
            uuid: self::childText($overview, 'uuid') ?? '',
            internalReferenceNumber: self::childNonEmptyText($overview, 'internalReferenceNumber'),
            referenceNumber: self::childNonEmptyText($overview, 'referenceNumber'),
            verificationNumber: self::childNonEmptyText($overview, 'verificationNumber'),
            status: EudrStatus::fromString($rawStatus),
            rawStatusValue: $rawStatus,
            rejectionReason: self::childNonEmptyText($overview, 'rejectionReason'),
            communicationToOperatorDate: $commDate,
            communicationToOperatorContent: $commContent,
            date: self::childNonEmptyText($overview, 'date'),
            updatedBy: self::childNonEmptyText($overview, 'updatedBy'),
            version: self::childInt($overview, 'version'),
        );
    }
}
