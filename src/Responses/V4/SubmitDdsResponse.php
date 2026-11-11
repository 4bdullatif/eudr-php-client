<?php

declare(strict_types=1);

namespace Eudr\Responses\V4;

use Eudr\Exceptions\XmlException;
use Eudr\Responses\Response;
use Eudr\Responses\V4\Concerns\ReadsV4Xml;
use Eudr\Support\SoapEnvelope;

/**
 * submitDds response (SubmitDdsResponseType).
 */
final readonly class SubmitDdsResponse implements Response
{
    use ReadsV4Xml;

    public function __construct(
        /** UUID assigned by the Information System to the submitted DDS. */
        public string $uuid,
        /** EUDR announcement from the EudrUserMessage response header, if any. */
        public ?string $userMessage = null,
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
        return self::fromEnvelope(SoapEnvelope::parse($xml));
    }

    /**
     * @throws XmlException
     */
    public static function fromEnvelope(SoapEnvelope $envelope): self
    {
        return self::fromSoapBody($envelope->getBody(), self::userMessageFromHeader($envelope->getHeader()));
    }

    /**
     * @throws XmlException
     */
    public static function fromSoapBody(\SimpleXMLElement $body, ?string $userMessage = null): self
    {
        $response = self::findResponseElement($body, ['SubmitDdsResponse']);

        if ($response === null) {
            throw new XmlException('Could not find SubmitDdsResponse in SOAP body');
        }

        return new self(uuid: self::childText($response, 'uuid') ?? '', userMessage: $userMessage);
    }
}
