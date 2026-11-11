<?php

declare(strict_types=1);

namespace Eudr\Requests\V4;

use Eudr\Config\Config;
use Eudr\Data\V4\DdsCommodity;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\Xml;
use Eudr\Support\XmlBuilder;

/**
 * Base class for DDS V4 submit and amend requests (DueDiligenceStatementBaseType).
 */
abstract class AbstractDdsStatementRequest extends AbstractStatementRequest
{
    public const ENDPOINT = 'EUDRDueDiligenceStatementServiceV4';
    public const MAX_PRODUCERS_PER_STATEMENT = 10000;

    /** @var list<DdsCommodity> */
    protected array $commodities = [];

    public function addCommodity(DdsCommodity $commodity): static
    {
        $clone = clone $this;
        $clone->commodities = [...$this->commodities, $commodity];
        return $clone;
    }

    /**
     * @param list<DdsCommodity> $commodities
     */
    public function withCommodities(array $commodities): static
    {
        $clone = clone $this;
        $clone->commodities = $commodities;
        return $clone;
    }

    public function getEndpoint(): string
    {
        return self::ENDPOINT;
    }

    protected function statementPrefix(): string
    {
        return 'dds';
    }

    protected function internalReferenceMaxLength(): int
    {
        return 50;
    }

    protected function commodityCount(): int
    {
        return \count($this->commodities);
    }

    protected function validateCommodities(): void
    {
        $producerCount = 0;

        foreach ($this->commodities as $index => $commodity) {
            $commodity->validate($this->activityType, "Commodity #{$index}");
            $producerCount += \count($commodity->producers);
        }

        if ($producerCount > self::MAX_PRODUCERS_PER_STATEMENT) {
            throw new ValidationException('Must not exceed ' . self::MAX_PRODUCERS_PER_STATEMENT . ' producers per statement');
        }
    }

    protected function createDdsBuilder(Config $config): XmlBuilder
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('dds', Xml::EUDR_DDS_NS_V4);
        $builder->addNamespace('eudrCommon', Xml::EUDR_COMMON_NS_V4);

        return $builder;
    }

    protected function appendCommodities(XmlBuilder $builder, \DOMElement $statement): void
    {
        foreach ($this->commodities as $commodity) {
            $commodityEl = $builder->createElement('dds:commodities');
            $statement->appendChild($commodityEl);

            if ($commodity->position !== null) {
                self::appendText($builder, $commodityEl, 'dds:position', (string) $commodity->position);
            }

            self::appendDescriptors($builder, $commodityEl, 'dds:descriptors', $commodity->description, $commodity->goodsMeasure);
            self::appendText($builder, $commodityEl, 'dds:hsHeading', $commodity->hsHeading);

            foreach ($commodity->speciesInfo as $species) {
                $speciesEl = $builder->createElement('dds:speciesInfo');
                $commodityEl->appendChild($speciesEl);

                if ($species->scientificName !== null) {
                    self::appendText($builder, $speciesEl, 'dds:scientificName', $species->scientificName);
                }

                if ($species->commonName !== null) {
                    self::appendText($builder, $speciesEl, 'dds:commonName', $species->commonName);
                }
            }

            foreach ($commodity->producers as $producer) {
                $producerEl = $builder->createElement('dds:producers');
                $commodityEl->appendChild($producerEl);

                if ($producer->position !== null) {
                    self::appendText($builder, $producerEl, 'dds:position', (string) $producer->position);
                }

                self::appendText($builder, $producerEl, 'dds:country', $producer->country);

                if ($producer->name !== null) {
                    self::appendText($builder, $producerEl, 'dds:name', $producer->name);
                }

                self::appendText($builder, $producerEl, 'dds:geometryGeojson', $producer->geometryGeojson);
            }
        }
    }
}
