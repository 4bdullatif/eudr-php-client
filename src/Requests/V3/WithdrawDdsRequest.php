<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;
use Eudr\Support\Xml;

/**
 * withdrawDds — withdraw a Due Diligence Statement within the amendment window (V3).
 */
final class WithdrawDdsRequest extends AbstractV3Request
{
    private ?string $uuid = null;

    private function __construct()
    {
    }

    public static function make(): static
    {
        return new self();
    }

    public function withUuid(string $uuid): static
    {
        $clone = clone $this;
        $clone->uuid = $uuid;
        return $clone;
    }

    public function getEndpoint(): string
    {
        return AbstractDdsStatementRequest::ENDPOINT;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_DDS_NS_V3 . '/withdrawDds';
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->uuid === null || $this->uuid === '') {
            throw new ValidationException('DDS UUID is required for withdraw');
        }

        Rules::uuid($this->uuid, 'DDS UUID');
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('dds', Xml::EUDR_DDS_NS_V3);

        $request = $builder->createElement('dds:WithdrawDdsRequest');
        $builder->getBody()->appendChild($request);
        $request->appendChild($builder->createElement('dds:uuid', $this->uuid ?? ''));

        return $builder->toString();
    }
}
