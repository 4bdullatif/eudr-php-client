<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;
use Eudr\Support\Xml;

/**
 * withdrawSd — withdraw a Simplified Declaration (V3).
 */
final class WithdrawSdRequest extends AbstractV3Request
{
    private ?string $sdIdentifier = null;

    private function __construct()
    {
    }

    public static function make(): static
    {
        return new self();
    }

    public function withSdIdentifier(string $sdIdentifier): static
    {
        $clone = clone $this;
        $clone->sdIdentifier = $sdIdentifier;
        return $clone;
    }

    public function getEndpoint(): string
    {
        return AbstractSdStatementRequest::ENDPOINT;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SD_NS_V3 . '/withdrawSd';
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->sdIdentifier === null || $this->sdIdentifier === '') {
            throw new ValidationException('SD identifier is required for withdraw');
        }

        Rules::uuid($this->sdIdentifier, 'SD identifier');
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('sd', Xml::EUDR_SD_NS_V3);

        $request = $builder->createElement('sd:WithdrawSdRequest');
        $builder->getBody()->appendChild($request);
        $request->appendChild($builder->createElement('sd:sdIdentifier', $this->sdIdentifier ?? ''));

        return $builder->toString();
    }
}
