<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V4;

use Eudr\Data\SpeciesInfo;
use Eudr\Data\V4\DdsCommodity;
use Eudr\Data\V4\DdsProducer;
use Eudr\Data\V4\GoodsMeasure;
use Eudr\Data\V4\OperatorReferenceNumber;
use Eudr\Data\V4\RepresentedOperator;
use Eudr\Data\V4\SummaryAddress;
use Eudr\Enums\ActivityType;
use Eudr\Enums\V4\IdentifierType;
use Eudr\Enums\V4\OperatorRole;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V4\AmendDdsRequest;
use Eudr\Requests\V4\GetDdsByInternalReferenceRequest;
use Eudr\Requests\V4\GetDdsRequest;
use Eudr\Requests\V4\SubmitDdsRequest;
use Eudr\Requests\V4\WithdrawDdsRequest;
use Eudr\Support\Xml;
use Eudr\Tests\Support\BuildsV4Requests;
use Eudr\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DdsRequestsTest extends TestCase
{
    use BuildsV4Requests;

    public function testEndpointsAndSoapActionsMatchWsdl(): void
    {
        $ns = 'http://ec.europa.eu/tracesnt/certificate/eudr/due-diligence-statement/v4';

        $cases = [
            [SubmitDdsRequest::make(), "{$ns}/submitDds"],
            [AmendDdsRequest::make(), "{$ns}/amendDds"],
            [WithdrawDdsRequest::make(), "{$ns}/withdrawDds"],
            [GetDdsRequest::make(), "{$ns}/getDds"],
            [GetDdsByInternalReferenceRequest::make(), "{$ns}/getDdsByInternalReference"],
        ];

        foreach ($cases as [$request, $action]) {
            $this->assertSame('EUDRDueDiligenceStatementServiceV4', $request->getEndpoint());
            $this->assertSame($action, $request->getSoapAction());
        }
    }

    public function testBuildersAreImmutable(): void
    {
        $original = SubmitDdsRequest::make();
        $modified = $original->withOperatorRole(OperatorRole::OPERATOR);

        $this->assertNotSame($original, $modified);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Operator role is required');
        $original->validate();
    }

    public function testValidRequestsPass(): void
    {
        self::validDdsSubmit()->validate();
        self::fullDdsSubmit()->validate();
        $this->addToAssertionCount(2);
    }

    public function testOmitsOptionalElementsWhenNotSet(): void
    {
        $xml = self::validDdsSubmit()->toSoapXml($this->createConfig());

        $this->assertStringNotContainsString('internalReferenceNumber', $xml);
        $this->assertStringNotContainsString('representedOperator', $xml);
        $this->assertStringNotContainsString('comment', $xml);
        $this->assertStringNotContainsString('groupedDeclarations', $xml);
        $this->assertStringContainsString('<dds:geoLocationConfidential>false</dds:geoLocationConfidential>', $xml);
    }

    public function testDecimalsAreNeverInScientificNotation(): void
    {
        $request = self::validDdsSubmit()->withCommodities([new DdsCommodity(
            description: 'Wood',
            hsHeading: '4401',
            goodsMeasure: new GoodsMeasure(
                netWeight: 1.0E-6,
                supplementaryUnit: 1.0E+9,
                supplementaryUnitQualifier: 'MTQ',
                percentageEstimationOrDeviation: 12.34567,
            ),
            producers: [new DdsProducer('BR', self::geoJson())],
        )]);

        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('<eudrCommon:netWeight>0.000001</eudrCommon:netWeight>', $xml);
        $this->assertStringContainsString('<eudrCommon:supplementaryUnit>1000000000</eudrCommon:supplementaryUnit>', $xml);
        $this->assertStringContainsString(
            '<eudrCommon:percentageEstimationOrDeviation>12.346</eudrCommon:percentageEstimationOrDeviation>',
            $xml,
        );
    }

    public function testSummaryAddressIsSerialisedAfterOperatorAddress(): void
    {
        $xml = self::fullDdsSubmit()->toSoapXml($this->createConfig());

        $this->assertStringContainsString(
            '</eudrCommon:operatorAddress><eudrCommon:summaryAddress><eudrCommon:name>Example GmbH</eudrCommon:name>'
            . '<eudrCommon:country>DE</eudrCommon:country></eudrCommon:summaryAddress><eudrCommon:operatorEmail>',
            $xml,
        );
    }

    public function testEmptySummaryAddressIsAllowed(): void
    {
        $request = self::validDdsSubmit()->withRepresentedOperator(new RepresentedOperator('Op', summaryAddress: new SummaryAddress()));
        $request->validate();

        $this->assertStringContainsString('<eudrCommon:summaryAddress/>', $request->toSoapXml($this->createConfig()));
    }

    public function testFromGeoJsonEncodesGeometry(): void
    {
        $producer = DdsProducer::fromGeoJson('BR', ['type' => 'Point', 'coordinates' => [-47.871234, -15.791234]]);

        $this->assertSame('{"type":"Point","coordinates":[-47.871234,-15.791234]}', base64_decode($producer->geometryGeojson, true));
    }

    /**
     * @return iterable<string, array{SubmitDdsRequest, string}>
     */
    public static function invalidSubmitProvider(): iterable
    {
        $valid = self::validDdsSubmit();
        $commodity = self::ddsCommodity();

        yield 'missing activity type' => [
            SubmitDdsRequest::make()->withOperatorRole(OperatorRole::OPERATOR)->addCommodity($commodity),
            'Activity type is required',
        ];
        yield 'TRADE activity' => [$valid->withActivityType(ActivityType::TRADE), 'TRADE is not supported'];
        yield 'no commodities' => [$valid->withCommodities([]), 'between 1 and 200 commodities'];
        yield 'too many commodities' => [$valid->withCommodities(array_fill(0, 201, $commodity)), 'between 1 and 200 commodities'];
        yield 'internal reference too long' => [$valid->withInternalReference(str_repeat('A', 51)), 'Internal reference number must not exceed 50'];
        yield 'non-EU country of activity' => [$valid->withCountryOfActivity('BR'), 'Country of activity must be an EU Member State'];
        yield 'lowercase border country' => [$valid->withBorderCrossCountry('nl'), 'Border cross country must be an EU Member State'];
        yield 'comment too long' => [$valid->withComment(str_repeat('a', 2001)), 'Comment must not exceed 2000'];
        yield 'representative without represented operator' => [
            $valid->withOperatorRole(OperatorRole::REPRESENTATIVE_OPERATOR),
            'Represented operator is required',
        ];
        yield 'invalid represented operator email' => [
            $valid->withRepresentedOperator(new RepresentedOperator('Op', email: 'not-an-email')),
            'Represented operator email must be a valid email address',
        ];
        yield 'too many operator identifiers' => [
            $valid->withRepresentedOperator(new RepresentedOperator(
                'Op',
                array_fill(0, 13, new OperatorReferenceNumber(IdentifierType::EORI, 'X')),
            )),
            'must not have more than 12 reference numbers',
        ];
        yield 'summary address name too long' => [
            $valid->withRepresentedOperator(new RepresentedOperator('Op', summaryAddress: new SummaryAddress(str_repeat('a', 201)))),
            'summary address name must be between 1 and 200',
        ];
        yield 'summary address invalid country' => [
            $valid->withRepresentedOperator(new RepresentedOperator('Op', summaryAddress: new SummaryAddress(country: 'DEU'))),
            'summary address country must be a valid ISO 3166-1 alpha-2 code',
        ];
        yield 'grouped reference too long' => [$valid->addGroupedDeclaration(str_repeat('A', 15)), 'reference number must not exceed 14'];
        yield 'commodity without producers and no grouping' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0))]),
            'must have at least one producer unless the statement groups other declarations',
        ];
        yield 'description too long' => [
            $valid->withCommodities([new DdsCommodity(str_repeat('a', 151), '4401', new GoodsMeasure(netWeight: 1.0), producers: $commodity->producers)]),
            'description must not exceed 150',
        ];
        yield 'hs heading too long' => [
            $valid->withCommodities([new DdsCommodity('Wood', '44011234567', new GoodsMeasure(netWeight: 1.0), producers: $commodity->producers)]),
            'hsHeading must be 2-10 digits',
        ];
        yield 'net weight missing for import' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(supplementaryUnit: 1.0, supplementaryUnitQualifier: 'MTQ'), producers: $commodity->producers)]),
            'netWeight is required for IMPORT activities',
        ];
        yield 'no quantity for domestic' => [
            $valid->withActivityType(ActivityType::DOMESTIC)->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(), producers: $commodity->producers)]),
            'must provide netWeight or supplementaryUnit',
        ];
        yield 'supplementary unit without qualifier' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0, supplementaryUnit: 2.0), producers: $commodity->producers)]),
            'supplementaryUnitQualifier is required',
        ];
        yield 'qualifier too short' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0, supplementaryUnit: 2.0, supplementaryUnitQualifier: 'M3'), producers: $commodity->producers)]),
            'supplementaryUnitQualifier must be between 3 and 4',
        ];
        yield 'negative net weight' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: -1.0), producers: $commodity->producers)]),
            'netWeight must be between 0 and 9999999999.999999',
        ];
        yield 'species name too long' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0), [new SpeciesInfo(str_repeat('a', 201))], $commodity->producers)]),
            'scientificName must be between 1 and 200',
        ];
        yield 'producer country invalid' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0), producers: [new DdsProducer('BRA', self::geoJson())])]),
            'country must be a valid ISO 3166-1 alpha-2 code',
        ];
        yield 'producer geometry not base64' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0), producers: [new DdsProducer('BR', '{"type":"Point"}')])]),
            'geometryGeojson must be base64 encoded',
        ];
        yield 'producer position zero' => [
            $valid->withCommodities([new DdsCommodity('Wood', '4401', new GoodsMeasure(netWeight: 1.0), producers: [new DdsProducer('BR', self::geoJson(), position: 0)])]),
            'position must be a positive integer',
        ];
        yield 'operator access identifier too long' => [$valid->withOperatorAccessIdentifier(str_repeat('A', 17)), 'must not exceed 16 characters'];
        yield 'operator access identifier empty' => [$valid->withOperatorAccessIdentifier(' '), 'must not be empty'];
    }

    #[DataProvider('invalidSubmitProvider')]
    public function testSubmitValidationErrors(SubmitDdsRequest $request, string $message): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        $request->validate();
    }

    public function testCommodityWithoutProducersIsAllowedWhenGrouping(): void
    {
        self::validDdsSubmit()
            ->withCommodities([new DdsCommodity('Grouped cocoa shipment', '1801', new GoodsMeasure(netWeight: 5000.0))])
            ->addGroupedDeclaration('26FRYUI34JTQKB')
            ->validate();

        $this->addToAssertionCount(1);
    }

    public function testAmendRequiresValidUuid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS UUID must be a valid UUID');
        AmendDdsRequest::make()->withUuid('123')->withActivityType(ActivityType::IMPORT)->addCommodity(self::ddsCommodity())->validate();
    }

    public function testAmendRequiresUuid(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS UUID is required for amend');
        AmendDdsRequest::make()->withActivityType(ActivityType::IMPORT)->addCommodity(self::ddsCommodity())->validate();
    }

    public function testAmendRejectsGroupedDeclarations(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Grouped declarations cannot be added, modified or removed on amend');
        AmendDdsRequest::make()
            ->withUuid(self::DDS_UUID)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(self::ddsCommodity())
            ->addGroupedDeclaration('26FRYUI34JTQKB')
            ->validate();
    }

    public function testAmendXmlContainsUuidAndStatement(): void
    {
        $xml = AmendDdsRequest::make()
            ->withUuid(self::DDS_UUID)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(self::ddsCommodity())
            ->toSoapXml($this->createConfig());

        $this->assertStringContainsString('<dds:AmendDdsRequest><dds:uuid>' . self::DDS_UUID . '</dds:uuid><dds:statement>', $xml);
        $this->assertStringNotContainsString('operatorRole', $xml);
    }

    public function testWithdrawValidation(): void
    {
        WithdrawDdsRequest::make()->withUuid(self::DDS_UUID)->validate();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS UUID is required for withdraw');
        WithdrawDdsRequest::make()->validate();
    }

    public function testGetDdsValidation(): void
    {
        GetDdsRequest::make()->withUuids(array_fill(0, 100, self::DDS_UUID))->validate();
        $this->addToAssertionCount(1);

        try {
            GetDdsRequest::make()->validate();
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertSame('At least one DDS UUID is required', $e->getMessage());
        }

        try {
            GetDdsRequest::make()->withUuids(array_fill(0, 101, self::DDS_UUID))->validate();
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertSame('Maximum 100 UUIDs per request', $e->getMessage());
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS UUID #0 must be a valid UUID');
        GetDdsRequest::make()->withUuid('nope')->validate();
    }

    public function testGetDdsXml(): void
    {
        $xml = GetDdsRequest::make()->withUuids([self::DDS_UUID, self::SD_UUID])->toSoapXml($this->createConfig());

        $this->assertStringContainsString(
            '<dds:GetDdsRequest><dds:uuidList>' . self::DDS_UUID . '</dds:uuidList><dds:uuidList>' . self::SD_UUID . '</dds:uuidList></dds:GetDdsRequest>',
            $xml,
        );
        $this->assertStringContainsString('xmlns:dds="' . Xml::EUDR_DDS_NS_V4 . '"', $xml);
    }

    public function testGetByInternalReferenceValidation(): void
    {
        GetDdsByInternalReferenceRequest::make()->withInternalReference(str_repeat('A', 50))->validate();
        $this->addToAssertionCount(1);

        try {
            GetDdsByInternalReferenceRequest::make()->validate();
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertSame('Internal reference number is required', $e->getMessage());
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Internal reference number must not exceed 50 characters');
        GetDdsByInternalReferenceRequest::make()->withInternalReference(str_repeat('A', 51))->validate();
    }

    public function testValidationIsSkippedWhenDisabledInConfig(): void
    {
        // toSoapXml never validates; Connector validates only when Config::$validateRequests is true.
        $xml = SubmitDdsRequest::make()->toSoapXml($this->createConfig());

        $this->assertStringContainsString('<dds:SubmitDdsRequest>', $xml);
    }
}
