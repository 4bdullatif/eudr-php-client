<?php

declare(strict_types=1);

namespace Eudr\Requests\V4;

use Eudr\Config\Config;
use Eudr\Support\V4\Rules;
use Eudr\Support\Xml;

/**
 * getSdByInternalReference — retrieve Simplified Declaration overviews by the operator's
 * internal reference number (V4).
 */
final class GetSdByInternalReferenceRequest extends AbstractV4Request
{
    public const SOAP_ACTION = Xml::EUDR_SD_NS_V4 . '/getSdByInternalReference';

    private string $internalReference = '';

    private function __construct()
    {
    }

    public static function make(): static
    {
        return new self();
    }

    public function withInternalReference(string $reference): static
    {
        $clone = clone $this;
        $clone->internalReference = $reference;
        return $clone;
    }

    public function getEndpoint(): string
    {
        return AbstractSdStatementRequest::ENDPOINT;
    }

    public function getSoapAction(): string
    {
        return self::SOAP_ACTION;
    }

    public function validate(): void
    {
        parent::validate();

        Rules::nonBlank($this->internalReference, 'Internal reference number');
        Rules::length($this->internalReference, 0, 50, 'Internal reference number');
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('sd', Xml::EUDR_SD_NS_V4);

        $request = $builder->createElement('sd:GetSdByInternalReferenceRequest');
        $builder->getBody()->appendChild($request);
        $request->appendChild($builder->createElement('sd:internalReference', $this->internalReference));

        return $builder->toString();
    }
}
