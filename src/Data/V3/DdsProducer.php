<?php

declare(strict_types=1);

namespace Eudr\Data\V3;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;

/**
 * Producer of a DDS V3 commodity (DdsProducerType).
 */
final readonly class DdsProducer
{
    public function __construct(
        /** Country of production, ISO 3166-1 alpha-2. */
        public string $country,
        /** Base64-encoded GeoJSON geometry of the plot of land. */
        public string $geometryGeojson,
        public ?string $name = null,
        public ?int $position = null,
    ) {
    }

    /**
     * Create a producer from a raw (not yet encoded) GeoJSON geometry.
     *
     * @param string|array<mixed> $geoJson GeoJSON as a JSON string or decoded array.
     *
     * @throws ValidationException If the array cannot be JSON-encoded.
     */
    public static function fromGeoJson(
        string $country,
        string|array $geoJson,
        ?string $name = null,
        ?int $position = null,
    ): self {
        if (\is_array($geoJson)) {
            try {
                $geoJson = json_encode($geoJson, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException $e) {
                throw new ValidationException('Producer GeoJSON could not be encoded: ' . $e->getMessage(), 0, $e);
            }
        }

        return new self($country, base64_encode($geoJson), $name, $position);
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Producer'): void
    {
        Rules::countryCode($this->country, "{$label} country");
        Rules::base64($this->geometryGeojson, "{$label} geometryGeojson");

        if ($this->name !== null) {
            Rules::length($this->name, 0, 500, "{$label} name");
        }

        Rules::position($this->position, "{$label} position");
    }
}
