<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Commodity entry of a DDS V4 statement (DdsCommodityType).
 */
final readonly class DdsCommodity
{
    public const MAX_SPECIES_INFO = 500;
    public const MAX_PRODUCERS = 1000;

    /**
     * @param list<SpeciesInfo> $speciesInfo Required for wood-based products.
     * @param list<DdsProducer> $producers May be empty only when the statement groups other declarations.
     */
    public function __construct(
        /** Commercial description of the goods (max 150 characters). */
        public string $description,
        /** HS code, 2 to 10 digits. */
        public string $hsHeading,
        public GoodsMeasure $goodsMeasure,
        public array $speciesInfo = [],
        public array $producers = [],
        public ?int $position = null,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(?ActivityType $activityType = null, string $label = 'Commodity'): void
    {
        Rules::position($this->position, "{$label} position");
        Rules::nonBlank($this->description, "{$label} description");
        Rules::length($this->description, 0, 150, "{$label} description");
        Rules::hsHeading($this->hsHeading, "{$label} hsHeading");

        $this->goodsMeasure->validate($activityType, "{$label} goods measure");

        if (\count($this->speciesInfo) > self::MAX_SPECIES_INFO) {
            throw new ValidationException("{$label} must not exceed " . self::MAX_SPECIES_INFO . ' species info entries');
        }

        foreach ($this->speciesInfo as $index => $species) {
            $species->validate();

            if ($species->scientificName !== null) {
                Rules::length($species->scientificName, 1, 200, "{$label} species info #{$index} scientificName");
            }

            if ($species->commonName !== null) {
                Rules::length($species->commonName, 1, 200, "{$label} species info #{$index} commonName");
            }
        }

        if (\count($this->producers) > self::MAX_PRODUCERS) {
            throw new ValidationException("{$label} must not exceed " . self::MAX_PRODUCERS . ' producers');
        }

        foreach ($this->producers as $index => $producer) {
            $producer->validate("{$label} producer #{$index}");
        }
    }
}
