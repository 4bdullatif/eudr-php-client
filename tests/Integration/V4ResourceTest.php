<?php

declare(strict_types=1);

namespace Eudr\Tests\Integration;

use Eudr\Enums\ActivityType;
use Eudr\Enums\V4\EudrStatus;
use Eudr\EudrClient;
use Eudr\Exceptions\ApiException;
use Eudr\Exceptions\AuthenticationException;
use Eudr\Exceptions\HttpException;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V4\AmendDdsRequest;
use Eudr\Requests\V4\GetSdRequest;
use Eudr\Requests\V4\UpdateSdRequest;
use Eudr\Requests\V4\WithdrawDdsRequest;
use Eudr\Requests\V4\WithdrawSdRequest;
use Eudr\Resources\V4\DdsResource;
use Eudr\Resources\V4\SdResource;
use Eudr\Support\Xml;
use Eudr\Tests\Support\BuildsV4Requests;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;
use Psr\Http\Message\RequestInterface;

final class V4ResourceTest extends TestCase
{
    use BuildsV4Requests;

    private MockClient $http;
    private EudrClient $client;

    protected function setUp(): void
    {
        $this->http = new MockClient();
        $this->client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $this->http,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );
    }

    public function testClientExposesMemoizedV4Resources(): void
    {
        $this->assertInstanceOf(DdsResource::class, $this->client->ddsV4());
        $this->assertSame($this->client->ddsV4(), $this->client->ddsV4());
        $this->assertInstanceOf(SdResource::class, $this->client->sdV4());
        $this->assertSame($this->client->sdV4(), $this->client->sdV4());
    }

    public function testExistingResourcesAreUnchanged(): void
    {
        $this->assertInstanceOf(\Eudr\Resources\DdsResource::class, $this->client->dds());
        $this->assertInstanceOf(\Eudr\Resources\V1\DdsResource::class, $this->client->ddsV1());
        $this->assertInstanceOf(\Eudr\Resources\V3\DdsResource::class, $this->client->ddsV3());
        $this->assertInstanceOf(\Eudr\Resources\V3\SdResource::class, $this->client->sdV3());
    }

    public function testSubmitDds(): void
    {
        $this->respondWith('v4/submit_dds_response.xml');

        $response = $this->client->ddsV4()->submit(self::validDdsSubmit());

        $this->assertSame('071874bd-8c62-4cac-8eb6-b2fbe003410c', $response->uuid);
        $this->assertSame('Example EUDR announcement.', $response->userMessage);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV4', Xml::EUDR_DDS_NS_V4 . '/submitDds', 'dds:SubmitDdsRequest');
    }

    public function testAmendDds(): void
    {
        $this->respondWith('v4/amend_dds_response.xml');

        $response = $this->client->ddsV4()->amend(
            AmendDdsRequest::make()->withUuid(self::DDS_UUID)->withActivityType(ActivityType::IMPORT)->addCommodity(self::ddsCommodity()),
        );

        $this->assertSame(EudrStatus::AVAILABLE, $response->status);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV4', Xml::EUDR_DDS_NS_V4 . '/amendDds', 'dds:AmendDdsRequest');
    }

    public function testWithdrawDds(): void
    {
        $this->respondWith('v4/withdraw_dds_response.xml');

        $response = $this->client->ddsV4()->withdraw(WithdrawDdsRequest::make()->withUuid(self::DDS_UUID));

        $this->assertSame(EudrStatus::WITHDRAWN, $response->status);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV4', Xml::EUDR_DDS_NS_V4 . '/withdrawDds', 'dds:WithdrawDdsRequest');
    }

    public function testDdsRetrievalHelpers(): void
    {
        $this->respondWith('v4/get_dds_response.xml');
        $overview = $this->client->ddsV4()->retrieve(self::DDS_UUID);
        $this->assertSame('26BE7XTVCZAQ2S', $overview->referenceNumber);
        $this->assertSame('Assigned', $overview->referenceNumberInfo);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV4', Xml::EUDR_DDS_NS_V4 . '/getDds', '<dds:uuidList>' . self::DDS_UUID . '</dds:uuidList>');

        $this->respondWith('v4/get_dds_response_multiple.xml');
        $this->assertCount(2, $this->client->ddsV4()->retrieveMany([self::DDS_UUID, self::SD_UUID]));

        $this->respondWith('v4/get_dds_by_internal_reference_response.xml');
        $this->assertSame('SFFCB4Y3', $this->client->ddsV4()->retrieveByReference('26BEDWNW9JD1TN')->verificationNumber);
        $this->assertSentTo(
            'EUDRDueDiligenceStatementServiceV4',
            Xml::EUDR_DDS_NS_V4 . '/getDdsByInternalReference',
            '<dds:internalReference>26BEDWNW9JD1TN</dds:internalReference>',
        );

        $this->respondWith('v4/get_dds_by_internal_reference_response.xml');
        $this->assertCount(1, $this->client->ddsV4()->retrieveAllByReference('26BEDWNW9JD1TN'));
    }

    public function testSdOperations(): void
    {
        $sd = $this->client->sdV4();

        $this->respondWith('v4/submit_sd_response.xml');
        $submitted = $sd->submit(self::validSdSubmit());
        $this->assertSame(self::SD_UUID, $submitted->sdIdentifier);
        $this->assertSame('Example EUDR announcement.', $submitted->userMessage);
        $this->assertSentTo('EUDRSimplifiedDeclarationServiceV4', Xml::EUDR_SD_NS_V4 . '/submitSd', 'sd:SubmitSdRequest');

        $this->respondWith('v4/update_sd_response.xml');
        $updated = $sd->update(
            UpdateSdRequest::make()->withSdIdentifier(self::SD_UUID)->withActivityType(ActivityType::IMPORT)->addCommodity(self::sdCommodity()),
        );
        $this->assertSame(2, $updated->version);
        $this->assertSentTo('EUDRSimplifiedDeclarationServiceV4', Xml::EUDR_SD_NS_V4 . '/updateSd', 'sd:UpdateSdRequest');

        $this->respondWith('v4/withdraw_sd_response.xml');
        $this->assertSame(EudrStatus::WITHDRAWN, $sd->withdraw(WithdrawSdRequest::make()->withSdIdentifier(self::SD_UUID))->status);
        $this->assertSentTo('EUDRSimplifiedDeclarationServiceV4', Xml::EUDR_SD_NS_V4 . '/withdrawSd', 'sd:WithdrawSdRequest');

        $this->respondWith('v4/get_sd_response.xml');
        $this->assertSame('S26BECB39D2GRX', $sd->retrieve(self::SD_UUID, 1)->referenceNumber);
        $this->assertSentTo(
            'EUDRSimplifiedDeclarationServiceV4',
            GetSdRequest::SOAP_ACTION,
            '<eudrCommon:versionNumber>1</eudrCommon:versionNumber>',
        );

        $this->respondWith('v4/get_sd_response.xml');
        $this->assertCount(1, $sd->retrieveMany([self::SD_UUID]));

        $this->respondWith('v4/get_sd_by_internal_reference_response.xml');
        $this->assertSame('H6ORMNTX', $sd->retrieveByReference('REF-0001000087')->verificationNumber);
        $this->assertSentTo(
            'EUDRSimplifiedDeclarationServiceV4',
            Xml::EUDR_SD_NS_V4 . '/getSdByInternalReference',
            '<sd:internalReference>REF-0001000087</sd:internalReference>',
        );

        $this->respondWith('v4/get_sd_by_internal_reference_response.xml');
        $this->assertCount(1, $sd->retrieveAllByReference('REF-0001000087'));
    }

    public function testForOperatorAddsBodyIdentityHeader(): void
    {
        $resource = $this->client->ddsV4()->forOperator('OPID-1');

        $this->assertNotSame($this->client->ddsV4(), $resource);
        $this->assertSame('OPID-1', $resource->getOperatorAccessIdentifier());
        $this->assertNull($this->client->ddsV4()->getOperatorAccessIdentifier());

        $this->respondWith('v4/get_dds_response.xml');
        $resource->retrieve(self::DDS_UUID);
        $this->assertStringContainsString(
            '<body:BodyIdentity xmlns:body="' . Xml::TRACESNT_BODY_NS_V4 . '"><OperatorAccessIdentifier>OPID-1</OperatorAccessIdentifier></body:BodyIdentity>',
            $this->lastBody(),
        );

        // A request-level identifier takes precedence over the resource-level one.
        $this->respondWith('v4/submit_sd_response.xml');
        $this->client->sdV4()->forOperator('OPID-1')->submit(self::validSdSubmit()->withOperatorAccessIdentifier('OPID-2'));
        $this->assertStringContainsString('<OperatorAccessIdentifier>OPID-2</OperatorAccessIdentifier>', $this->lastBody());
        $this->assertStringNotContainsString('OPID-1', $this->lastBody());
    }

    public function testNoBodyIdentityHeaderWithoutOperatorContext(): void
    {
        $this->respondWith('v4/get_dds_response.xml');
        $this->client->ddsV4()->retrieve(self::DDS_UUID);

        $this->assertStringNotContainsString('BodyIdentity', $this->lastBody());
    }

    public function testNotFoundFaultThrowsApiException(): void
    {
        $this->respondWith('v4/dds_not_found_fault.xml');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Statement detail access is possible only over UI');
        $this->client->ddsV4()->retrieve(self::DDS_UUID);
    }

    public function testFaultWithHttp500IsSurfacedAsApiException(): void
    {
        $this->http->addResponse(new Response(500, ['Content-Type' => 'text/xml'], $this->fixture('v4/business_rules_fault.xml')));

        try {
            $this->client->ddsV4()->submit(self::validDdsSubmit());
            $this->fail('Expected exception');
        } catch (ApiException $e) {
            $this->assertNotNull($e->errorResponse);
            $this->assertSame('EUDR-GROUPING-NOT-ALLOWED-ON-AMEND', $e->errorResponse->errors[0]->id);
            $this->assertInstanceOf(HttpException::class, $e->getPrevious());
            $this->assertSame(500, $e->getPrevious()->statusCode);
        }
    }

    public function testHttpErrorWithoutFaultStaysHttpException(): void
    {
        $this->http->addResponse(new Response(502, [], 'Bad Gateway'));

        try {
            $this->client->ddsV4()->retrieve(self::DDS_UUID);
            $this->fail('Expected exception');
        } catch (HttpException $e) {
            $this->assertSame(502, $e->statusCode);
        }

        $this->http->addResponse(new Response(500, [], '<html>not xml'));

        try {
            $this->client->sdV4()->retrieve(self::SD_UUID);
            $this->fail('Expected exception');
        } catch (HttpException $e) {
            $this->assertSame(500, $e->statusCode);
        }
    }

    public function testAuthenticationErrorsAreNotConverted(): void
    {
        $this->http->addResponse(new Response(401, [], $this->fixture('v4/dds_not_found_fault.xml')));

        $this->expectException(AuthenticationException::class);
        $this->client->ddsV4()->retrieve(self::DDS_UUID);
    }

    public function testV2StillSurfacesHttp500AsHttpException(): void
    {
        $this->http->addResponse(new Response(500, ['Content-Type' => 'text/xml'], $this->fixture('soap_fault.xml')));

        $this->expectException(HttpException::class);
        $this->client->dds()->retrieve(self::DDS_UUID);
    }

    public function testBusinessRuleFaultExposesErrorDetails(): void
    {
        $this->respondWith('v4/business_rules_fault.xml');

        try {
            $this->client->ddsV4()->submit(self::validDdsSubmit());
            $this->fail('Expected exception');
        } catch (ApiException $e) {
            $this->assertNotNull($e->errorResponse);
            $this->assertSame('EUDR-GROUPING-NOT-ALLOWED-ON-AMEND', $e->errorResponse->errors[0]->id);
        }
    }

    public function testValidationRunsBeforeSending(): void
    {
        try {
            $this->client->sdV4()->submit(self::validSdSubmit()->withInternalReference(str_repeat('A', 51)));
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('must not exceed 50', $e->getMessage());
        }

        $this->assertCount(0, $this->http->getRequests());
    }

    private function respondWith(string $fixture): void
    {
        $this->http->addResponse(new Response(200, ['Content-Type' => 'text/xml'], $this->fixture($fixture)));
    }

    private function lastRequest(): RequestInterface
    {
        $request = $this->http->getLastRequest();
        $this->assertInstanceOf(RequestInterface::class, $request);

        return $request;
    }

    private function lastBody(): string
    {
        return (string) $this->lastRequest()->getBody();
    }

    private function assertSentTo(string $service, string $soapAction, string $bodyFragment): void
    {
        $request = $this->lastRequest();

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://webgate.ec.europa.eu/tracesnt/ws/' . $service, (string) $request->getUri());
        $this->assertSame($soapAction, $request->getHeaderLine('SOAPAction'));
        $this->assertSame('text/xml; charset=utf-8', $request->getHeaderLine('Content-Type'));
        $this->assertStringContainsString($bodyFragment, $this->lastBody());
    }
}
