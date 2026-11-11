<?php

declare(strict_types=1);

namespace Eudr\Enums\V4;

/**
 * Roles permitted for Simplified Declaration submission in the V4 API (SdOperatorRoleType).
 */
enum SdOperatorRole: string
{
    /** Micro or small primary operator (MSPO). */
    case MICRO_OPERATOR = 'MICRO_OPERATOR';

    /** Authorised representative acting on behalf of a micro or small primary operator. */
    case REPRESENTATIVE_MSPO = 'REPRESENTATIVE_MSPO';

    /** Member State authority acting under Article 4a(4). */
    case MEMBER_STATE = 'MEMBER_STATE';

    public function requiresRepresentedOperator(): bool
    {
        return $this !== self::MICRO_OPERATOR;
    }
}
