<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Enums;

use Eudr\Enums\DdsStatus;
use Eudr\Tests\TestCase;

final class DdsStatusTest extends TestCase
{
    public function testFromStringWithValidStatus(): void
    {
        $this->assertSame(DdsStatus::SUBMITTED, DdsStatus::fromString('SUBMITTED'));
        $this->assertSame(DdsStatus::AVAILABLE, DdsStatus::fromString('AVAILABLE'));
        $this->assertSame(DdsStatus::PENDING_CREATION, DdsStatus::fromString('PENDING_CREATION'));
    }

    public function testFromStringReturnsUnknownForInvalidStatus(): void
    {
        $this->assertSame(DdsStatus::UNKNOWN, DdsStatus::fromString('INVALID_STATUS'));
        $this->assertSame(DdsStatus::UNKNOWN, DdsStatus::fromString(''));
    }

    public function testAllStatusValues(): void
    {
        $cases = DdsStatus::cases();
        $this->assertCount(9, $cases);
    }
}
