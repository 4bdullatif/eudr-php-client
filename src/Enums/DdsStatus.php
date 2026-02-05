<?php

declare(strict_types=1);

namespace Eudr\Enums;

enum DdsStatus: string
{
    case PENDING_CREATION = 'PENDING_CREATION';
    case AVAILABLE = 'AVAILABLE';
    case SUBMITTED = 'SUBMITTED';
    case REJECTED = 'REJECTED';
    case RETRACTED = 'RETRACTED';
    case CANCELLED = 'CANCELLED';
    case WITHDRAWN = 'WITHDRAWN';
    case ARCHIVED = 'ARCHIVED';
    case UNKNOWN = 'UNKNOWN';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::UNKNOWN;
    }
}
