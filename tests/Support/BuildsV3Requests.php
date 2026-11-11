<?php

declare(strict_types=1);

namespace Eudr\Tests\Support;

use Eudr\Data\SpeciesInfo;
use Eudr\Data\V3\DdsCommodity;
use Eudr\Data\V3\DdsProducer;
use Eudr\Data\V3\GoodsMeasure;
use Eudr\Data\V3\OperatorAddress;
use Eudr\Data\V3\OperatorReferenceNumber;
use Eudr\Data\V3\RepresentedOperator;
use Eudr\Data\V3\SdCommodity;
use Eudr\Data\V3\SdPostalAddress;
use Eudr\Data\V3\SdProducer;
use Eudr\Data\V3\SdProducerLocation;
use Eudr\Enums\ActivityType;
use Eudr\Enums\V3\IdentifierType;
use Eudr\Enums\V3\OperatorRole;
use Eudr\Enums\V3\SdOperatorRole;
use Eudr\Requests\V3\SubmitDdsRequest;
use Eudr\Requests\V3\SubmitSdRequest;

trait BuildsV3Requests
{
    protected const DDS_UUID = '071874bd-8c62-4cac-8eb6-b2fbe003410c';
    protected const SD_UUID = 'e3b3206d-6625-47a0-b2ef-b8a7b9da1217';

    protected static function geoJson(): string
    {
        return base64_encode('{"type":"Point","coordinates":[-47.871234,-15.791234]}');
    }

    protected static function ddsCommodity(): DdsCommodity
    {
        return new DdsCommodity(
            description: 'Test wood product',
            hsHeading: '4410',
            goodsMeasure: new GoodsMeasure(netWeight: 200.0, supplementaryUnit: 20.0, supplementaryUnitQualifier: 'MTQ'),
            speciesInfo: [new SpeciesInfo('Bifora testiculata', 'Test Name')],
            producers: [new DdsProducer('FR', self::geoJson(), 'Producer Name', 1)],
            position: 1,
        );
    }

    protected static function fullDdsCommodity(): DdsCommodity
    {
        return new DdsCommodity(
            description: 'Tropical hardwood lumber',
            hsHeading: '4403991000',
            goodsMeasure: new GoodsMeasure(
                netWeight: 5000.123456,
                supplementaryUnit: 12.5,
                supplementaryUnitQualifier: 'MTQ',
                percentageEstimationOrDeviation: 2.5,
            ),
            speciesInfo: [
                new SpeciesInfo('Swietenia macrophylla', 'Mahogany'),
                new SpeciesInfo(scientificName: 'Tectona grandis'),
            ],
            producers: [
                new DdsProducer('BR', self::geoJson(), 'Brazilian Forest Co', 1),
                new DdsProducer('br', self::geoJson()),
            ],
            position: 1,
        );
    }

    protected static function representedOperator(): RepresentedOperator
    {
        return new RepresentedOperator(
            name: 'Example GmbH',
            referenceNumbers: [
                new OperatorReferenceNumber(IdentifierType::EORI, 'DE123456789012345'),
                new OperatorReferenceNumber(IdentifierType::VAT, 'DE123456789'),
            ],
            address: new OperatorAddress('DE', 'Hauptstrasse 42', '10115', 'Berlin', 'Hauptstrasse 42, 10115 Berlin'),
            email: 'contact@example.com',
            phone: '+49 30 1234567',
        );
    }

    protected static function validDdsSubmit(): SubmitDdsRequest
    {
        return SubmitDdsRequest::make()
            ->withOperatorRole(OperatorRole::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->withCountryOfActivity('BE')
            ->withBorderCrossCountry('BE')
            ->addCommodity(self::ddsCommodity());
    }

    protected static function fullDdsSubmit(): SubmitDdsRequest
    {
        return SubmitDdsRequest::make()
            ->withOperatorRole(OperatorRole::REPRESENTATIVE_OPERATOR)
            ->withInternalReference('MY-REF-2026-001')
            ->withActivityType(ActivityType::IMPORT)
            ->withRepresentedOperator(self::representedOperator())
            ->withCountryOfActivity('DE')
            ->withBorderCrossCountry('NL')
            ->withComment('Annual timber import')
            ->addCommodity(self::fullDdsCommodity())
            ->addCommodity(self::ddsCommodity())
            ->withGeoLocationConfidential()
            ->addGroupedDeclaration('26FRYUI34JTQKB')
            ->addGroupedDeclaration('26FRLSCV861ZVV');
    }

    protected static function sdCommodity(): SdCommodity
    {
        return new SdCommodity(
            description: 'Test cocoa product',
            hsHeading: '1801',
            goodsMeasure: new GoodsMeasure(netWeight: 200.0),
            producers: [
                new SdProducer('BE', SdProducerLocation::geoJson(self::geoJson()), 'Producer with geojson', 1),
            ],
            position: 1,
        );
    }

    protected static function fullSdCommodity(): SdCommodity
    {
        return new SdCommodity(
            description: 'Test cocoa product',
            hsHeading: '180100',
            goodsMeasure: new GoodsMeasure(
                netWeight: 200.0,
                supplementaryUnit: 20.0,
                supplementaryUnitQualifier: 'MTQ',
                percentageEstimationOrDeviation: 1.25,
            ),
            producers: [
                new SdProducer('BE', SdProducerLocation::geoJson(self::geoJson()), 'Producer with geojson', 1),
                new SdProducer(
                    'BE',
                    SdProducerLocation::postalAddresses(
                        new SdPostalAddress('Rue De Geneve 4', '1140', 'Brussels'),
                        new SdPostalAddress('Rue De Geneve 6', '1140', 'Brussels'),
                    ),
                    'Producer with address',
                    2,
                ),
                new SdProducer(
                    'BE',
                    SdProducerLocation::cadastralIdentifiers('BE-BRU-2024-PARCEL-001', 'BE-BRU-2024-PARCEL-002'),
                    'Producer with cadastral',
                    3,
                ),
            ],
            position: 1,
        );
    }

    protected static function validSdSubmit(): SubmitSdRequest
    {
        return SubmitSdRequest::make()
            ->withOperatorRole(SdOperatorRole::MICRO_OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity(self::sdCommodity());
    }

    protected static function fullSdSubmit(): SubmitSdRequest
    {
        return SubmitSdRequest::make()
            ->withOperatorRole(SdOperatorRole::REPRESENTATIVE_MSPO)
            ->withInternalReference('SD-REF-0001')
            ->withActivityType(ActivityType::DOMESTIC)
            ->withRepresentedOperator(self::representedOperator())
            ->withCountryOfActivity('BE')
            ->withBorderCrossCountry('XI')
            ->withComment('MSPO declaration')
            ->addCommodity(self::fullSdCommodity())
            ->withGeoLocationConfidential(false)
            ->addGroupedDeclaration('S26BECB39D2GRX');
    }
}
