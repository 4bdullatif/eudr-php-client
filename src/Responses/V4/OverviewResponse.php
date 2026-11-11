<?php

declare(strict_types=1);

namespace Eudr\Responses\V4;

use Eudr\Enums\V4\EudrStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Responses\Response;
use Eudr\Responses\V4\Concerns\ReadsV4Xml;
use Eudr\Support\SoapEnvelope;

/**
 * Overview of a DDS or Simplified Declaration (common OverviewType), returned by
 * getDds, getDdsByInternalReference, getSd and getSdByInternalReference.
 */
final readonly class OverviewResponse implements Response
{
    use ReadsV4Xml;

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
        /**
         * Human-readable reference number status (new in V4), e.g. "Under system analysis",
         * "Under Competent Authority review (Article 17)" or "Assigned".
         */
        public ?string $referenceNumberInfo = null,
        /** EUDR announcement from the EudrUserMessage response header, if any. */
        public ?string $userMessage = null,
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
        $envelope = SoapEnvelope::parse($xml);

        return self::fromSoapBody($envelope->getBody(), self::userMessageFromHeader($envelope->getHeader()));
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
        return self::allFromEnvelope(SoapEnvelope::parse($xml));
    }

    /**
     * Parse all overviews from a parsed SOAP envelope, including the EudrUserMessage header.
     *
     * @return list<self>
     *
     * @throws XmlException If the retrieval response element is missing.
     */
    public static function allFromEnvelope(SoapEnvelope $envelope): array
    {
        return self::allFromSoapBody($envelope->getBody(), self::userMessageFromHeader($envelope->getHeader()));
    }

    /**
     * Parse the first overview from a parsed SOAP envelope, including the EudrUserMessage header.
     *
     * @throws XmlException If the response is missing or contains no overview.
     */
    public static function fromEnvelope(SoapEnvelope $envelope): self
    {
        return self::fromSoapBody($envelope->getBody(), self::userMessageFromHeader($envelope->getHeader()));
    }

    /**
     * Parse the first overview from a pre-parsed SOAP Body element.
     *
     * @throws XmlException If the response is missing or contains no overview.
     */
    public static function fromSoapBody(\SimpleXMLElement $body, ?string $userMessage = null): self
    {
        $results = self::allFromSoapBody($body, $userMessage);

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
    public static function allFromSoapBody(\SimpleXMLElement $body, ?string $userMessage = null): array
    {
        $response = self::findResponseElement($body, self::RESPONSE_ELEMENTS);

        if ($response === null) {
            throw new XmlException('Could not find retrieval response in SOAP body');
        }

        $results = [];

        foreach (self::LIST_ELEMENTS as $listElement) {
            foreach (self::childElements($response, $listElement) as $overview) {
                $results[] = self::parseOverview($overview, $userMessage);
            }
        }

        return $results;
    }

    private static function parseOverview(\SimpleXMLElement $overview, ?string $userMessage): self
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
            referenceNumberInfo: self::childNonEmptyText($overview, 'referenceNumberInfo'),
            userMessage: $userMessage,
        );
    }
}
