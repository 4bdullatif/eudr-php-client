<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;
use Eudr\Support\Xml;

/**
 * amendDds — amend an AVAILABLE Due Diligence Statement within the amendment window (V3).
 *
 * The statement fully replaces the previous content. Grouped declarations cannot be set on amend.
 */
final class AmendDdsRequest extends AbstractDdsStatementRequest
{
    private ?string $uuid = null;

    private function __construct()
    {
        parent::__construct();
    }

    public static function make(): static
    {
        return new self();
    }

    /**
     * UUID of the DDS to amend (as returned by submitDds).
     */
    public function withUuid(string $uuid): static
    {
        $clone = clone $this;
        $clone->uuid = $uuid;
        return $clone;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_DDS_NS_V3 . '/amendDds';
    }

    public function validate(): void
    {
        if ($this->uuid === null || $this->uuid === '') {
            throw new ValidationException('DDS UUID is required for amend');
        }

        Rules::uuid($this->uuid, 'DDS UUID');

        parent::validate();

        $this->assertNoGroupedDeclarations('amend');
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createDdsBuilder($config);

        $request = $builder->createElement('dds:AmendDdsRequest');
        $builder->getBody()->appendChild($request);

        self::appendText($builder, $request, 'dds:uuid', $this->uuid ?? '');
        $this->appendStatement($builder, $request, false);

        return $builder->toString();
    }
}
