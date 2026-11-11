<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;
use Eudr\Support\Xml;

/**
 * getDds — retrieve the overview of one or more Due Diligence Statements by UUID (V3).
 */
final class GetDdsRequest extends AbstractV3Request
{
    public const MAX_UUIDS = 100;

    /** @var list<string> */
    private array $uuids = [];

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
        $clone->uuids = [$uuid];
        return $clone;
    }

    /**
     * @param list<string> $uuids Up to 100 UUIDs per call.
     */
    public function withUuids(array $uuids): static
    {
        $clone = clone $this;
        $clone->uuids = $uuids;
        return $clone;
    }

    public function getEndpoint(): string
    {
        return AbstractDdsStatementRequest::ENDPOINT;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_DDS_NS_V3 . '/getDds';
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->uuids === []) {
            throw new ValidationException('At least one DDS UUID is required');
        }

        if (\count($this->uuids) > self::MAX_UUIDS) {
            throw new ValidationException('Maximum ' . self::MAX_UUIDS . ' UUIDs per request');
        }

        foreach ($this->uuids as $index => $uuid) {
            Rules::uuid($uuid, "DDS UUID #{$index}");
        }
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('dds', Xml::EUDR_DDS_NS_V3);

        $request = $builder->createElement('dds:GetDdsRequest');
        $builder->getBody()->appendChild($request);

        foreach ($this->uuids as $uuid) {
            $request->appendChild($builder->createElement('dds:uuidList', $uuid));
        }

        return $builder->toString();
    }
}
