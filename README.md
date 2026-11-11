# EUDR PHP Client

[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-8892BF.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A framework-agnostic PHP client for the [EU Deforestation Regulation (EUDR)](https://environment.ec.europa.eu/topics/forests/deforestation/regulation-deforestation-free-products_en) TracesNT SOAP API. Supports the V1, V2 and V3 endpoints with full WS-Security authentication.

## Features

- Full V1 and V2 API coverage: Submit, Amend, Retract, Retrieve, and cross-supply-chain operations
- V3 API (Regulation (EU) 2025/2650): Due Diligence Statements and Simplified Declarations, grouped declarations, multi-operator `BodyIdentity` header
- WS-Security password digest authentication
- Immutable request builders with fluent API
- PSR-18 HTTP client with auto-discovery
- Configurable middleware pipeline (retry, logging, custom)
- Strict types and PHPStan level 9 throughout

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Supported Operations](#supported-operations)
- [Usage](#usage)
  - [Submitting a DDS](#submitting-a-dds)
  - [Amending a DDS](#amending-a-dds)
  - [Retracting a DDS](#retracting-a-dds)
  - [Retrieving a DDS](#retrieving-a-dds)
  - [Cross-Supply-Chain Retrieval](#cross-supply-chain-retrieval)
  - [Echo Service](#echo-service)
- [V3 API](#v3-api)
- [Middleware](#middleware)
- [Error Handling](#error-handling)
- [API Versions](#api-versions)
- [Development](#development)
- [Architecture](#architecture)
- [License](#license)

---

## Requirements

- PHP 8.3 or higher
- `ext-dom`, `ext-libxml`, `ext-mbstring`, and `ext-simplexml`
- A PSR-18 HTTP client (e.g. Guzzle, Symfony HttpClient)
- PSR-17 HTTP factories

## Installation

```bash
composer require 4bdullatif/eudr-php-client
```

If you don't already have a PSR-18 HTTP client:

```bash
composer require guzzlehttp/guzzle guzzlehttp/psr7
```

## Quick Start

```php
use Eudr\Config\Config;
use Eudr\Config\Credentials;
use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\EudrClient;
use Eudr\Requests\V2\SubmitDdsRequest;

$client = new EudrClient(
    config: new Config(
        baseUrl: 'https://webgate.acceptance.ec.europa.eu/tracesnt/ws',
        credentials: new Credentials(
            username: 'your-username',
            authKey: 'your-auth-key',
            clientId: 'your-client-id',
        ),
    ),
);

$request = SubmitDdsRequest::make()
    ->withOperatorType(OperatorType::OPERATOR)
    ->withActivityType(ActivityType::IMPORT)
    ->withInternalReference('MY-REF-2024-001')
    ->addCommodity(
        Commodity::make()
            ->position(1)
            ->description('Tropical hardwood lumber')
            ->hsHeading('440399')
            ->netWeight(5000.0)
            ->addSpeciesInfo(new SpeciesInfo('Swietenia macrophylla', 'Mahogany'))
            ->addProducer(new Producer('BR', base64_encode('{"type":"Point","coordinates":[-47.87,-15.79]}')))
            ->build(),
    );

$response = $client->dds()->submit($request);

echo $response->ddsIdentifier; // UUID of the created DDS
```

## Configuration

### Direct construction

```php
use Eudr\Config\Config;
use Eudr\Config\Credentials;

$config = new Config(
    baseUrl: 'https://webgate.acceptance.ec.europa.eu/tracesnt/ws',
    credentials: new Credentials(
        username: 'your-username',
        authKey: 'your-auth-key',
        clientId: 'your-client-id',
    ),
    timeout: 30,              // HTTP timeout in seconds (default: 30)
    validateRequests: true,   // Validate before sending (default: true)
    logger: $psrLogger,       // Optional PSR-3 logger
);
```

### From array

```php
$config = Config::fromArray([
    'baseUrl' => 'https://webgate.acceptance.ec.europa.eu/tracesnt/ws',
    'username' => 'your-username',
    'authKey' => 'your-auth-key',
    'clientId' => 'your-client-id',
    'timeout' => 30,
    'validateRequests' => true,
]);
```

### Providing your own HTTP client

PSR-18/PSR-17 implementations are auto-discovered via [php-http/discovery](https://github.com/php-http/discovery). You can also inject them explicitly:

```php
$client = new EudrClient(
    config: $config,
    httpClient: $psrHttpClient,
    requestFactory: $psrRequestFactory,
    streamFactory: $psrStreamFactory,
    middleware: [
        new RetryMiddleware(maxAttempts: 3, baseDelayMs: 200),
    ],
);
```

## Supported Operations

| Operation                  | Description                          | V1  | V2  |
|----------------------------|--------------------------------------|:---:|:---:|
| Submit                     | Create a new DDS                     |  Y  |  Y  |
| Amend                      | Modify an existing DDS               |  Y  |  Y  |
| Retract                    | Cancel/withdraw a DDS                |  Y  |  Y  |
| Retrieve                   | Get DDS info by UUID                 |  Y  |  Y  |
| RetrieveMany               | Batch retrieve up to 100 UUIDs       |  Y  |  Y  |
| RetrieveByReference        | Get DDS by internal reference number |  Y  |  Y  |
| GetStatementByIdentifiers  | Cross-supply-chain retrieval         |  Y  |  Y  |
| GetReferencedDds           | Follow referenced DDS chain          |  -  |  Y  |
| Echo                       | Test connectivity and authentication |  Y  |  Y  |

V3 uses different operations and data structures; see [V3 API](#v3-api).

---

## Usage

### Submitting a DDS

All request objects are immutable. Each `with*`/`add*` method returns a new instance.

```php
use Eudr\Data\Address;
use Eudr\Data\Commodity;
use Eudr\Data\EconomicOperator;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\Requests\V2\SubmitDdsRequest;

$request = SubmitDdsRequest::make()
    ->withOperatorType(OperatorType::OPERATOR)
    ->withActivityType(ActivityType::IMPORT)
    ->withInternalReference('MY-REF-2024-001')
    ->withCountryOfActivity('DE')
    ->withBorderCrossCountry('NL')
    ->withComment('Annual timber import')
    ->withGeoLocationConfidential()
    ->withOperator(new EconomicOperator(
        name: 'Example GmbH',
        address: new Address(
            street: 'Hauptstrasse',
            number: '42',
            postcode: '10115',
            city: 'Berlin',
            countryCode: 'DE',
        ),
        email: 'contact@example.com',
        phone: '+49 30 1234567',
        referenceNumbers: [
            ['identifierType' => 'EORI', 'identifierValue' => 'DE123456789'],
        ],
    ))
    ->addCommodity(
        Commodity::make()
            ->position(1)
            ->description('Tropical hardwood lumber')
            ->hsHeading('440399')
            ->volume(200.0)
            ->netWeight(5000.0)
            ->numberOfUnits(100)
            ->percentageEstimationOrDeviation(2.5)
            ->supplementaryUnit('m3')
            ->supplementaryUnitQualifier('CBM')
            ->addSpeciesInfo(new SpeciesInfo('Swietenia macrophylla', 'Mahogany'))
            ->addProducer(new Producer(
                country: 'BR',
                geometryGeojson: base64_encode('{"type":"Point","coordinates":[-47.87,-15.79]}'),
                name: 'Brazilian Forest Co',
            ))
            ->build(),
    )
    ->addAssociatedStatement('REF-2024-001', 'VER-2024-001');

$response = $client->dds()->submit($request);

$response->ddsIdentifier; // "3f09ab3f-4c97-4663-8463-89d58f1d646b"
$response->isSuccess();   // true
```

### Amending a DDS

Modify an existing DDS in `AVAILABLE` status. The activity type cannot be changed from the original.

```php
use Eudr\Requests\V2\AmendDdsRequest;

$request = AmendDdsRequest::make()
    ->withDdsIdentifier('3f09ab3f-4c97-4663-8463-89d58f1d646b')
    ->withOperatorType(OperatorType::OPERATOR)
    ->withActivityType(ActivityType::IMPORT)
    ->withInternalReference('MY-REF-2024-001-AMENDED')
    ->addCommodity($commodity);

$response = $client->dds()->amend($request);
$response->isSuccess(); // true (status === 'SC_200_OK')
```

### Retracting a DDS

Cancel a DDS in `SUBMITTED` status or withdraw one in `AVAILABLE` status.

```php
use Eudr\Requests\V2\RetractDdsRequest;

$request = RetractDdsRequest::make()
    ->withDdsIdentifier('3f09ab3f-4c97-4663-8463-89d58f1d646b');

$response = $client->dds()->retract($request);
$response->isSuccess(); // true
```

### Retrieving a DDS

#### Single retrieval

```php
$response = $client->dds()->retrieve('3f09ab3f-4c97-4663-8463-89d58f1d646b');

$response->identifier;                     // UUID
$response->internalReferenceNumber;        // "MY-REF-001"
$response->referenceNumber;                // "24FRXVV3VOS991"
$response->verificationNumber;             // "SEKUYXPP"
$response->status;                         // DdsStatus::AVAILABLE
$response->rejectionReason;                // null or string
$response->communicationToOperatorDate;    // null or CA communication date
$response->communicationToOperatorMessage; // null or CA communication message
```

#### Batch retrieval (up to 100 UUIDs)

```php
$responses = $client->dds()->retrieveMany([
    '3f09ab3f-4c97-4663-8463-89d58f1d646b',
    'a1b2c3d4-e5f6-7890-abcd-ef1234567890',
]);

foreach ($responses as $response) {
    echo $response->identifier . ': ' . $response->status->value . "\n";
}
```

#### By internal reference number

```php
// Returns the first match
$response = $client->dds()->retrieveByReference('MY-REF-001');

// Returns all matches (up to 1000)
$responses = $client->dds()->retrieveAllByReference('MY-REF-001');
```

### Cross-Supply-Chain Retrieval

#### Get statement by identifiers

Retrieve a supplier's DDS using their shared reference and verification numbers:

```php
$response = $client->dds()->getStatementByIdentifiers(
    referenceNumber: '24FRIOBORU2228',
    verificationNumber: 'LWKAOH97',
);

$response->referenceNumber;      // "24FRIOBORU2228"
$response->activityType;         // "IMPORT"
$response->status;               // DdsStatus::AVAILABLE
$response->statusDate;           // "2024-09-23T11:05:00.000"
$response->operatorName;         // "FR DDS OPER TRAD AUTH REP"
$response->operatorCountry;      // "FR"
$response->associatedStatements; // [['referenceNumber' => '...']]
```

#### Get referenced DDS (V2 only)

Follow the chain of referenced DDS documents without requiring the original verification number:

```php
$response = $client->dds()->getReferencedDds(
    referenceNumber: '25FR6CWUOLKN59',
    referenceDdsVerificationNumber: 'encrypted-verification-string',
);
```

### Echo Service

Test connectivity and authentication. Available in acceptance/testing environments only.

```php
$response = $client->echo('hello');
$response->result;      // Echo response from server
$response->isSuccess(); // true
```

---

## V3 API

V3 is the redesign introduced by Regulation (EU) 2025/2650. It replaces the V1/V2 service contracts with new
namespaces, operations and data structures, so it has its own request, data and response classes under the
`V3` namespaces. The V1/V2 classes are unchanged.

| Service                                    | Resource            | Operations                                                                 |
|--------------------------------------------|---------------------|----------------------------------------------------------------------------|
| `EUDRDueDiligenceStatementServiceV3` (DDS) | `$client->ddsV3()`  | `submitDds`, `amendDds`, `withdrawDds`, `getDds`, `getDdsByInternalReference` |
| `EUDRSimplifiedDeclarationServiceV3` (SD)  | `$client->sdV3()`   | `submitSd`, `updateSd`, `withdrawSd`, `getSd`, `getSdByInternalReference`  |

`getDdsByIdentifiers` and `getSdByIdentifiers` are not exposed: the EUDR system has discontinued them as API operations,
and they always return a `NotFoundException`.

The V3 services are published on the EUDR Information System hosts:

| Environment | Base URL                                                     |
|-------------|--------------------------------------------------------------|
| Production  | `https://eudr.webcloud.ec.europa.eu/tracesnt/ws`             |
| Acceptance  | `https://acceptance.eudr.webcloud.ec.europa.eu/tracesnt/ws`  |

### Submitting a DDS (V3)

```php
use Eudr\Data\SpeciesInfo;
use Eudr\Data\V3\DdsCommodity;
use Eudr\Data\V3\DdsProducer;
use Eudr\Data\V3\GoodsMeasure;
use Eudr\Enums\ActivityType;
use Eudr\Enums\V3\OperatorRole;
use Eudr\Requests\V3\SubmitDdsRequest;

$request = SubmitDdsRequest::make()
    ->withOperatorRole(OperatorRole::OPERATOR)
    ->withActivityType(ActivityType::IMPORT)          // DOMESTIC, IMPORT or EXPORT (TRADE is not valid in V3)
    ->withInternalReference('MY-REF-2026-001')        // optional, max 50 characters
    ->withCountryOfActivity('BE')                     // EU Member State or XI
    ->withBorderCrossCountry('BE')
    ->addCommodity(new DdsCommodity(
        description: 'Tropical hardwood lumber',      // max 150 characters
        hsHeading: '4403991000',                      // 2 to 10 digits
        goodsMeasure: new GoodsMeasure(
            netWeight: 5000.0,                        // kg, required for IMPORT/EXPORT
            supplementaryUnit: 20.0,
            supplementaryUnitQualifier: 'MTQ',        // required with supplementaryUnit
        ),
        speciesInfo: [new SpeciesInfo('Swietenia macrophylla', 'Mahogany')],
        producers: [
            DdsProducer::fromGeoJson('BR', ['type' => 'Point', 'coordinates' => [-47.871234, -15.791234]], 'Brazilian Forest Co'),
            // or: new DdsProducer('BR', $base64EncodedGeoJson)
        ],
        position: 1,
    ));

$response = $client->ddsV3()->submit($request);
$response->uuid; // "071874bd-8c62-4cac-8eb6-b2fbe003410c"
```

When the operator role is `REPRESENTATIVE_OPERATOR`, add the represented operator:

```php
use Eudr\Data\V3\OperatorAddress;
use Eudr\Data\V3\OperatorReferenceNumber;
use Eudr\Data\V3\RepresentedOperator;
use Eudr\Enums\V3\IdentifierType;

$request = $request
    ->withOperatorRole(OperatorRole::REPRESENTATIVE_OPERATOR)
    ->withRepresentedOperator(new RepresentedOperator(
        name: 'Example GmbH',
        referenceNumbers: [new OperatorReferenceNumber(IdentifierType::EORI, 'DE123456789012345')],
        address: new OperatorAddress(country: 'DE', street: 'Hauptstrasse 42', postalCode: '10115', city: 'Berlin'),
        email: 'contact@example.com',
    ));
```

#### Grouped declarations

A DDS can group previously submitted, `AVAILABLE` DDS or SD by reference number. The referenced declarations get
the status `GROUPED`. Grouping can only be set at submission. Commodities of a grouping statement do not need
producers, because the grouped declarations carry the geolocation.

```php
$request = $request
    ->addGroupedDeclaration('26FRYUI34JTQKB')
    ->addGroupedDeclaration('26FRLSCV861ZVV');
```

### Amending, withdrawing and retrieving a DDS (V3)

```php
use Eudr\Requests\V3\AmendDdsRequest;
use Eudr\Requests\V3\WithdrawDdsRequest;

// Amend (within the amendment window). The statement replaces the previous content.
$result = $client->ddsV3()->amend(
    AmendDdsRequest::make()
        ->withUuid('071874bd-8c62-4cac-8eb6-b2fbe003410c')
        ->withActivityType(ActivityType::IMPORT)
        ->addCommodity($commodity),
);
$result->status; // EudrStatus::AVAILABLE

// Withdraw
$result = $client->ddsV3()->withdraw(WithdrawDdsRequest::make()->withUuid($uuid));
$result->status; // EudrStatus::WITHDRAWN

// Retrieve overviews
$overview = $client->ddsV3()->retrieve($uuid);
$overview->referenceNumber;    // "26BE7XTVCZAQ2S"
$overview->verificationNumber; // "SFFCB4Y3"
$overview->status;             // EudrStatus::AVAILABLE
$overview->version;            // 1

$client->ddsV3()->retrieveMany([$uuid1, $uuid2]);             // up to 100
$client->ddsV3()->retrieveByReference('MY-REF-2026-001');     // first match
$client->ddsV3()->retrieveAllByReference('MY-REF-2026-001');  // all matches
```

`OverviewResponse` also exposes `internalReferenceNumber`, `rejectionReason`, `communicationToOperatorDate`,
`communicationToOperatorContent`, `date`, `updatedBy` and `rawStatusValue`.

### Simplified Declarations (V3)

Micro or small primary operators (MSPOs), their authorised representatives and Member States submit Simplified
Declarations. A producer location can be a GeoJSON geometry, one or more postal addresses, or one or more cadastral
identifiers.

```php
use Eudr\Data\V3\GoodsMeasure;
use Eudr\Data\V3\SdCommodity;
use Eudr\Data\V3\SdPostalAddress;
use Eudr\Data\V3\SdProducer;
use Eudr\Data\V3\SdProducerLocation;
use Eudr\Enums\V3\SdOperatorRole;
use Eudr\Requests\V3\SubmitSdRequest;
use Eudr\Requests\V3\UpdateSdRequest;
use Eudr\Requests\V3\WithdrawSdRequest;

$request = SubmitSdRequest::make()
    ->withOperatorRole(SdOperatorRole::MICRO_OPERATOR)   // REPRESENTATIVE_MSPO and MEMBER_STATE need withRepresentedOperator()
    ->withInternalReference('SD-REF-0001')               // max 14 characters in V3
    ->withActivityType(ActivityType::DOMESTIC)
    ->addCommodity(new SdCommodity(
        description: 'Cocoa beans',
        hsHeading: '1801',
        goodsMeasure: new GoodsMeasure(netWeight: 200.0),
        producers: [
            new SdProducer('BE', SdProducerLocation::fromRawGeoJson('{"type":"Point","coordinates":[4.351234,50.851234]}')),
            new SdProducer('BE', SdProducerLocation::postalAddresses(new SdPostalAddress('Rue De Geneve 4', '1140', 'Brussels'))),
            new SdProducer('BE', SdProducerLocation::cadastralIdentifiers('BE-BRU-2024-PARCEL-001')),
        ],
    ));

$sdIdentifier = $client->sdV3()->submit($request)->sdIdentifier;

$client->sdV3()->update(UpdateSdRequest::make()->withSdIdentifier($sdIdentifier)->withActivityType(ActivityType::DOMESTIC)->addCommodity($commodity));
$client->sdV3()->withdraw(WithdrawSdRequest::make()->withSdIdentifier($sdIdentifier));

$client->sdV3()->retrieve($sdIdentifier);       // latest version
$client->sdV3()->retrieve($sdIdentifier, 1);    // a specific version
$client->sdV3()->retrieveMany([$id1, $id2]);
$client->sdV3()->retrieveAllByReference('SD-REF-0001');
```

### Acting for several operators (BodyIdentity header)

If your API user is linked to more than one operator, give the operator's Web Service Access Identifier (max 16 characters).
It is sent in the V3 `BodyIdentity` SOAP header.

```php
// For every request sent through this resource instance:
$client->ddsV3()->forOperator('YOUR_IDENTIFIER')->submit($ddsRequest);

// Or for one request (this takes precedence over forOperator()):
$client->sdV3()->submit($sdRequest->withOperatorAccessIdentifier('YOUR_IDENTIFIER'));
```

### Errors (V3)

V3 resources throw the same exceptions as V1/V2. There is one difference: a SOAP fault returned with an HTTP
error status (SOAP 1.1 services use HTTP 500 for faults) is thrown as `ApiException`, not `HttpException`. This makes
the structured EUDR error details available. The original `HttpException` is kept as `getPrevious()`. HTTP 401/403
is still thrown as `AuthenticationException`, and an HTTP error without a SOAP fault body is still an `HttpException`.

```php
use Eudr\Exceptions\ApiException;

try {
    $client->ddsV3()->amend($amendRequest);
} catch (ApiException $e) {
    foreach ($e->errorResponse?->errors ?? [] as $error) {
        $error->id;      // e.g. "EUDR_API_AMEND_DEADLINE_ELAPSED"
        $error->message;
        $error->field;
    }
}
```

### V3 enums

**`OperatorRole`** (DDS) - `OPERATOR`, `REPRESENTATIVE_OPERATOR`

**`SdOperatorRole`** (SD) - `MICRO_OPERATOR`, `REPRESENTATIVE_MSPO`, `MEMBER_STATE`

**`EudrStatus`** - `SUBMITTED`, `AVAILABLE`, `REJECTED`, `WITHDRAWN`, `ARCHIVED`, `SUSPENDED`, `UPDATED`, `GROUPED`, `OBSOLETE`, `UNKNOWN`

**`IdentifierType`** - `EORI`, `VAT`, `GLN`, `TIN`, `CBR`, `CIN`, `DUNS`, `COMPANY_NUMBER`, `COMPANY_REGISTER`, `OTHER_NATIONAL_IDENTIFIER`

V3 requests are validated against the constraints of the official V3 XSD (lengths, patterns, cardinalities, EU country codes)
and against the documented business rules: net mass is required for IMPORT/EXPORT; a supplementary unit needs its
qualifier; grouping is not allowed on amend or update; the represented operator is required for representative roles.
Quantities are written as plain `xs:decimal` values (never in scientific notation), rounded to 6 fraction digits.
The percentage is rounded to 3 fraction digits.

---

## Middleware

The client supports a PSR-7 middleware pipeline for cross-cutting concerns.

### Retry

Retries failed requests on 5xx responses and transport exceptions with exponential backoff:

```php
use Eudr\Http\Middleware\RetryMiddleware;

$client = new EudrClient(
    config: $config,
    middleware: [
        new RetryMiddleware(maxAttempts: 3, baseDelayMs: 100),
    ],
);
```

### Logging

Automatically enabled when a PSR-3 logger is provided in the config. Logs request method/URI and response status/duration.

```php
$config = new Config(
    // ...
    logger: $monologLogger,
);
```

### Custom middleware

Implement the `Middleware` interface:

```php
use Eudr\Http\Middleware\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RateLimitMiddleware implements Middleware
{
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $this->waitForRateLimit();
        $response = $next($request);
        $this->updateRateLimit($response);

        return $response;
    }
}
```

---

## Error Handling

All exceptions extend `Eudr\Exceptions\EudrException`:

```
RuntimeException
  └── EudrException
        ├── ConfigurationException   // Invalid config
        ├── ValidationException      // Request validation failed
        ├── XmlException             // Response XML parsing failed
        ├── ApiException             // SOAP fault from the API
        └── HttpException            // HTTP 4xx/5xx errors
              └── AuthenticationException  // HTTP 401/403
```

```php
use Eudr\Exceptions\ApiException;
use Eudr\Exceptions\AuthenticationException;
use Eudr\Exceptions\HttpException;
use Eudr\Exceptions\ValidationException;
use Eudr\Exceptions\XmlException;

try {
    $response = $client->dds()->submit($request);
} catch (ValidationException $e) {
    // Missing required fields, invalid data formats
} catch (AuthenticationException $e) {
    // HTTP 401/403 - check credentials
    $e->statusCode;
    $e->responseBody;
} catch (ApiException $e) {
    // SOAP fault returned by the EUDR API
} catch (HttpException $e) {
    // Other HTTP errors (5xx, network issues)
    $e->statusCode;
    $e->responseBody;
} catch (XmlException $e) {
    // Response XML could not be parsed
}
```

### SOAP fault error details

The `ErrorResponse` object provides structured access to SOAP fault details from the TracesNT error namespace:

```php
// ErrorResponse fields:
$error->faultCode;   // SOAP fault code
$error->faultString; // Human-readable error message
$error->detail;      // Raw XML detail string
$error->errors;      // ErrorDetail[] - parsed structured errors

// Each ErrorDetail contains:
foreach ($error->errors as $detail) {
    $detail->id;      // e.g. "EUDR-REFERENCE-NUMBER-INVALID"
    $detail->message;  // e.g. "Has not allowed characters"
    $detail->field;    // e.g. "Reference number"
}
```

---

## API Versions

The package supports V1, V2 and V3 of the EUDR API. `dds()` keeps returning the V2 resource so that existing code
works as before. V3 is available through `ddsV3()` and `sdV3()` (see [V3 API](#v3-api)).

> According to the EUDR documentation, V1 and V2 are decommissioned once V3 is released. Move new integrations to V3.

```php
// V2 (default)
$client->dds()->submit($v2Request);
$client->dds()->amend($v2AmendRequest);
$client->dds()->retract($v2RetractRequest);
$client->dds()->retrieve('uuid');
$client->dds()->retrieveMany(['uuid-1', 'uuid-2']);
$client->dds()->retrieveByReference('MY-REF-001');
$client->dds()->retrieveAllByReference('MY-REF-001');
$client->dds()->getStatementByIdentifiers('REF-001', 'VER-001');
$client->dds()->getReferencedDds('REF-001', 'encrypted-verification');

// V1
$client->ddsV1()->submit($v1Request);
$client->ddsV1()->amend($v1AmendRequest);
$client->ddsV1()->retract($v1RetractRequest);
$client->ddsV1()->retrieve('uuid');

// V3
$client->ddsV3()->submit($v3Request);
$client->ddsV3()->amend($v3AmendRequest);
$client->ddsV3()->withdraw($v3WithdrawRequest);
$client->ddsV3()->retrieve('uuid');
$client->sdV3()->submit($sdRequest);
$client->sdV3()->update($sdUpdateRequest);
$client->sdV3()->withdraw($sdWithdrawRequest);
$client->sdV3()->retrieve('uuid', version: 2);

// Echo (connectivity test)
$client->echo('hello');
```

### Key differences between V1 and V2

| Feature          | V1                             | V2                                           |
|------------------|--------------------------------|----------------------------------------------|
| Operator address | `nameAndAddress` (flat string) | `operatorAddress` (structured fields)        |
| GoodsMeasure     | All fields except `percentageEstimationOrDeviation` | All fields including `percentageEstimationOrDeviation` |
| Namespace prefix | `v1`/`v11`                     | `v2`/`v21`                                   |
| Endpoint suffix  | `*ServiceV1`                   | `*ServiceV2`                                 |
| GetReferencedDds | Not available                  | Available                                    |

### Enums

**`OperatorType`** - `OPERATOR`, `TRADER`, `REPRESENTATIVE_OPERATOR`, `REPRESENTATIVE_TRADER`

**`ActivityType`** - `DOMESTIC`, `TRADE`, `IMPORT`, `EXPORT`

**`DdsStatus`** - `PENDING_CREATION`, `AVAILABLE`, `SUBMITTED`, `REJECTED`, `RETRACTED`, `CANCELLED`, `WITHDRAWN`, `ARCHIVED`, `UNKNOWN`

> Unknown API status values gracefully fall back to `DdsStatus::UNKNOWN`.

---

## Development

```bash
composer install          # Install dependencies
composer test             # Run tests
composer analyse          # Static analysis (PHPStan level 9)
composer cs-check         # Code style check
composer cs-fix           # Fix code style
composer check            # Run all checks
```

## Architecture

```
src/
├── Config/           Configuration and credentials
├── Data/             Immutable value objects (Commodity, Producer, Address, etc.; V3/ for V3 types)
├── Enums/            OperatorType, ActivityType, DdsStatus (V3/ for V3 enums)
├── Exceptions/       Exception hierarchy
├── Http/             PSR-18 connector and middleware pipeline
│   └── Middleware/   Retry, logging, and custom middleware
├── Requests/         SOAP request builders
│   ├── Builders/     Fluent builders for Commodity, Producer, Operator
│   ├── V1/           V1-specific requests and XML traits
│   ├── V2/           V2-specific requests and XML traits
│   └── V3/           V3 DDS and Simplified Declaration requests
├── Resources/        API resource classes (DDS operations; V3 DDS and SD)
├── Responses/        SOAP response parsers
│   ├── V1/           V1 response parsers
│   ├── V2/           V2 response parsers
│   └── V3/           V3 submit, modification and overview parsers
├── Support/          XML utilities, namespace constants, SOAP envelope parser
└── EudrClient.php    Main entry point
```

## License

[MIT](LICENSE)
