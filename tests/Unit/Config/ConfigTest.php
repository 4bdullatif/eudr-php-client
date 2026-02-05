<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Config;

use Eudr\Config\Config;
use Eudr\Config\Credentials;
use Eudr\Exceptions\ConfigurationException;
use Eudr\Tests\TestCase;

final class ConfigTest extends TestCase
{
    public function testCreatesConfigWithDefaults(): void
    {
        $config = $this->createConfig();

        $this->assertSame('https://webgate.ec.europa.eu/tracesnt/ws', $config->baseUrl);
        $this->assertSame(30, $config->timeout);
        $this->assertTrue($config->validateRequests);
        $this->assertNull($config->logger);
    }

    public function testGetEndpoint(): void
    {
        $config = $this->createConfig();

        $this->assertSame(
            'https://webgate.ec.europa.eu/tracesnt/ws/EUDRSubmissionServiceV2',
            $config->getEndpoint('EUDRSubmissionServiceV2'),
        );
    }

    public function testGetEndpointStripsTrailingSlash(): void
    {
        $config = new Config(
            baseUrl: 'https://example.com/api/',
            credentials: new Credentials('user', 'key', 'client'),
        );

        $this->assertSame(
            'https://example.com/api/service',
            $config->getEndpoint('service'),
        );
    }

    public function testFromArray(): void
    {
        $config = Config::fromArray([
            'baseUrl' => 'https://example.com',
            'username' => 'user',
            'authKey' => 'key',
            'clientId' => 'client',
            'timeout' => 60,
        ]);

        $this->assertSame('https://example.com', $config->baseUrl);
        $this->assertSame(60, $config->timeout);
        $this->assertSame('user', $config->credentials->username);
    }

    public function testFromArrayThrowsOnMissingKeys(): void
    {
        $this->expectException(ConfigurationException::class);

        Config::fromArray(['baseUrl' => 'https://example.com']);
    }

    public function testEmptyBaseUrlThrows(): void
    {
        $this->expectException(ConfigurationException::class);

        new Config(
            baseUrl: '',
            credentials: new Credentials('user', 'key', 'client'),
        );
    }

    public function testZeroTimeoutThrows(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Timeout must be greater than 0');

        new Config(
            baseUrl: 'https://example.com/api',
            credentials: new Credentials('user', 'key', 'client'),
            timeout: 0,
        );
    }

    public function testNegativeTimeoutThrows(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Timeout must be greater than 0');

        new Config(
            baseUrl: 'https://example.com/api',
            credentials: new Credentials('user', 'key', 'client'),
            timeout: -5,
        );
    }

    public function testNonHttpsBaseUrlThrows(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('https://');

        new Config(
            baseUrl: 'http://example.com/api',
            credentials: new Credentials('user', 'key', 'client'),
        );
    }

    public function testHttpsBaseUrlIsCaseInsensitive(): void
    {
        $config = new Config(
            baseUrl: 'HTTPS://example.com/api',
            credentials: new Credentials('user', 'key', 'client'),
        );

        $this->assertSame('HTTPS://example.com/api', $config->baseUrl);
    }

    public function testCredentialsEmptyUsernameThrows(): void
    {
        $this->expectException(ConfigurationException::class);

        new Credentials('', 'key', 'client');
    }

    public function testCredentialsEmptyAuthKeyThrows(): void
    {
        $this->expectException(ConfigurationException::class);

        new Credentials('user', '', 'client');
    }

    public function testCredentialsEmptyClientIdThrows(): void
    {
        $this->expectException(ConfigurationException::class);

        new Credentials('user', 'key', '');
    }

    public function testCredentialsRedactsAuthKeyInDebugInfo(): void
    {
        $credentials = new Credentials('user', 'secret-key', 'client');
        $debug = $credentials->__debugInfo();

        $this->assertSame('user', $debug['username']);
        $this->assertSame('********', $debug['authKey']);
        $this->assertSame('client', $debug['clientId']);
    }

    public function testFromArrayThrowsWithMissingKeys(): void
    {
        $this->expectException(ConfigurationException::class);

        Config::fromArray([]);
    }

    public function testFromArrayThrowsWithNonStringUsername(): void
    {
        $this->expectException(ConfigurationException::class);

        Config::fromArray([
            'baseUrl' => 'https://example.com',
            'username' => 123,
            'authKey' => 'key',
            'clientId' => 'client',
        ]);
    }

    public function testFromArrayThrowsWithNullUsername(): void
    {
        $this->expectException(ConfigurationException::class);

        Config::fromArray([
            'baseUrl' => 'https://example.com',
            'username' => null,
            'authKey' => 'key',
            'clientId' => 'client',
        ]);
    }

    public function testFromArrayThrowsWithEmptyBaseUrl(): void
    {
        $this->expectException(ConfigurationException::class);

        Config::fromArray([
            'baseUrl' => '',
            'username' => 'user',
            'authKey' => 'key',
            'clientId' => 'client',
        ]);
    }

    public function testFromArrayUsesDefaults(): void
    {
        $config = Config::fromArray([
            'baseUrl' => 'https://example.com',
            'username' => 'user',
            'authKey' => 'key',
            'clientId' => 'client',
        ]);

        $this->assertSame(30, $config->timeout);
        $this->assertTrue($config->validateRequests);
    }

    public function testCredentialsDebugInfoRedactsAuthKey(): void
    {
        $credentials = new Credentials('myuser', 'super-secret-auth-key-12345', 'my-client');
        $debug = $credentials->__debugInfo();

        $this->assertArrayHasKey('authKey', $debug);
        $this->assertSame('********', $debug['authKey']);
        $this->assertNotSame('super-secret-auth-key-12345', $debug['authKey']);
        $this->assertSame('myuser', $debug['username']);
        $this->assertSame('my-client', $debug['clientId']);
    }
}
