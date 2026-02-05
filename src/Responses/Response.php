<?php

declare(strict_types=1);

namespace Eudr\Responses;

interface Response
{
    public function isSuccess(): bool;
}
