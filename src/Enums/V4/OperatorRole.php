<?php

declare(strict_types=1);

namespace Eudr\Enums\V4;

/**
 * Roles permitted for DDS submission in the V4 API (OperatorRoleType).
 */
enum OperatorRole: string
{
    /** Places relevant products on the market or exports them (excluding downstream operators). */
    case OPERATOR = 'OPERATOR';

    /** Authorised representative acting on behalf of an operator under written mandate. */
    case REPRESENTATIVE_OPERATOR = 'REPRESENTATIVE_OPERATOR';
}
