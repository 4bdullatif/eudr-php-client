<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Enums;

use Eudr\Enums\ActivityType;
use Eudr\Tests\TestCase;

final class ActivityTypeTest extends TestCase
{
    public function testActivityTypeValues(): void
    {
        $this->assertSame('DOMESTIC', ActivityType::DOMESTIC->value);
        $this->assertSame('TRADE', ActivityType::TRADE->value);
        $this->assertSame('IMPORT', ActivityType::IMPORT->value);
        $this->assertSame('EXPORT', ActivityType::EXPORT->value);
    }
}
