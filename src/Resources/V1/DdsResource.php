<?php

declare(strict_types=1);

namespace Eudr\Resources\V1;

use Eudr\Requests\V1\AmendDdsRequest;
use Eudr\Requests\V1\GetStatementByIdentifiersRequest;
use Eudr\Requests\V1\RetractDdsRequest;
use Eudr\Requests\V1\RetrieveDdsRequest;
use Eudr\Requests\V1\SubmitDdsRequest;
use Eudr\Resources\Resource;
use Eudr\Responses\SupplierStatementResponse;
use Eudr\Responses\V1\RetrieveDdsResponse;
use Eudr\Responses\V1\StatusResponse;
use Eudr\Responses\V1\SubmitDdsResponse;

final class DdsResource extends Resource
{
    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function submit(SubmitDdsRequest $request): SubmitDdsResponse
    {
        return SubmitDdsResponse::fromSoapBody($this->sendAndParse($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function amend(AmendDdsRequest $request): StatusResponse
    {
        return StatusResponse::fromSoapBody($this->sendAndParse($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retract(RetractDdsRequest $request): StatusResponse
    {
        return StatusResponse::fromSoapBody($this->sendAndParse($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieve(string $identifier): RetrieveDdsResponse
    {
        $request = RetrieveDdsRequest::make()->withIdentifier($identifier);

        return RetrieveDdsResponse::fromSoapBody($this->sendAndParse($request));
    }

    /**
     * Retrieve multiple DDS by their identifiers (up to 100 per call).
     *
     * @param list<string> $identifiers
     * @return list<RetrieveDdsResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveMany(array $identifiers): array
    {
        $request = RetrieveDdsRequest::make()->withIdentifiers($identifiers);

        return RetrieveDdsResponse::allFromSoapBody($this->sendAndParse($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveByReference(string $reference): RetrieveDdsResponse
    {
        $request = RetrieveDdsRequest::make()->withReference($reference);

        return RetrieveDdsResponse::fromSoapBody($this->sendAndParse($request));
    }

    /**
     * Retrieve all DDS matching an internal reference number (up to 1000 results).
     *
     * @return list<RetrieveDdsResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveAllByReference(string $reference): array
    {
        $request = RetrieveDdsRequest::make()->withReference($reference);

        return RetrieveDdsResponse::allFromSoapBody($this->sendAndParse($request));
    }

    /**
     * Cross-supply-chain retrieval using reference + verification numbers.
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getStatementByIdentifiers(string $referenceNumber, string $verificationNumber): SupplierStatementResponse
    {
        $request = GetStatementByIdentifiersRequest::make()
            ->withReferenceNumber($referenceNumber)
            ->withVerificationNumber($verificationNumber);

        return SupplierStatementResponse::fromSoapBody($this->sendAndParse($request));
    }
}
