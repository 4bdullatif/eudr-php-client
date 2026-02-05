<?php

declare(strict_types=1);

namespace Eudr\Tests;

use Eudr\Config\Config;
use Eudr\Config\Credentials;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createConfig(): Config
    {
        return new Config(
            baseUrl: 'https://webgate.ec.europa.eu/tracesnt/ws',
            credentials: new Credentials(
                username: 'testuser',
                authKey: 'testauthkey123',
                clientId: 'test-client-id',
            ),
        );
    }

    protected function fixture(string $name): string
    {
        $path = __DIR__ . '/Fixtures/' . $name;
        $contents = file_get_contents($path);
        if ($contents === false) {
            $this->fail('Could not read fixture file: ' . $path);
        }
        return $contents;
    }
}
