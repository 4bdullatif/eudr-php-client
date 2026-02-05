# EUDR PHP Client

[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-level%209-brightgreen.svg)](https://phpstan.org/)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-8892BF.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

A framework-agnostic PHP client for the [EU Deforestation Regulation (EUDR)](https://environment.ec.europa.eu/topics/forests/deforestation/regulation-deforestation-free-products_en) TracesNT SOAP API. Supports both V1 and V2 endpoints with full WS-Security authentication.

## Features

- Full V1 and V2 API coverage: Submit, Amend, Retract, Retrieve, and cross-supply-chain operations
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

The package supports both V1 and V2 of the EUDR API. **V2 is the current recommended version.**

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
├── Data/             Immutable value objects (Commodity, Producer, Address, etc.)
├── Enums/            OperatorType, ActivityType, DdsStatus
├── Exceptions/       Exception hierarchy
├── Http/             PSR-18 connector and middleware pipeline
│   └── Middleware/   Retry, logging, and custom middleware
├── Requests/         SOAP request builders
│   ├── Builders/     Fluent builders for Commodity, Producer, Operator
│   ├── V1/           V1-specific requests and XML traits
│   └── V2/           V2-specific requests and XML traits
├── Resources/        API resource classes (DDS operations)
├── Responses/        SOAP response parsers
│   ├── V1/           V1 response parsers
│   └── V2/           V2 response parsers
├── Support/          XML utilities, namespace constants, SOAP envelope parser
└── EudrClient.php    Main entry point
```

## License

[MIT](LICENSE)
