<?php

declare(strict_types=1);

namespace Eudr\Requests;

use Eudr\Config\Config;

interface Request
{
    public function toSoapXml(Config $config): string;

    public function getEndpoint(): string;

    public function getSoapAction(): ?string;

    public function validate(): void;
}
