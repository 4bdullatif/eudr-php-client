<?php

declare(strict_types=1);

namespace Eudr\Responses\V3;

use Eudr\Enums\V3\EudrStatus;
use Eudr\Exceptions\XmlException;
use Eudr\Responses\Response;
use Eudr\Responses\V3\Concerns\ReadsV3Xml;
use Eudr\Support\SoapEnvelope;

/**
 * Response of amendDds, withdrawDds, updateSd and withdrawSd
 * (DdsModificationResponseType / SdModificationResponseType).
 */
final readonly class ModificationResponse implements Response
{
    use ReadsV3Xml;

    private const RESPONSE_ELEMENTS = [
        'AmendDdsResponse',
        'WithdrawDdsResponse',
        'UpdateSdResponse',
        'WithdrawSdResponse',
    ];

    public function __construct(
        public string $uuid,
        /** Resulting lifecycle status, e.g. AVAILABLE after amend or WITHDRAWN after withdraw. */
        public EudrStatus $status = EudrStatus::UNKNOWN,
        public string $rawStatusValue = '',
        /** Declaration version (Simplified Declarations only). */
        public ?int $version = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->uuid !== '';
    }

    /**
     * @throws XmlException
     */
    public static function fromXml(string $xml): self
    {
        return self::fromSoapBody(SoapEnvelope::parse($xml)->getBody());
    }

    /**
     * @throws XmlException
     */
    public static function fromSoapBody(\SimpleXMLElement $body): self
    {
        $response = self::findResponseElement($body, self::RESPONSE_ELEMENTS);

        if ($response === null) {
            throw new XmlException('Could not find amend/update/withdraw response in SOAP body');
        }

        $rawStatus = self::childText($response, 'status') ?? '';

        return new self(
            uuid: self::childText($response, 'uuid') ?? '',
            status: EudrStatus::fromString($rawStatus),
            rawStatusValue: $rawStatus,
            version: self::childInt($response, 'version'),
        );
    }
}
