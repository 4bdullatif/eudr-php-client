<?php

declare(strict_types=1);

namespace Eudr\Responses\V3;

use Eudr\Exceptions\XmlException;
use Eudr\Responses\Response;
use Eudr\Responses\V3\Concerns\ReadsV3Xml;
use Eudr\Support\SoapEnvelope;

/**
 * submitSd response (SubmitSdResponseType).
 */
final readonly class SubmitSdResponse implements Response
{
    use ReadsV3Xml;

    public function __construct(
        /** UUID assigned by the Information System to the submitted Simplified Declaration. */
        public string $sdIdentifier,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->sdIdentifier !== '';
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
        $response = self::findResponseElement($body, ['SubmitSdResponse']);

        if ($response === null) {
            throw new XmlException('Could not find SubmitSdResponse in SOAP body');
        }

        return new self(sdIdentifier: self::childText($response, 'sdIdentifier') ?? '');
    }
}
