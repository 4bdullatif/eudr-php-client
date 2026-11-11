<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Support\V3\Rules;
use Eudr\Support\Xml;

/**
 * getDdsByInternalReference — retrieve DDS overviews by the operator's internal reference number (V3).
 */
final class GetDdsByInternalReferenceRequest extends AbstractV3Request
{
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
        return AbstractDdsStatementRequest::ENDPOINT;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_DDS_NS_V3 . '/getDdsByInternalReference';
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
        $builder->addNamespace('dds', Xml::EUDR_DDS_NS_V3);

        $request = $builder->createElement('dds:GetDdsByInternalReferenceRequest');
        $builder->getBody()->appendChild($request);
        $request->appendChild($builder->createElement('dds:internalReference', $this->internalReference));

        return $builder->toString();
    }
}
