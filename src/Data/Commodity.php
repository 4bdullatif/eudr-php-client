<?php

declare(strict_types=1);

namespace Eudr\Data;

use Eudr\Exceptions\ValidationException;
use Eudr\Requests\Builders\CommodityBuilder;

final readonly class Commodity
{
    /**
     * @param list<SpeciesInfo> $speciesInfo
     * @param list<Producer> $producers
     */
    public function __construct(
        public int $position,
        public string $description,
        public string $hsHeading,
        public ?float $volume = null,
        public ?float $netWeight = null,
        public ?int $numberOfUnits = null,
        public ?string $supplementaryUnit = null,
        public ?string $supplementaryUnitQualifier = null,
        public array $speciesInfo = [],
        public array $producers = [],
        /** V2 only -- ignored when building V1 SOAP requests. */
        public ?float $percentageEstimationOrDeviation = null,
    ) {
    }

    public static function make(): CommodityBuilder
    {
        return new CommodityBuilder();
    }

    public function validate(): void
    {
        if ($this->position < 1) {
            throw new ValidationException('Commodity position must be a positive integer');
        }

        if ($this->description === '') {
            throw new ValidationException('Commodity description is required');
        }

        if (mb_strlen($this->description) > 512) {
            throw new ValidationException('Commodity description must not exceed 512 characters');
        }

        if (!preg_match('/^\d{2,8}$/', $this->hsHeading)) {
            throw new ValidationException('Commodity hsHeading must be 2-8 digits');
        }

        if ($this->supplementaryUnit !== null && mb_strlen($this->supplementaryUnit) > 50) {
            throw new ValidationException('Commodity supplementaryUnit must not exceed 50 characters');
        }

        if ($this->speciesInfo === []) {
            throw new ValidationException('Commodity must have at least one species info');
        }

        foreach ($this->speciesInfo as $species) {
            $species->validate();
        }

        if ($this->volume !== null && $this->volume < 0) {
            throw new ValidationException('Commodity volume must not be negative');
        }

        if ($this->netWeight !== null && $this->netWeight < 0) {
            throw new ValidationException('Commodity netWeight must not be negative');
        }

        if ($this->numberOfUnits !== null && $this->numberOfUnits < 0) {
            throw new ValidationException('Commodity numberOfUnits must not be negative');
        }

        if ($this->producers === [] || \count($this->producers) > 500) {
            throw new ValidationException('Commodity must have between 1 and 500 producers');
        }

        foreach ($this->producers as $producer) {
            $producer->validate();
        }
    }
}
