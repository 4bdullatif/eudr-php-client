<?php

declare(strict_types=1);

namespace Eudr\Tests\Integration;

use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\DdsStatus;
use Eudr\Enums\OperatorType;
use Eudr\EudrClient;
use Eudr\Requests\V1\SubmitDdsRequest;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;

final class DdsResourceV1Test extends TestCase
{
    public function testSubmitDdsV1Flow(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('submit_response_v1.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withInternalReference('V1-TEST-REF-001')
            ->addCommodity(
                Commodity::make()
                    ->position(1)
                    ->description('Tropical hardwood lumber')
                    ->hsHeading('440399')
                    ->netWeight(5000.0)
                    ->addSpeciesInfo(new SpeciesInfo('Swietenia macrophylla', 'Mahogany'))
                    ->addProducer(new Producer('BR', 'eyJ0eXBlIjoiUG9pbnQifQ==', 'Brazilian Forest Co'))
                    ->build(),
            );

        $response = $client->ddsV1()->submit($request);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('EUDR.2024.DE.9876543210', $response->ddsIdentifier);
        $this->assertSame('2024-05-10T08:15:00Z', $response->timestamp);

        // Verify V1 endpoint was used
        $sentRequest = $mockClient->getRequests()[0];
        $this->assertStringContainsString('EUDRSubmissionService', (string) $sentRequest->getUri());
        $this->assertStringNotContainsString('V2', (string) $sentRequest->getUri());
    }

    public function testRetrieveDdsV1Flow(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('retrieve_response_v1.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $response = $client->ddsV1()->retrieve('EUDR.2024.DE.9876543210');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('EUDR.2024.DE.9876543210', $response->identifier);
        $this->assertSame('V1-REF-001', $response->internalReferenceNumber);
        $this->assertSame('REF-2024-V1-001', $response->referenceNumber);
        $this->assertSame('VER-2024-V1-001', $response->verificationNumber);
        $this->assertSame(DdsStatus::AVAILABLE, $response->status);
        $this->assertSame('v1user@example.com', $response->updatedBy);

        // Verify V1 endpoint
        $sentRequest = $mockClient->getRequests()[0];
        $this->assertStringContainsString('EUDRRetrievalService', (string) $sentRequest->getUri());
        $this->assertStringNotContainsString('V2', (string) $sentRequest->getUri());
    }

    public function testRetrieveByReferenceV1Flow(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('retrieve_response_v1.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $response = $client->ddsV1()->retrieveByReference('V1-REF-001');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('EUDR.2024.DE.9876543210', $response->identifier);

        // Verify the request contained reference
        $sentRequest = $mockClient->getRequests()[0];
        $body = (string) $sentRequest->getBody();
        $this->assertStringContainsString('GetDdsInfoByInternalReferenceNumberRequest', $body);
    }

    public function testV1SoapFaultHandledAsException(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('soap_fault.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(new Commodity(
                position: 1,
                description: 'Test',
                hsHeading: '4403',
                speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
                producers: [new Producer('DE', 'geojson')],
            ));

        $this->expectException(\Eudr\Exceptions\ApiException::class);
        $this->expectExceptionMessage('Internal Server Error');
        $client->ddsV1()->submit($request);
    }

    public function testV1RequestUsesV1Namespaces(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('submit_response_v1.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(new Commodity(
                position: 1,
                description: 'Test',
                hsHeading: '4403',
                speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
                producers: [new Producer('DE', 'geojson')],
            ));

        $client->ddsV1()->submit($request);

        $sentRequest = $mockClient->getRequests()[0];
        $body = (string) $sentRequest->getBody();

        // Should contain V1 namespaces
        $this->assertStringContainsString('eudr/submission/v1', $body);
        $this->assertStringContainsString('eudr/model/v1', $body);
        // Should NOT contain V2 namespaces
        $this->assertStringNotContainsString('eudr/submission/v2', $body);
        $this->assertStringNotContainsString('eudr/model/v2', $body);
    }
}
