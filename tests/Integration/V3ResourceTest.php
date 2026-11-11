<?php

declare(strict_types=1);

namespace Eudr\Tests\Integration;

use Eudr\Enums\ActivityType;
use Eudr\Enums\V3\EudrStatus;
use Eudr\EudrClient;
use Eudr\Exceptions\ApiException;
use Eudr\Exceptions\AuthenticationException;
use Eudr\Exceptions\HttpException;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V3\AmendDdsRequest;
use Eudr\Requests\V3\GetSdRequest;
use Eudr\Requests\V3\UpdateSdRequest;
use Eudr\Requests\V3\WithdrawDdsRequest;
use Eudr\Requests\V3\WithdrawSdRequest;
use Eudr\Resources\V3\DdsResource;
use Eudr\Resources\V3\SdResource;
use Eudr\Support\Xml;
use Eudr\Tests\Support\BuildsV3Requests;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;
use Psr\Http\Message\RequestInterface;

final class V3ResourceTest extends TestCase
{
    use BuildsV3Requests;

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

    public function testClientExposesMemoizedV3Resources(): void
    {
        $this->assertInstanceOf(DdsResource::class, $this->client->ddsV3());
        $this->assertSame($this->client->ddsV3(), $this->client->ddsV3());
        $this->assertInstanceOf(SdResource::class, $this->client->sdV3());
        $this->assertSame($this->client->sdV3(), $this->client->sdV3());
    }

    public function testExistingResourcesAreUnchanged(): void
    {
        $this->assertInstanceOf(\Eudr\Resources\DdsResource::class, $this->client->dds());
        $this->assertInstanceOf(\Eudr\Resources\V1\DdsResource::class, $this->client->ddsV1());
    }

    public function testSubmitDds(): void
    {
        $this->respondWith('v3/submit_dds_response.xml');

        $response = $this->client->ddsV3()->submit(self::validDdsSubmit());

        $this->assertSame('071874bd-8c62-4cac-8eb6-b2fbe003410c', $response->uuid);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV3', Xml::EUDR_DDS_NS_V3 . '/submitDds', 'dds:SubmitDdsRequest');
    }

    public function testAmendDds(): void
    {
        $this->respondWith('v3/amend_dds_response.xml');

        $response = $this->client->ddsV3()->amend(
            AmendDdsRequest::make()->withUuid(self::DDS_UUID)->withActivityType(ActivityType::IMPORT)->addCommodity(self::ddsCommodity()),
        );

        $this->assertSame(EudrStatus::AVAILABLE, $response->status);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV3', Xml::EUDR_DDS_NS_V3 . '/amendDds', 'dds:AmendDdsRequest');
    }

    public function testWithdrawDds(): void
    {
        $this->respondWith('v3/withdraw_dds_response.xml');

        $response = $this->client->ddsV3()->withdraw(WithdrawDdsRequest::make()->withUuid(self::DDS_UUID));

        $this->assertSame(EudrStatus::WITHDRAWN, $response->status);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV3', Xml::EUDR_DDS_NS_V3 . '/withdrawDds', 'dds:WithdrawDdsRequest');
    }

    public function testDdsRetrievalHelpers(): void
    {
        $this->respondWith('v3/get_dds_response.xml');
        $this->assertSame('26BE7XTVCZAQ2S', $this->client->ddsV3()->retrieve(self::DDS_UUID)->referenceNumber);
        $this->assertSentTo('EUDRDueDiligenceStatementServiceV3', Xml::EUDR_DDS_NS_V3 . '/getDds', '<dds:uuidList>' . self::DDS_UUID . '</dds:uuidList>');

        $this->respondWith('v3/get_dds_response_multiple.xml');
        $this->assertCount(2, $this->client->ddsV3()->retrieveMany([self::DDS_UUID, self::SD_UUID]));

        $this->respondWith('v3/get_dds_by_internal_reference_response.xml');
        $this->assertSame('SFFCB4Y3', $this->client->ddsV3()->retrieveByReference('26BEDWNW9JD1TN')->verificationNumber);
        $this->assertSentTo(
            'EUDRDueDiligenceStatementServiceV3',
            Xml::EUDR_DDS_NS_V3 . '/getDdsByInternalReference',
            '<dds:internalReference>26BEDWNW9JD1TN</dds:internalReference>',
        );

        $this->respondWith('v3/get_dds_by_internal_reference_response.xml');
        $this->assertCount(1, $this->client->ddsV3()->retrieveAllByReference('26BEDWNW9JD1TN'));
    }

