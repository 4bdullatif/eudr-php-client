<?php

declare(strict_types=1);

namespace Eudr\Data\V4;

use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;

/**
 * Location of a Simplified Declaration producer (SdProducerLocationType).
 *
 * Exactly one form is used: a GeoJSON geometry, one or more postal addresses,
 * or one or more cadastral identifiers.
 */
final readonly class SdProducerLocation
{
    public const MAX_ENTRIES = 100;

    /**
     * @param list<SdPostalAddress> $postalAddresses
     * @param list<string> $cadastralIdentifiers
     */
    private function __construct(
        public ?string $geometryGeojson = null,
        public array $postalAddresses = [],
        public array $cadastralIdentifiers = [],
    ) {
    }

    /**
     * @param string $base64GeoJson Base64-encoded GeoJSON geometry.
     */
    public static function geoJson(string $base64GeoJson): self
    {
        return new self(geometryGeojson: $base64GeoJson);
    }

    /**
     * Encode a raw GeoJSON geometry (JSON string or decoded array) as base64.
     *
     * @param string|array<mixed> $geoJson
     *
     * @throws ValidationException If the array cannot be JSON-encoded.
     */
    public static function fromRawGeoJson(string|array $geoJson): self
    {
        if (\is_array($geoJson)) {
            try {
                $geoJson = json_encode($geoJson, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (\JsonException $e) {
                throw new ValidationException('Producer GeoJSON could not be encoded: ' . $e->getMessage(), 0, $e);
            }
        }

        return new self(geometryGeojson: base64_encode($geoJson));
    }

    public static function postalAddresses(SdPostalAddress ...$addresses): self
    {
        return new self(postalAddresses: array_values($addresses));
    }

    public static function cadastralIdentifiers(string ...$identifiers): self
    {
        return new self(cadastralIdentifiers: array_values($identifiers));
    }

    /**
     * @throws ValidationException
     */
    public function validate(string $label = 'Producer location'): void
    {
        if ($this->geometryGeojson !== null) {
            Rules::base64($this->geometryGeojson, "{$label} geometryGeojson");

            return;
        }

        if ($this->postalAddresses !== []) {
            if (\count($this->postalAddresses) > self::MAX_ENTRIES) {
                throw new ValidationException("{$label} must not have more than " . self::MAX_ENTRIES . ' postal addresses');
            }

            foreach ($this->postalAddresses as $index => $address) {
                $address->validate("{$label} postal address #{$index}");
            }

            return;
        }

        if ($this->cadastralIdentifiers !== []) {
            if (\count($this->cadastralIdentifiers) > self::MAX_ENTRIES) {
                throw new ValidationException("{$label} must not have more than " . self::MAX_ENTRIES . ' cadastral identifiers');
            }

            foreach ($this->cadastralIdentifiers as $index => $identifier) {
                Rules::nonBlank($identifier, "{$label} cadastral identifier #{$index}");
                Rules::length($identifier, 0, 80, "{$label} cadastral identifier #{$index}");
            }

            return;
        }

        throw new ValidationException("{$label} requires a GeoJSON geometry, postal address or cadastral identifier");
    }
}
