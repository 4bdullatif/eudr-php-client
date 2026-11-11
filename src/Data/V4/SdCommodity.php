<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Enums\ActivityType;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Commodity entry of a Simplified Declaration (SdCommodityType).
 */
final readonly class SdCommodity
{
    public const MAX_PRODUCERS = 1000;

    /**
     * @param list<SdProducer> $producers
     */
    public function __construct(
        /** Commercial description of the goods (max 150 characters). */
        public string $description,
        /** HS code, 2 to 10 digits. */
        public string $hsHeading,
        public GoodsMeasure $goodsMeasure,
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

        if (\count($this->producers) > self::MAX_PRODUCERS) {
            throw new ValidationException("{$label} must not exceed " . self::MAX_PRODUCERS . ' producers');
        }

        foreach ($this->producers as $index => $producer) {
            $producer->validate("{$label} producer #{$index}");
        }
    }
}
