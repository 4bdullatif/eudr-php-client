<?php

declare(strict_types=1);

namespace Eudr\Config;

use Eudr\Exceptions\ConfigurationException;

final readonly class Credentials
{
    public function __construct(
        public string $username,
        public string $authKey,
        public string $clientId,
    ) {
        if ($username === '') {
            throw new ConfigurationException('Username cannot be empty');
        }
        if ($authKey === '') {
            throw new ConfigurationException('Auth key cannot be empty');
        }
        if ($clientId === '') {
            throw new ConfigurationException('Client ID cannot be empty');
        }
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return [
            'username' => $this->username,
            'authKey' => '********',
            'clientId' => $this->clientId,
        ];
    }
}
