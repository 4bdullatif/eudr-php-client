<?php

declare(strict_types=1);

namespace Eudr\Enums\V4;

/**
 * Lifecycle status of a V4 declaration (EudrStatusType).
 *
 * Unknown values returned by the API map to UNKNOWN; the raw value is kept on the response.
 */
enum EudrStatus: string
{
    case SUBMITTED = 'SUBMITTED';
    case AVAILABLE = 'AVAILABLE';
    case REJECTED = 'REJECTED';
    case WITHDRAWN = 'WITHDRAWN';
    /** Declaration cancelled by the operator before availability. */
    case CANCELLED = 'CANCELLED';
    case ARCHIVED = 'ARCHIVED';
    /** Defined in the schema but not active in the current API release. */
    case SUSPENDED = 'SUSPENDED';
    /** Defined in the schema but not active in the current API release. */
    case UPDATED = 'UPDATED';
    case GROUPED = 'GROUPED';
    case OBSOLETE = 'OBSOLETE';
    case UNKNOWN = 'UNKNOWN';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }
}
