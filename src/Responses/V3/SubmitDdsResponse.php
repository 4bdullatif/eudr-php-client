<?php

declare(strict_types=1);

namespace Eudr\Responses\V3;

use Eudr\Exceptions\XmlException;
use Eudr\Responses\Response;
use Eudr\Responses\V3\Concerns\ReadsV3Xml;
use Eudr\Support\SoapEnvelope;

/**
 * submitDds response (SubmitDdsResponseType).
 */
final readonly class SubmitDdsResponse implements Response
{
    use ReadsV3Xml;

    public function __construct(
        /** UUID assigned by the Information System to the submitted DDS. */
        public string $uuid,
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
        $response = self::findResponseElement($body, ['SubmitDdsResponse']);

        if ($response === null) {
            throw new XmlException('Could not find SubmitDdsResponse in SOAP body');
        }

        return new self(uuid: self::childText($response, 'uuid') ?? '');
    }
}