    public function testSdOperations(): void
    {
        $sd = $this->client->sdV3();

        $this->respondWith('v3/submit_sd_response.xml');
        $this->assertSame(self::SD_UUID, $sd->submit(self::validSdSubmit())->sdIdentifier);
        $this->assertSentTo('EUDRSimplifiedDeclarationServiceV3', Xml::EUDR_SD_NS_V3 . '/submitSd', 'sd:SubmitSdRequest');

        $this->respondWith('v3/update_sd_response.xml');
        $updated = $sd->update(
            UpdateSdRequest::make()->withSdIdentifier(self::SD_UUID)->withActivityType(ActivityType::IMPORT)->addCommodity(self::sdCommodity()),
        );
        $this->assertSame(2, $updated->version);
        $this->assertSentTo('EUDRSimplifiedDeclarationServiceV3', Xml::EUDR_SD_NS_V3 . '/updateSd', 'sd:UpdateSdRequest');

        $this->respondWith('v3/withdraw_sd_response.xml');
        $this->assertSame(EudrStatus::WITHDRAWN, $sd->withdraw(WithdrawSdRequest::make()->withSdIdentifier(self::SD_UUID))->status);
        $this->assertSentTo('EUDRSimplifiedDeclarationServiceV3', Xml::EUDR_SD_NS_V3 . '/withdrawSd', 'sd:WithdrawSdRequest');

        $this->respondWith('v3/get_sd_response.xml');
        $this->assertSame('S26BECB39D2GRX', $sd->retrieve(self::SD_UUID, 1)->referenceNumber);
        $this->assertSentTo(
            'EUDRSimplifiedDeclarationServiceV3',
            GetSdRequest::SOAP_ACTION,
            '<eudrCommon:versionNumber>1</eudrCommon:versionNumber>',
        );

        $this->respondWith('v3/get_sd_response.xml');
        $this->assertCount(1, $sd->retrieveMany([self::SD_UUID]));

        $this->respondWith('v3/get_sd_by_internal_reference_response.xml');
        $this->assertSame('H6ORMNTX', $sd->retrieveByReference('REF-0001000087')->verificationNumber);
        $this->assertSentTo(
            'EUDRSimplifiedDeclarationServiceV3',
            'http://ec.europa.eu/tracesnt/certificate/eudr/due-diligence-statement/v3/getSdByInternalReference',
            '<sd:internalReference>REF-0001000087</sd:internalReference>',
        );

        $this->respondWith('v3/get_sd_by_internal_reference_response.xml');
        $this->assertCount(1, $sd->retrieveAllByReference('REF-0001000087'));
    }

    public function testForOperatorAddsBodyIdentityHeader(): void
    {
        $resource = $this->client->ddsV3()->forOperator('OPID-1');

        $this->assertNotSame($this->client->ddsV3(), $resource);
        $this->assertSame('OPID-1', $resource->getOperatorAccessIdentifier());
        $this->assertNull($this->client->ddsV3()->getOperatorAccessIdentifier());

        $this->respondWith('v3/get_dds_response.xml');
        $resource->retrieve(self::DDS_UUID);
        $this->assertStringContainsString(
            '<body:BodyIdentity xmlns:body="' . Xml::TRACESNT_BODY_NS_V3 . '"><OperatorAccessIdentifier>OPID-1</OperatorAccessIdentifier></body:BodyIdentity>',
            $this->lastBody(),
        );

        // A request-level identifier takes precedence over the resource-level one.
        $this->respondWith('v3/submit_sd_response.xml');
        $this->client->sdV3()->forOperator('OPID-1')->submit(self::validSdSubmit()->withOperatorAccessIdentifier('OPID-2'));
        $this->assertStringContainsString('<OperatorAccessIdentifier>OPID-2</OperatorAccessIdentifier>', $this->lastBody());
        $this->assertStringNotContainsString('OPID-1', $this->lastBody());
    }

    public function testNoBodyIdentityHeaderWithoutOperatorContext(): void
    {
        $this->respondWith('v3/get_dds_response.xml');
        $this->client->ddsV3()->retrieve(self::DDS_UUID);

        $this->assertStringNotContainsString('BodyIdentity', $this->lastBody());
    }

    public function testNotFoundFaultThrowsApiException(): void
    {
        $this->respondWith('v3/dds_not_found_fault.xml');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Statement detail access is possible only over UI');
        $this->client->ddsV3()->retrieve(self::DDS_UUID);
    }

    public function testFaultWithHttp500IsSurfacedAsApiException(): void
    {
        $this->http->addResponse(new Response(500, ['Content-Type' => 'text/xml'], $this->fixture('v3/business_rules_fault.xml')));

        try {
            $this->client->ddsV3()->submit(self::validDdsSubmit());
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
            $this->client->ddsV3()->retrieve(self::DDS_UUID);
            $this->fail('Expected exception');
        } catch (HttpException $e) {
            $this->assertSame(502, $e->statusCode);
        }

        $this->http->addResponse(new Response(500, [], '<html>not xml'));

        try {
            $this->client->sdV3()->retrieve(self::SD_UUID);
            $this->fail('Expected exception');
        } catch (HttpException $e) {
            $this->assertSame(500, $e->statusCode);
        }
    }

    public function testAuthenticationErrorsAreNotConverted(): void
    {
        $this->http->addResponse(new Response(401, [], $this->fixture('v3/dds_not_found_fault.xml')));

        $this->expectException(AuthenticationException::class);
        $this->client->ddsV3()->retrieve(self::DDS_UUID);
    }

    public function testV2StillSurfacesHttp500AsHttpException(): void
    {
        $this->http->addResponse(new Response(500, ['Content-Type' => 'text/xml'], $this->fixture('soap_fault.xml')));

        $this->expectException(HttpException::class);
        $this->client->dds()->retrieve(self::DDS_UUID);
    }

    public function testBusinessRuleFaultExposesErrorDetails(): void
    {
        $this->respondWith('v3/business_rules_fault.xml');

        try {
            $this->client->ddsV3()->submit(self::validDdsSubmit());
            $this->fail('Expected exception');
        } catch (ApiException $e) {
            $this->assertNotNull($e->errorResponse);
            $this->assertSame('EUDR-GROUPING-NOT-ALLOWED-ON-AMEND', $e->errorResponse->errors[0]->id);
        }
    }

    public function testValidationRunsBeforeSending(): void
    {
        try {
            $this->client->sdV3()->submit(self::validSdSubmit()->withInternalReference('THIS-IS-TOO-LONG'));
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('must not exceed 14', $e->getMessage());
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
