<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Enums\ActivityType;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Quantity information of a V4 commodity (GoodsMeasureType).
 *
 * For IMPORT and EXPORT activities the net mass (kg) is mandatory. For DOMESTIC activities
 * at least the net mass or a supplementary unit quantity must be given. A supplementary unit
 * quantity requires its qualifier (e.g. "MTQ").
 *
 * Values are serialised as xs:decimal: quantities are rounded to 6 fraction digits and the
 * percentage to 3 fraction digits.
 */
final readonly class GoodsMeasure
{
    public function __construct(
        /** Net mass in kilograms. */
        public ?float $netWeight = null,
        /** Supplementary unit quantity (e.g. number of units, volume). */
        public ?float $supplementaryUnit = null,
        /** Supplementary unit type (3-4 characters, e.g. "MTQ", "NAR"). Required with supplementaryUnit. */
        public ?string $supplementaryUnitQualifier = null,
        public ?float $percentageEstimationOrDeviation = null,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function validate(?ActivityType $activityType = null, string $label = 'Goods measure'): void
    {
        if ($this->netWeight !== null) {
            Rules::quantity($this->netWeight, "{$label} netWeight");
        }

        if ($this->supplementaryUnit !== null) {
            Rules::quantity($this->supplementaryUnit, "{$label} supplementaryUnit");

            if ($this->supplementaryUnitQualifier === null) {
                throw new ValidationException("{$label} supplementaryUnitQualifier is required when supplementaryUnit is provided");
            }
        }

        if ($this->supplementaryUnitQualifier !== null) {
            Rules::length($this->supplementaryUnitQualifier, 3, 4, "{$label} supplementaryUnitQualifier");
        }

        if ($this->percentageEstimationOrDeviation !== null && !is_finite($this->percentageEstimationOrDeviation)) {
            throw new ValidationException("{$label} percentageEstimationOrDeviation must be a finite number");
        }

        if (
            ($activityType === ActivityType::IMPORT || $activityType === ActivityType::EXPORT)
            && $this->netWeight === null
        ) {
            throw new ValidationException("{$label} netWeight is required for {$activityType->value} activities");
        }

        if ($this->netWeight === null && $this->supplementaryUnit === null) {
            throw new ValidationException("{$label} must provide netWeight or supplementaryUnit");
        }
    }
}
