<?php

declare(strict_types=1);

namespace Eudr\Resources\V4;

use Eudr\Requests\V4\AmendDdsRequest;
use Eudr\Requests\V4\GetDdsByInternalReferenceRequest;
use Eudr\Requests\V4\GetDdsRequest;
use Eudr\Requests\V4\SubmitDdsRequest;
use Eudr\Requests\V4\WithdrawDdsRequest;
use Eudr\Responses\V4\ModificationResponse;
use Eudr\Responses\V4\OverviewResponse;
use Eudr\Responses\V4\SubmitDdsResponse;

/**
 * Due Diligence Statement operations of the V4 API (EUDRDueDiligenceStatementServiceV4).
 *
 * getDdsByIdentifiers is not exposed: it has been discontinued as an API operation and
 * always returns a NotFoundException.
 */
final class DdsResource extends AbstractV4Resource
{
    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function submit(SubmitDdsRequest $request): SubmitDdsResponse
    {
        return SubmitDdsResponse::fromEnvelope($this->sendV4($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function amend(AmendDdsRequest $request): ModificationResponse
    {
        return ModificationResponse::fromEnvelope($this->sendV4($request));
    }

    /**
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function withdraw(WithdrawDdsRequest $request): ModificationResponse
    {
        return ModificationResponse::fromEnvelope($this->sendV4($request));
    }

    /**
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function get(GetDdsRequest $request): array
    {
        return OverviewResponse::allFromEnvelope($this->sendV4($request));
    }

    /**
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function getByInternalReference(GetDdsByInternalReferenceRequest $request): array
    {
        return OverviewResponse::allFromEnvelope($this->sendV4($request));
    }

    /**
     * Retrieve the overview of a single DDS by UUID.
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieve(string $uuid): OverviewResponse
    {
        return OverviewResponse::fromEnvelope($this->sendV4(GetDdsRequest::make()->withUuid($uuid)));
    }

    /**
     * Retrieve the overviews of up to 100 DDS by UUID.
     *
     * @param list<string> $uuids
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveMany(array $uuids): array
    {
        return $this->get(GetDdsRequest::make()->withUuids($uuids));
    }

    /**
     * Retrieve the first DDS overview matching an internal reference number.
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveByReference(string $internalReference): OverviewResponse
    {
        return OverviewResponse::fromEnvelope(
            $this->sendV4(GetDdsByInternalReferenceRequest::make()->withInternalReference($internalReference)),
        );
    }

    /**
     * Retrieve all DDS overviews matching an internal reference number.
     *
     * @return list<OverviewResponse>
     *
     * @throws \Eudr\Exceptions\ValidationException
     * @throws \Eudr\Exceptions\ApiException
     * @throws \Eudr\Exceptions\XmlException
     * @throws \Eudr\Exceptions\HttpException
     * @throws \Eudr\Exceptions\AuthenticationException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function retrieveAllByReference(string $internalReference): array
    {
        return $this->getByInternalReference(
            GetDdsByInternalReferenceRequest::make()->withInternalReference($internalReference),
        );
    }
}
