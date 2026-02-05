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
use Eudr\Requests\V2\SubmitDdsRequest;
use Eudr\Tests\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Http\Mock\Client as MockClient;

final class DdsResourceTest extends TestCase
{
    public function testSubmitDdsFlow(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('submit_response_v2.xml')),
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
            ->withInternalReference('TEST-REF-001')
            ->addCommodity(
                Commodity::make()
                    ->position(1)
                    ->description('Tropical hardwood lumber')
                    ->hsHeading('440399')
                    ->netWeight(5000.0)
                    ->addSpeciesInfo(new SpeciesInfo('Swietenia macrophylla', 'Mahogany'))
                    ->addProducer(new Producer('BR', 'eyJ0eXBlIjoiUG9pbnQiLCJjb29yZGluYXRlcyI6Wy00Ny44NywtMTUuNzldfQ==', 'Brazilian Forest Co'))
                    ->build(),
            );

        $response = $client->dds()->submit($request);

        $this->assertTrue($response->isSuccess());
        $this->assertSame('EUDR.2024.DE.1234567890', $response->ddsIdentifier);
        $this->assertSame('2024-06-15T10:30:00Z', $response->timestamp);
    }

    public function testRetrieveDdsFlow(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('retrieve_response_v2.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $response = $client->dds()->retrieve('EUDR.2024.DE.1234567890');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('EUDR.2024.DE.1234567890', $response->identifier);
        $this->assertSame('INT-REF-001', $response->internalReferenceNumber);
        $this->assertSame('REF-2024-001', $response->referenceNumber);
        $this->assertSame('VER-2024-001', $response->verificationNumber);
        $this->assertSame(DdsStatus::SUBMITTED, $response->status);
        $this->assertSame('user@example.com', $response->updatedBy);
    }

    public function testRetrieveByReferenceFlow(): void
    {
        $mockClient = new MockClient();
        $mockClient->addResponse(
            new Response(200, ['Content-Type' => 'text/xml'], $this->fixture('retrieve_response_v2.xml')),
        );

        $client = new EudrClient(
            config: $this->createConfig(),
            httpClient: $mockClient,
            requestFactory: new HttpFactory(),
            streamFactory: new HttpFactory(),
        );

        $response = $client->dds()->retrieveByReference('INT-REF-001');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('EUDR.2024.DE.1234567890', $response->identifier);

        // Verify the request contained reference, not identifier
        $sentRequest = $mockClient->getRequests()[0];
        $body = (string) $sentRequest->getBody();
        $this->assertStringContainsString('GetDdsInfoByInternalReferenceNumberRequest', $body);
    }

    public function testSoapFaultHandledAsException(): void
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
        $client->dds()->submit($request);
    }
}
