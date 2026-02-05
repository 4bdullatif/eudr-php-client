<?php

declare(strict_types=1);

namespace Eudr\Enums;

enum OperatorType: string
{
    case OPERATOR = 'OPERATOR';
    case TRADER = 'TRADER';
    case REPRESENTATIVE_OPERATOR = 'REPRESENTATIVE_OPERATOR';
    case REPRESENTATIVE_TRADER = 'REPRESENTATIVE_TRADER';
}
