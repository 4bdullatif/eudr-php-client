<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Enums;

use Eudr\Enums\OperatorType;
use Eudr\Tests\TestCase;

final class OperatorTypeTest extends TestCase
{
    public function testOperatorTypeValues(): void
    {
        $this->assertSame('OPERATOR', OperatorType::OPERATOR->value);
        $this->assertSame('TRADER', OperatorType::TRADER->value);
        $this->assertSame('REPRESENTATIVE_OPERATOR', OperatorType::REPRESENTATIVE_OPERATOR->value);
        $this->assertSame('REPRESENTATIVE_TRADER', OperatorType::REPRESENTATIVE_TRADER->value);
    }
}
