<?php

declare(strict_types=1);

namespace Eudr\Config;

use Eudr\Exceptions\ConfigurationException;
use Psr\Log\LoggerInterface;

final readonly class Config
{
    public function __construct(
        public string $baseUrl,
        public Credentials $credentials,
        public int $timeout = 30,
        public bool $validateRequests = true,
        public ?LoggerInterface $logger = null,
    ) {
        if ($baseUrl === '') {
            throw new ConfigurationException('Base URL cannot be empty');
        }

        if (!str_starts_with(strtolower($baseUrl), 'https://')) {
            throw new ConfigurationException('Base URL must start with https:// to prevent credential exposure over plain HTTP');
        }

        if ($timeout <= 0) {
            throw new ConfigurationException('Timeout must be greater than 0');
        }
    }

    public function getEndpoint(string $service): string
    {
        return rtrim($this->baseUrl, '/') . '/' . ltrim($service, '/');
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $required = ['baseUrl', 'username', 'authKey', 'clientId'];
        $missing = [];
        foreach ($required as $key) {
            if (!\is_string($data[$key] ?? null)) {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new ConfigurationException(
                'Missing required configuration keys: ' . implode(', ', $missing)
            );
        }

        /** @var string $baseUrl */
        $baseUrl = $data['baseUrl'];
        /** @var string $username */
        $username = $data['username'];
        /** @var string $authKey */
        $authKey = $data['authKey'];
        /** @var string $clientId */
        $clientId = $data['clientId'];

        return new self(
            baseUrl: $baseUrl,
            credentials: new Credentials(
                username: $username,
                authKey: $authKey,
                clientId: $clientId,
            ),
            timeout: \is_int($data['timeout'] ?? null) ? $data['timeout'] : 30,
            validateRequests: \is_bool($data['validateRequests'] ?? null) ? $data['validateRequests'] : true,
        );
    }
}
