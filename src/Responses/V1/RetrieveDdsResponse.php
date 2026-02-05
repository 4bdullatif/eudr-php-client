<?php

declare(strict_types=1);

namespace Eudr\Responses\V1;

use Eudr\Enums\DdsStatus;
use Eudr\Responses\Concerns\ParsesRetrieveDdsResponse;
use Eudr\Responses\Response;
use Eudr\Support\Xml;

final readonly class RetrieveDdsResponse implements Response
{
    use ParsesRetrieveDdsResponse;

    public function __construct(
        public string $identifier,
        public ?string $internalReferenceNumber = null,
        public ?string $referenceNumber = null,
        public ?string $verificationNumber = null,
        public DdsStatus $status = DdsStatus::UNKNOWN,
        public string $rawStatusValue = '',
        public ?string $updatedBy = null,
        public ?string $rejectionReason = null,
        public ?string $date = null,
        public ?string $communicationToOperatorDate = null,
        public ?string $communicationToOperatorMessage = null,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->identifier !== '';
    }

    /**
     * @return list<string>
     */
    protected static function getRetrievalNamespaces(): array
    {
        return [Xml::EUDR_RETRIEVAL_NS_V1, Xml::EUDR_RETRIEVAL_NS_V2];
    }

    /**
     * @return list<string>
     */
    protected static function getModelNamespaces(): array
    {
        return [Xml::EUDR_MODEL_NS_V1, Xml::EUDR_MODEL_NS_V2];
    }

    protected static function getFallbackModelNamespace(): string
    {
        return Xml::EUDR_MODEL_NS_V1;
    }
}
