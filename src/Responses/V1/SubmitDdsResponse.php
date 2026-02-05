<?php

declare(strict_types=1);

namespace Eudr\Responses\V1;

use Eudr\Responses\Concerns\ParsesSubmitDdsResponse;
use Eudr\Responses\Response;
use Eudr\Support\Xml;

final readonly class SubmitDdsResponse implements Response
{
    use ParsesSubmitDdsResponse;

    public function __construct(
        public string $ddsIdentifier,
        public ?string $timestamp = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->ddsIdentifier !== '';
    }

    /**
     * @return list<string>
     */
    protected static function getSubmissionNamespaces(): array
    {
        return [Xml::EUDR_SUBMISSION_NS_V1, Xml::EUDR_SUBMISSION_NS_V2];
    }
}
