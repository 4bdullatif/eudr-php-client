<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V2;

use Eudr\Data\Address;
use Eudr\Data\Commodity;
use Eudr\Data\EconomicOperator;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V2\SubmitDdsRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class SubmitDdsRequestTest extends TestCase
{
    public function testMakeReturnsInstance(): void
    {
        $request = SubmitDdsRequest::make();
        $this->assertInstanceOf(SubmitDdsRequest::class, $request);
    }

    public function testFluentBuilderIsImmutable(): void
    {
        $request1 = SubmitDdsRequest::make();
        $request2 = $request1->withOperatorType(OperatorType::OPERATOR);

        $this->assertNotSame($request1, $request2);
    }

    public function testGetEndpoint(): void
    {
        $request = SubmitDdsRequest::make();
        $this->assertSame('EUDRSubmissionServiceV2', $request->getEndpoint());
    }

    public function testValidateRequiresOperatorType(): void
    {
        $request = SubmitDdsRequest::make()
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Operator type is required');
        $request->validate();
    }

    public function testValidateRequiresActivityType(): void
    {
        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Activity type is required');
        $request->validate();
    }

    public function testValidateRequiresAtLeastOneCommodity(): void
    {
        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('between 1 and 200 commodities');
        $request->validate();
    }

    public function testValidatePassesForValidRequest(): void
    {
        $request = $this->createValidRequest();
        $request->validate();
        $this->addToAssertionCount(1);
    }

    public function testToSoapXmlContainsSoapEnvelope(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('soapenv:Envelope', $xml);
        $this->assertStringContainsString('soapenv:Header', $xml);
        $this->assertStringContainsString('soapenv:Body', $xml);
    }

    public function testToSoapXmlContainsNamespaces(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString(Xml::EUDR_SUBMISSION_NS_V2, $xml);
        $this->assertStringContainsString(Xml::EUDR_MODEL_NS_V2, $xml);
        $this->assertStringContainsString(Xml::TRACESNT_BASE_NS, $xml);
    }

    public function testToSoapXmlContainsSecurityHeader(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('wsse:Security', $xml);
        $this->assertStringContainsString('wsse:UsernameToken', $xml);
        $this->assertStringContainsString('wsse:Username', $xml);
        $this->assertStringContainsString('wsse:Password', $xml);
        $this->assertStringContainsString('wsse:Nonce', $xml);
        $this->assertStringContainsString('wsu:Timestamp', $xml);
        $this->assertStringContainsString('wsu:Created', $xml);
        $this->assertStringContainsString('wsu:Expires', $xml);
        $this->assertStringContainsString('WebServiceClientId', $xml);
        $this->assertStringContainsString('testuser', $xml);
        $this->assertStringContainsString('test-client-id', $xml);
    }

    public function testToSoapXmlContainsSubmitBody(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v2:SubmitStatementRequest', $xml);
        $this->assertStringContainsString('v2:operatorType', $xml);
        $this->assertStringContainsString('OPERATOR', $xml);
        $this->assertStringContainsString('v2:statement', $xml);
        $this->assertStringContainsString('v21:activityType', $xml);
        $this->assertStringContainsString('IMPORT', $xml);
    }

    public function testToSoapXmlContainsCommodityData(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:commodities', $xml);
        $this->assertStringContainsString('v21:position', $xml);
        $this->assertStringContainsString('v21:descriptors', $xml);
        $this->assertStringContainsString('v21:descriptionOfGoods', $xml);
        $this->assertStringContainsString('v21:hsHeading', $xml);
        $this->assertStringContainsString('v21:producers', $xml);
        $this->assertStringContainsString('v21:country', $xml);
        $this->assertStringContainsString('v21:geometryGeojson', $xml);
    }

    public function testToSoapXmlContainsOptionalFields(): void
    {
        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withInternalReference('REF-001')
            ->withCountryOfActivity('DE')
            ->withBorderCrossCountry('NL')
            ->withGeoLocationConfidential(true)
            ->addCommodity($this->createValidCommodity());

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:internalReferenceNumber', $xml);
        $this->assertStringContainsString('REF-001', $xml);
        $this->assertStringContainsString('v21:countryOfActivity', $xml);
        $this->assertStringContainsString('DE', $xml);
        $this->assertStringContainsString('v21:borderCrossCountry', $xml);
        $this->assertStringContainsString('NL', $xml);
        $this->assertStringContainsString('v21:geoLocationConfidential', $xml);
        $this->assertStringContainsString('true', $xml);
    }

    public function testToSoapXmlContainsOperator(): void
    {
        $operator = new EconomicOperator(
            name: 'Test Corp',
            address: new Address(
                street: 'Main Street',
                number: '123',
                postcode: '10115',
                city: 'Berlin',
                countryCode: 'DE',
            ),
            email: 'test@example.com',
            phone: '+49123456789',
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withOperator($operator)
            ->addCommodity($this->createValidCommodity());

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:operator', $xml);
        $this->assertStringContainsString('v21:operatorAddress', $xml);
        $this->assertStringContainsString('v21:name', $xml);
        $this->assertStringContainsString('Test Corp', $xml);
        $this->assertStringContainsString('v21:country', $xml);
        $this->assertStringContainsString('v21:street', $xml);
        $this->assertStringContainsString('Main Street 123', $xml);
        $this->assertStringContainsString('v21:postalCode', $xml);
        $this->assertStringContainsString('10115', $xml);
        $this->assertStringContainsString('v21:city', $xml);
        $this->assertStringContainsString('Berlin', $xml);
        $this->assertStringContainsString('v21:email', $xml);
        $this->assertStringContainsString('test@example.com', $xml);
        $this->assertStringContainsString('v21:phone', $xml);
        // V2 does NOT use v4 namespace elements in operator
        $this->assertStringNotContainsString('v4:name', $xml);
        $this->assertStringNotContainsString('v4:address', $xml);
        $this->assertStringNotContainsString('v11:nameAndAddress', $xml);
    }

    public function testToSoapXmlContainsSpeciesInfo(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Oak wood')
            ->hsHeading('4403')
            ->addSpeciesInfo(new SpeciesInfo('Quercus robur', 'European Oak'))
            ->addProducer(new Producer('DE', 'geojsondata'))
            ->build();

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($commodity);

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:speciesInfo', $xml);
        $this->assertStringContainsString('v21:scientificName', $xml);
        $this->assertStringContainsString('Quercus robur', $xml);
        $this->assertStringContainsString('v21:commonName', $xml);
        $this->assertStringContainsString('European Oak', $xml);
    }

    public function testToSoapXmlContainsGoodsMeasure(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Test')
            ->hsHeading('4403')
            ->volume(100.5)
            ->netWeight(200.3)
            ->numberOfUnits(50)
            ->supplementaryUnit('M3')
            ->supplementaryUnitQualifier('NET')
            ->addProducer(new Producer('DE', 'geojson'))
            ->build();

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($commodity);

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:goodsMeasure', $xml);
        $this->assertStringContainsString('v21:volume', $xml);
        $this->assertStringContainsString('100.5', $xml);
        $this->assertStringContainsString('v21:netWeight', $xml);
        $this->assertStringContainsString('200.3', $xml);
        $this->assertStringContainsString('v21:numberOfUnits', $xml);
        $this->assertStringContainsString('50', $xml);
        $this->assertStringContainsString('v21:supplementaryUnit', $xml);
        $this->assertStringContainsString('M3', $xml);
    }

    public function testToSoapXmlContainsPercentageEstimationOrDeviation(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Test')
            ->hsHeading('4403')
            ->percentageEstimationOrDeviation(5.5)
            ->netWeight(200.3)
            ->addProducer(new Producer('DE', 'geojson'))
            ->build();

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($commodity);

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:percentageEstimationOrDeviation', $xml);
        $this->assertStringContainsString('5.5', $xml);
    }

    public function testToSoapXmlContainsComment(): void
    {
        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withComment('Test comment')
            ->addCommodity($this->createValidCommodity());

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:comment', $xml);
        $this->assertStringContainsString('Test comment', $xml);
    }

    public function testToSoapXmlContainsAssociatedStatements(): void
    {
        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addAssociatedStatement('REF-123', 'VER-456')
            ->addCommodity($this->createValidCommodity());

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:associatedStatements', $xml);
        $this->assertStringContainsString('v21:referenceNumber', $xml);
        $this->assertStringContainsString('REF-123', $xml);
        $this->assertStringContainsString('v21:verificationNumber', $xml);
        $this->assertStringContainsString('VER-456', $xml);
    }

    public function testToSoapXmlContainsOperatorReferenceNumbers(): void
    {
        $operator = new EconomicOperator(
            name: 'Test Corp',
            address: new Address(
                street: 'Main Street',
                number: '123',
                postcode: '10115',
                city: 'Berlin',
                countryCode: 'DE',
            ),
            referenceNumbers: [
                ['identifierType' => 'EORI', 'identifierValue' => 'DE123456789'],
            ],
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withOperator($operator)
            ->addCommodity($this->createValidCommodity());

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v21:referenceNumber', $xml);
        $this->assertStringContainsString('v21:identifierType', $xml);
        $this->assertStringContainsString('EORI', $xml);
        $this->assertStringContainsString('v21:identifierValue', $xml);
        $this->assertStringContainsString('DE123456789', $xml);
    }

    public function testToSoapXmlIsValidXml(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'Generated XML should be valid');
    }

    public function testWithCommoditiesReplacesAll(): void
    {
        $commodity1 = $this->createValidCommodity();
        $commodity2 = new Commodity(
            position: 2,
            description: 'Second',
            hsHeading: '4407',
            speciesInfo: [new SpeciesInfo('Fagus sylvatica', 'Beech')],
            producers: [new Producer('FR', 'geojson')],
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($commodity1)
            ->withCommodities([$commodity2]);

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('Second', $xml);
        $this->assertStringNotContainsString('Test commodity', $xml);
    }

    public function testXmlEscapesSpecialCharacters(): void
    {
        $commodity = Commodity::make()
            ->position(1)
            ->description('Wood & timber <products>')
            ->hsHeading('4403')
            ->addProducer(new Producer('DE', 'geojson', 'O\'Brien & Sons'))
            ->build();

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($commodity);

        $xml = $request->toSoapXml($this->createConfig());

        // Should be valid XML (special chars escaped)
        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'XML with special characters should be valid');
    }

    public function testValidateCallsOperatorValidation(): void
    {
        $operator = new EconomicOperator(
            name: 'Test Corp',
            address: new Address(
                street: 'Main Street',
                number: '123',
                postcode: '10115',
                city: 'Berlin',
                countryCode: 'DE',
            ),
        );

        $request = SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withOperator($operator)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('at least one reference number');
        $request->validate();
    }

    private function createValidCommodity(): Commodity
    {
        return new Commodity(
            position: 1,
            description: 'Test commodity',
            hsHeading: '4403',
            speciesInfo: [new SpeciesInfo('Quercus robur', 'Oak')],
            producers: [new Producer('DE', 'base64geojsondata')],
        );
    }

    private function createValidRequest(): SubmitDdsRequest
    {
        return SubmitDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($this->createValidCommodity());
    }
}
