<?php

declare(strict_types=1);

namespace Eudr\Requests\Builders;

use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;

/**
 * Fluent builder for constructing Commodity value objects.
 */
final class CommodityBuilder
{
    private int $position = 0;
    private string $description = '';
    private string $hsHeading = '';
    private ?float $volume = null;
    private ?float $netWeight = null;
    private ?int $numberOfUnits = null;
    private ?string $supplementaryUnit = null;
    private ?string $supplementaryUnitQualifier = null;
    /** @var list<SpeciesInfo> */
    private array $speciesInfo = [];
    /** @var list<Producer> */
    private array $producers = [];
    private ?float $percentageEstimationOrDeviation = null;

    public function position(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function description(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function hsHeading(string $hsHeading): self
    {
        $this->hsHeading = $hsHeading;
        return $this;
    }

    public function volume(float $volume): self
    {
        $this->volume = $volume;
        return $this;
    }

    public function netWeight(float $netWeight): self
    {
        $this->netWeight = $netWeight;
        return $this;
    }

    public function numberOfUnits(int $numberOfUnits): self
    {
        $this->numberOfUnits = $numberOfUnits;
        return $this;
    }

    public function supplementaryUnit(string $supplementaryUnit): self
    {
        $this->supplementaryUnit = $supplementaryUnit;
        return $this;
    }

    public function supplementaryUnitQualifier(string $qualifier): self
    {
        $this->supplementaryUnitQualifier = $qualifier;
        return $this;
    }

    /**
     * Set the percentage of estimation or deviation (V2 only -- ignored for V1 requests).
     */
    public function percentageEstimationOrDeviation(float $percentage): self
    {
        $this->percentageEstimationOrDeviation = $percentage;
        return $this;
    }

    public function addSpeciesInfo(SpeciesInfo $info): self
    {
        $this->speciesInfo[] = $info;
        return $this;
    }

    public function addProducer(Producer $producer): self
    {
        $this->producers[] = $producer;
        return $this;
    }

    /**
     * @param list<Producer> $producers
     */
    public function withProducers(array $producers): self
    {
        $this->producers = $producers;
        return $this;
    }

    /**
     * @param list<SpeciesInfo> $speciesInfo
     */
    public function withSpeciesInfo(array $speciesInfo): self
    {
        $this->speciesInfo = $speciesInfo;
        return $this;
    }

    public function build(): Commodity
    {
        return new Commodity(
            position: $this->position,
            description: $this->description,
            hsHeading: $this->hsHeading,
            volume: $this->volume,
            netWeight: $this->netWeight,
            numberOfUnits: $this->numberOfUnits,
            supplementaryUnit: $this->supplementaryUnit,
            supplementaryUnitQualifier: $this->supplementaryUnitQualifier,
            speciesInfo: $this->speciesInfo,
            producers: $this->producers,
            percentageEstimationOrDeviation: $this->percentageEstimationOrDeviation,
        );
    }
}
