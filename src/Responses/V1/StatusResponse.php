<?php

declare(strict_types=1);

namespace Eudr\Responses\V1;

use Eudr\Responses\Concerns\ParsesStatusResponse;
use Eudr\Responses\Response;
use Eudr\Support\Xml;

final readonly class StatusResponse implements Response
{
    use ParsesStatusResponse;

    private const SUCCESS_STATUS = 'SC_200_OK';

    public function __construct(
        public string $status,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status === self::SUCCESS_STATUS;
    }

    /**
     * @return list<string>
     */
    protected static function getSubmissionNamespaces(): array
    {
        return [Xml::EUDR_SUBMISSION_NS_V1, Xml::EUDR_SUBMISSION_NS_V2];
    }
}
