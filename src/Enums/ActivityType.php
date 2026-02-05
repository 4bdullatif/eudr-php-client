<?php

declare(strict_types=1);

namespace Eudr\Enums;

enum ActivityType: string
{
    case DOMESTIC = 'DOMESTIC';
    case TRADE = 'TRADE';
    case IMPORT = 'IMPORT';
    case EXPORT = 'EXPORT';
}
