<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Eudr\Config\Config;
use Eudr\Config\Credentials;
use Eudr\Data\Address;
use Eudr\Data\Commodity;
use Eudr\Data\EconomicOperator;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\EudrClient;
use Eudr\Http\Middleware\RetryMiddleware;
use Eudr\Requests\V2\AmendDdsRequest;
use Eudr\Requests\V2\RetractDdsRequest;
use Eudr\Requests\V2\SubmitDdsRequest;

// 1. Configure the client
$config = new Config(
    baseUrl: 'https://webgate.acceptance.ec.europa.eu/tracesnt/ws',
    credentials: new Credentials(
        username: 'your-username',
        authKey: 'your-auth-key',
        clientId: 'your-client-id',
    ),
);

// 2. Create the client with optional middleware
$client = new EudrClient(
    config: $config,
    middleware: [
        new RetryMiddleware(maxAttempts: 3),
    ],
);

// 3. Build a submit request using fluent API
$request = SubmitDdsRequest::make()
    ->withOperatorType(OperatorType::OPERATOR)
    ->withActivityType(ActivityType::IMPORT)
    ->withInternalReference('MY-REF-2024-001')
    ->withCountryOfActivity('DE')
    ->withOperator(new EconomicOperator(
        name: 'Example GmbH',
        address: new Address(
            street: 'Musterstraße',
            number: '42',
            postcode: '10115',
            city: 'Berlin',
            countryCode: 'DE',
        ),
        email: 'contact@example.com',
        referenceNumbers: [
            ['identifierType' => 'EORI', 'identifierValue' => 'DE123456789012345'],
        ],
    ))
    ->addCommodity(
        Commodity::make()
            ->position(1)
            ->description('Tropical hardwood lumber')
            ->hsHeading('440399')
            ->netWeight(5000.0)
            ->addSpeciesInfo(new SpeciesInfo('Swietenia macrophylla', 'Mahogany'))
            ->addProducer(new Producer(
                country: 'BR',
                geometryGeojson: base64_encode('{"type":"Point","coordinates":[-47.87,-15.79]}'),
                name: 'Brazilian Forest Co',
            ))
            ->build(),
    )
    ->addCommodity(
        Commodity::make()
            ->position(2)
            ->description('Oak planks')
            ->hsHeading('440791')
            ->netWeight(100.0)
            ->addSpeciesInfo(new SpeciesInfo('Quercus robur', 'European Oak'))
            ->addProducer(new Producer(
                country: 'DE',
                geometryGeojson: base64_encode('{"type":"Point","coordinates":[13.40,52.52]}'),
            ))
            ->build(),
    );

// 4. Submit the DDS
try {
    $response = $client->dds()->submit($request);
    echo "DDS submitted successfully!\n";
    echo "Identifier: {$response->ddsIdentifier}\n";
    echo "Timestamp: {$response->timestamp}\n";
} catch (\Eudr\Exceptions\ValidationException $e) {
    echo "Validation error: {$e->getMessage()}\n";
} catch (\Eudr\Exceptions\AuthenticationException $e) {
    echo "Authentication failed: {$e->getMessage()}\n";
} catch (\Eudr\Exceptions\ApiException $e) {
    echo "API error: {$e->getMessage()}\n";
}

// 5. Retrieve a DDS by identifier
try {
    $retrieveResponse = $client->dds()->retrieve('EUDR.2024.DE.1234567890');
    echo "\nDDS Retrieved:\n";
    echo "Status: {$retrieveResponse->status->value}\n";
    echo "Reference: {$retrieveResponse->referenceNumber}\n";
    echo "Verification: {$retrieveResponse->verificationNumber}\n";
} catch (\Eudr\Exceptions\ApiException $e) {
    echo "Retrieve error: {$e->getMessage()}\n";
}

// 6. Retrieve by internal reference
try {
    $refResponse = $client->dds()->retrieveByReference('MY-REF-2024-001');
    echo "\nDDS by reference: {$refResponse->identifier}\n";
} catch (\Eudr\Exceptions\ApiException $e) {
    echo "Retrieve by reference error: {$e->getMessage()}\n";
}

// 7. Amend an existing DDS
try {
    $amendRequest = AmendDdsRequest::make()
        ->withDdsIdentifier('EUDR.2024.DE.1234567890')
        ->withOperatorType(OperatorType::OPERATOR)
        ->withActivityType(ActivityType::IMPORT)
        ->withComment('Updated shipment details')
        ->addCommodity(
            Commodity::make()
                ->position(1)
                ->description('Tropical hardwood lumber (amended)')
                ->hsHeading('440399')
                ->netWeight(4800.0)
                ->addSpeciesInfo(new SpeciesInfo('Swietenia macrophylla', 'Mahogany'))
                ->addProducer(new Producer(
                    country: 'BR',
                    geometryGeojson: base64_encode('{"type":"Point","coordinates":[-47.87,-15.79]}'),
                ))
                ->build(),
        );

    $amendResponse = $client->dds()->amend($amendRequest);
    echo "\nDDS amended: {$amendResponse->status}\n";
} catch (\Eudr\Exceptions\ApiException $e) {
    echo "Amend error: {$e->getMessage()}\n";
}

// 8. Retract a DDS
try {
    $retractRequest = RetractDdsRequest::make()
        ->withDdsIdentifier('EUDR.2024.DE.1234567890');

    $retractResponse = $client->dds()->retract($retractRequest);
    echo "\nDDS retracted: {$retractResponse->status}\n";
} catch (\Eudr\Exceptions\ApiException $e) {
    echo "Retract error: {$e->getMessage()}\n";
}

// 9. Using the V1 API
try {
    $v1Response = $client->ddsV1()->retrieve('EUDR.2024.DE.1234567890');
    echo "\nV1 DDS Retrieved: {$v1Response->referenceNumber}\n";
} catch (\Eudr\Exceptions\ApiException $e) {
    echo "V1 retrieve error: {$e->getMessage()}\n";
}
