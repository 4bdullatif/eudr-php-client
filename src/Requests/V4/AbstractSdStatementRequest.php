<?php

declare(strict_types=1);

namespace Eudr\Requests\V4;

use Eudr\Config\Config;
use Eudr\Data\V4\SdCommodity;
use Eudr\Support\Xml;
use Eudr\Support\XmlBuilder;

/**
 * Base class for Simplified Declaration V4 submit and update requests (SimplifiedDeclarationBaseType).
 */
abstract class AbstractSdStatementRequest extends AbstractStatementRequest
{
    public const ENDPOINT = 'EUDRSimplifiedDeclarationServiceV4';

    /** @var list<SdCommodity> */
    protected array $commodities = [];

    public function addCommodity(SdCommodity $commodity): static
    {
        $clone = clone $this;
        $clone->commodities = [...$this->commodities, $commodity];
        return $clone;
    }

    /**
     * @param list<SdCommodity> $commodities
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
        return 'sd';
    }

    /**
     * The V4 SD internal reference uses InternalReferenceNumberType (max 50 characters).
     */
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
        foreach ($this->commodities as $index => $commodity) {
            $commodity->validate($this->activityType, "Commodity #{$index}");
        }
    }

    protected function createSdBuilder(Config $config): XmlBuilder
    {
        $builder = $this->createBuilder($config);
        $builder->addNamespace('sd', Xml::EUDR_SD_NS_V4);
        $builder->addNamespace('eudrCommon', Xml::EUDR_COMMON_NS_V4);

        return $builder;
    }

    protected function appendCommodities(XmlBuilder $builder, \DOMElement $statement): void
    {
        foreach ($this->commodities as $commodity) {
            $commodityEl = $builder->createElement('sd:commodities');
            $statement->appendChild($commodityEl);

            if ($commodity->position !== null) {
                self::appendText($builder, $commodityEl, 'sd:position', (string) $commodity->position);
            }

            self::appendDescriptors($builder, $commodityEl, 'sd:descriptors', $commodity->description, $commodity->goodsMeasure);
            self::appendText($builder, $commodityEl, 'sd:hsHeading', $commodity->hsHeading);

            foreach ($commodity->producers as $producer) {
                $producerEl = $builder->createElement('sd:producers');
                $commodityEl->appendChild($producerEl);

                if ($producer->position !== null) {
                    self::appendText($builder, $producerEl, 'sd:producerPosition', (string) $producer->position);
                }

                self::appendText($builder, $producerEl, 'sd:producerCountry', $producer->country);

                if ($producer->name !== null) {
                    self::appendText($builder, $producerEl, 'sd:producerName', $producer->name);
                }

                $locationEl = $builder->createElement('sd:producerLocation');
                $producerEl->appendChild($locationEl);

                $location = $producer->location;

                if ($location->geometryGeojson !== null) {
                    self::appendText($builder, $locationEl, 'sd:geometryGeojson', $location->geometryGeojson);
                } elseif ($location->postalAddresses !== []) {
                    foreach ($location->postalAddresses as $address) {
                        $addressEl = $builder->createElement('sd:postalAddress');
                        $locationEl->appendChild($addressEl);

                        self::appendText($builder, $addressEl, 'sd:producerStreet', $address->street);
                        self::appendText($builder, $addressEl, 'sd:producerPostalCode', $address->postalCode);
                        self::appendText($builder, $addressEl, 'sd:producerCity', $address->city);
                    }
                } else {
                    foreach ($location->cadastralIdentifiers as $identifier) {
                        self::appendText($builder, $locationEl, 'sd:cadastralIdentifier', $identifier);
                    }
                }
            }
        }
    }
}
