<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V4;

use Eudr\Data\V4\GoodsMeasure;
use Eudr\Data\V4\SdCommodity;
use Eudr\Data\V4\SdPostalAddress;
use Eudr\Data\V4\SdProducer;
use Eudr\Data\V4\SdProducerLocation;
use Eudr\Enums\ActivityType;
use Eudr\Enums\V4\SdOperatorRole;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V4\GetSdByInternalReferenceRequest;
use Eudr\Requests\V4\GetSdRequest;
use Eudr\Requests\V4\SubmitSdRequest;
use Eudr\Requests\V4\UpdateSdRequest;
use Eudr\Requests\V4\WithdrawSdRequest;
use Eudr\Tests\Support\BuildsV4Requests;
use Eudr\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class SdRequestsTest extends TestCase
{
    use BuildsV4Requests;

    public function testEndpointsAndSoapActionsMatchWsdl(): void
    {
        // Unlike V3, the V4 WSDL binds every SD operation to a SOAPAction in the SD namespace.
        $sd = 'http://ec.europa.eu/tracesnt/certificate/eudr/simplified-declaration/v4';

        $cases = [
            [SubmitSdRequest::make(), "{$sd}/submitSd"],
            [UpdateSdRequest::make(), "{$sd}/updateSd"],
            [WithdrawSdRequest::make(), "{$sd}/withdrawSd"],
            [GetSdRequest::make(), "{$sd}/getSd"],
            [GetSdByInternalReferenceRequest::make(), "{$sd}/getSdByInternalReference"],
        ];

        foreach ($cases as [$request, $action]) {
            $this->assertSame('EUDRSimplifiedDeclarationServiceV4', $request->getEndpoint());
            $this->assertSame($action, $request->getSoapAction());
        }
    }

    public function testValidRequestsPass(): void
    {
        self::validSdSubmit()->validate();
        self::fullSdSubmit()->validate();
        $this->addToAssertionCount(2);
    }

    public function testInternalReferenceAcceptsUpTo50CharactersInV4(): void
    {
        // V4 widened the SD internal reference from ReferenceNumberType (14) to InternalReferenceNumberType (50).
        self::validSdSubmit()->withInternalReference('GROUPED-REF-001')->validate();
        self::validSdSubmit()->withInternalReference(str_repeat('R', 50))->validate();
        $this->addToAssertionCount(2);
    }

    public function testInternalReferenceElementIsAlwaysEmitted(): void
    {
        $xml = self::validSdSubmit()->toSoapXml($this->createConfig());

        $this->assertStringContainsString('<sd:statement><sd:internalReferenceNumber></sd:internalReferenceNumber><sd:activityType>IMPORT</sd:activityType>', $xml);
    }

    public function testProducerWithoutLocationFails(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('requires a GeoJSON geometry, postal address or cadastral identifier');

        self::validSdSubmit()->withCommodities([new SdCommodity(
            'Cocoa',
            '1801',
            new GoodsMeasure(netWeight: 1.0),
            [new SdProducer('BE', SdProducerLocation::postalAddresses())],
        )])->validate();
    }

    public function testFromRawGeoJsonEncodesGeometry(): void
    {
        $location = SdProducerLocation::fromRawGeoJson('{"type":"Point","coordinates":[4.35,50.85]}');

        $this->assertSame('{"type":"Point","coordinates":[4.35,50.85]}', base64_decode((string) $location->geometryGeojson, true));
        $this->assertSame([], $location->postalAddresses);
        $this->assertSame([], $location->cadastralIdentifiers);
    }

    /**
     * @return iterable<string, array{SubmitSdRequest, string}>
     */
    public static function invalidSubmitProvider(): iterable
    {
        $valid = self::validSdSubmit();
        $measure = new GoodsMeasure(netWeight: 1.0);

        yield 'missing role' => [SubmitSdRequest::make()->withActivityType(ActivityType::IMPORT)->addCommodity(self::sdCommodity()), 'Operator role is required'];
        yield 'TRADE activity' => [$valid->withActivityType(ActivityType::TRADE), 'TRADE is not supported'];
        yield 'internal reference over 50 characters' => [$valid->withInternalReference(str_repeat('R', 51)), 'Internal reference number must not exceed 50'];
        yield 'representative MSPO without represented operator' => [
            $valid->withOperatorRole(SdOperatorRole::REPRESENTATIVE_MSPO),
            'Represented operator is required when the operator role is REPRESENTATIVE_MSPO',
        ];
        yield 'member state without represented operator' => [
            $valid->withOperatorRole(SdOperatorRole::MEMBER_STATE),
            'Represented operator is required when the operator role is MEMBER_STATE',
        ];
        yield 'postal address blank street' => [
            $valid->withCommodities([new SdCommodity('Cocoa', '1801', $measure, [
                new SdProducer('BE', SdProducerLocation::postalAddresses(new SdPostalAddress(' ', '1140', 'Brussels'))),
            ])]),
            'street is required',
        ];
        yield 'postal address empty city' => [
            $valid->withCommodities([new SdCommodity('Cocoa', '1801', $measure, [
                new SdProducer('BE', SdProducerLocation::postalAddresses(new SdPostalAddress('Rue 1', '1140', ''))),
            ])]),
            'city must be between 1 and 200',
        ];
        yield 'too many postal addresses' => [
            $valid->withCommodities([new SdCommodity('Cocoa', '1801', $measure, [
                new SdProducer('BE', SdProducerLocation::postalAddresses(...array_fill(0, 101, new SdPostalAddress('Rue 1', '1140', 'Brussels')))),
            ])]),
            'must not have more than 100 postal addresses',
        ];
        yield 'cadastral identifier too long' => [
            $valid->withCommodities([new SdCommodity('Cocoa', '1801', $measure, [
                new SdProducer('BE', SdProducerLocation::cadastralIdentifiers(str_repeat('a', 81))),
            ])]),
            'must not exceed 80',
        ];
        yield 'producer name too long' => [
            $valid->withCommodities([new SdCommodity('Cocoa', '1801', $measure, [
                new SdProducer('BE', SdProducerLocation::geoJson(self::geoJson()), str_repeat('a', 501)),
            ])]),
            'name must not exceed 500',
        ];
        yield 'invalid geojson' => [
            $valid->withCommodities([new SdCommodity('Cocoa', '1801', $measure, [
                new SdProducer('BE', SdProducerLocation::geoJson('')),
            ])]),
            'geometryGeojson is required',
        ];
    }

    #[DataProvider('invalidSubmitProvider')]
    public function testSubmitValidationErrors(SubmitSdRequest $request, string $message): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);
        $request->validate();
    }

    public function testUpdateValidation(): void
    {
        $update = UpdateSdRequest::make()
            ->withSdIdentifier(self::SD_UUID)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(self::sdCommodity());
        $update->validate();
        $this->addToAssertionCount(1);

        // The V4 SD schema dropped the V3 "no grouping on update" rule, so the client does not block it.
        $grouped = $update->addGroupedDeclaration('S26BECB39D2GRX');
        $grouped->validate();
        $this->assertStringContainsString(
            '<sd:groupedDeclarations><eudrCommon:groupedDeclaration>S26BECB39D2GRX</eudrCommon:groupedDeclaration></sd:groupedDeclarations>',
            $grouped->toSoapXml($this->createConfig()),
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SD identifier is required for update');
        UpdateSdRequest::make()->withActivityType(ActivityType::IMPORT)->addCommodity(self::sdCommodity())->validate();
    }

    public function testWithdrawXml(): void
    {
        $xml = WithdrawSdRequest::make()->withSdIdentifier(self::SD_UUID)->toSoapXml($this->createConfig());

        $this->assertStringContainsString('<sd:WithdrawSdRequest><sd:sdIdentifier>' . self::SD_UUID . '</sd:sdIdentifier></sd:WithdrawSdRequest>', $xml);
    }

    public function testWithdrawRequiresValidIdentifier(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SD identifier must be a valid UUID');
        WithdrawSdRequest::make()->withSdIdentifier('S26BECB39D2GRX')->validate();
    }

    public function testGetSdXmlWithOptionalVersion(): void
    {
        $xml = GetSdRequest::make()->addUuid(self::SD_UUID, 3)->addUuid(self::DDS_UUID)->toSoapXml($this->createConfig());

        $this->assertStringContainsString(
            '<sd:uuidAndVersionNumberList><eudrCommon:uuid>' . self::SD_UUID . '</eudrCommon:uuid><eudrCommon:versionNumber>3</eudrCommon:versionNumber></sd:uuidAndVersionNumberList>'
            . '<sd:uuidAndVersionNumberList><eudrCommon:uuid>' . self::DDS_UUID . '</eudrCommon:uuid></sd:uuidAndVersionNumberList>',
            $xml,
        );
    }

    public function testGetSdValidation(): void
    {
        GetSdRequest::make()->withUuids(array_fill(0, 100, self::SD_UUID))->validate();
        $this->addToAssertionCount(1);

        try {
            GetSdRequest::make()->withUuids(array_fill(0, 101, self::SD_UUID))->validate();
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertSame('Maximum 100 UUIDs per request', $e->getMessage());
        }

        try {
            GetSdRequest::make()->validate();
            $this->fail('Expected exception');
        } catch (ValidationException $e) {
            $this->assertSame('At least one SD UUID is required', $e->getMessage());
        }

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SD version #0 must be a positive integer');
        GetSdRequest::make()->addUuid(self::SD_UUID, 0)->validate();
    }

    public function testGetSdByInternalReferenceAcceptsUpTo50Characters(): void
    {
        GetSdByInternalReferenceRequest::make()->withInternalReference(str_repeat('A', 50))->validate();
        $this->addToAssertionCount(1);

        $this->expectException(ValidationException::class);
        GetSdByInternalReferenceRequest::make()->withInternalReference(str_repeat('A', 51))->validate();
    }
}
