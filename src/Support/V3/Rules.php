<?php

declare(strict_types=1);

namespace Eudr\Support\V3;

use Eudr\Exceptions\ValidationException;

/**
 * Validation helpers mirroring the constraints of the V3 XSD (common/v3 schema).
 *
 * @internal
 */
final class Rules
{
    /**
     * EuropeanCountryType: EU Member States plus XI (Northern Ireland).
     */
    public const EUROPEAN_COUNTRIES = [
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
        'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'XI',
    ];

    /** Upper bound of DecimalSixteenTotalSixPrecType. */
    public const MAX_QUANTITY = 9999999999.999999;

    private const UUID_PATTERN = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/D';
    private const EMAIL_PATTERN = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/D';

    private function __construct()
    {
    }

    /**
     * @throws ValidationException
     */
    public static function length(string $value, int $min, int $max, string $label): void
    {
        $length = mb_strlen($value);

        if ($length < $min || $length > $max) {
            throw new ValidationException(
                $min === $max
                    ? "{$label} must be exactly {$min} characters"
                    : ($min === 0
                        ? "{$label} must not exceed {$max} characters"
                        : "{$label} must be between {$min} and {$max} characters"),
            );
        }
    }

    /**
     * @throws ValidationException
     */
    public static function nonBlank(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new ValidationException("{$label} is required");
        }
    }

    /**
     * CountryType: two ASCII letters (ISO 3166-1 alpha-2).
     *
     * @throws ValidationException
     */
    public static function countryCode(string $value, string $label): void
    {
        if (!preg_match('/^[a-zA-Z]{2}$/D', $value)) {
            throw new ValidationException("{$label} must be a valid ISO 3166-1 alpha-2 code (e.g., \"DE\", \"BR\")");
        }
    }

    /**
     * @throws ValidationException
     */
    public static function europeanCountry(string $value, string $label): void
    {
        if (!\in_array($value, self::EUROPEAN_COUNTRIES, true)) {
            throw new ValidationException(
                "{$label} must be an EU Member State code or XI (e.g., \"DE\", \"FR\"), got \"{$value}\"",
            );
        }
    }

    /**
     * @throws ValidationException
     */
    public static function uuid(string $value, string $label): void
    {
        if (!preg_match(self::UUID_PATTERN, $value)) {
            throw new ValidationException("{$label} must be a valid UUID");
        }
    }

    /**
     * @throws ValidationException
     */
    public static function email(string $value, string $label): void
    {
        if (!preg_match(self::EMAIL_PATTERN, $value)) {
            throw new ValidationException("{$label} must be a valid email address");
        }

        self::length($value, 0, 200, $label);
    }

    /**
     * HSHeadingType: 2 to 10 digits.
     *
     * @throws ValidationException
     */
    public static function hsHeading(string $value, string $label): void
    {
        if (!preg_match('/^\d{2,10}$/D', $value)) {
            throw new ValidationException("{$label} must be 2-10 digits");
        }
    }

    /**
     * xs:base64Binary content (whitespace is permitted by the XSD lexical space).
     *
     * @throws ValidationException
     */
    public static function base64(string $value, string $label): void
    {
        $compact = preg_replace('/\s+/', '', $value) ?? '';

        if ($compact === '') {
            throw new ValidationException("{$label} is required");
        }

        if (\strlen($compact) % 4 !== 0 || !preg_match('/^[A-Za-z0-9+\/]+={0,2}$/D', $compact)) {
            throw new ValidationException("{$label} must be base64 encoded");
        }
    }

    /**
     * DecimalSixteenTotalSixPrecType: 0 to 9999999999.999999.
     *
     * @throws ValidationException
     */
    public static function quantity(float $value, string $label): void
    {
        if (!is_finite($value) || $value < 0 || $value > self::MAX_QUANTITY) {
            throw new ValidationException("{$label} must be between 0 and 9999999999.999999");
        }
    }

    /**
     * @throws ValidationException
     */
    public static function position(?int $position, string $label): void
    {
        if ($position !== null && $position < 1) {
            throw new ValidationException("{$label} must be a positive integer");
        }
    }

    /**
     * Format a number as an xs:decimal lexical value (never scientific notation),
     * rounded to the given number of fraction digits, without trailing zeros.
     */
    public static function decimal(float $value, int $fractionDigits): string
    {
        $formatted = number_format($value, $fractionDigits, '.', '');

        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted === '-0' ? '0' : $formatted;
    }
}
