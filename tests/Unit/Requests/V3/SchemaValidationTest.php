<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V3;

use Eudr\Data\V3\DdsCommodity;
use Eudr\Data\V3\GoodsMeasure;
use Eudr\Enums\ActivityType;
use Eudr\Requests\V3\AbstractV3Request;
use Eudr\Requests\V3\AmendDdsRequest;
use Eudr\Requests\V3\GetDdsByInternalReferenceRequest;
use Eudr\Requests\V3\GetDdsRequest;
use Eudr\Requests\V3\GetSdByInternalReferenceRequest;
use Eudr\Requests\V3\GetSdRequest;
use Eudr\Requests\V3\UpdateSdRequest;
use Eudr\Requests\V3\WithdrawDdsRequest;
use Eudr\Requests\V3\WithdrawSdRequest;
use Eudr\Support\Xml;
use Eudr\Tests\Support\BuildsV3Requests;
use Eudr\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Validates the SOAP body payload of every V3 request against the official V3 XSDs.
 */
final class SchemaValidationTest extends TestCase
{
    use BuildsV3Requests;

    private const XSD_DIR = __DIR__ . '/../../../Fixtures/xsd/v3';

    /**
     * @return iterable<string, array{AbstractV3Request, string, string, string}>
     */
    public static function requestProvider(): iterable
    {
        $dds = Xml::EUDR_DDS_NS_V3;
        $sd = Xml::EUDR_SD_NS_V3;

        yield 'submitDds minimal' => [self::validDdsSubmit(), 'dds.xsd', $dds, 'SubmitDdsRequest'];
        yield 'submitDds full' => [self::fullDdsSubmit(), 'dds.xsd', $dds, 'SubmitDdsRequest'];
        yield 'submitDds domestic with supplementary unit below 1e-5' => [
            self::validDdsSubmit()
                ->withActivityType(ActivityType::DOMESTIC)
                ->withCommodities([new DdsCommodity(
                    description: 'Furniture',
                    hsHeading: '94',
                    goodsMeasure: new GoodsMeasure(supplementaryUnit: 0.000001, supplementaryUnitQualifier: 'NAR'),
                    producers: [self::ddsCommodity()->producers[0]],
                )]),
            'dds.xsd',
            $dds,
            'SubmitDdsRequest',
        ];
        yield 'amendDds' => [
            AmendDdsRequest::make()
                ->withUuid(self::DDS_UUID)
                ->withInternalReference('MY-REF')
                ->withActivityType(ActivityType::EXPORT)
                ->withRepresentedOperator(self::representedOperator())
                ->addCommodity(self::fullDdsCommodity()),
            'dds.xsd',
            $dds,
            'AmendDdsRequest',
        ];
        yield 'withdrawDds' => [WithdrawDdsRequest::make()->withUuid(self::DDS_UUID), 'dds.xsd', $dds, 'WithdrawDdsRequest'];
        yield 'getDds' => [
            GetDdsRequest::make()->withUuids([self::DDS_UUID, self::SD_UUID]),
            'dds.xsd',
            $dds,
            'GetDdsRequest',
        ];
        yield 'getDdsByInternalReference' => [
            GetDdsByInternalReferenceRequest::make()->withInternalReference('26BEDWNW9JD1TN'),
            'dds.xsd',
            $dds,
            'GetDdsByInternalReferenceRequest',
        ];

        yield 'submitSd minimal' => [self::validSdSubmit(), 'sd.xsd', $sd, 'SubmitSdRequest'];
        yield 'submitSd full' => [self::fullSdSubmit(), 'sd.xsd', $sd, 'SubmitSdRequest'];
        yield 'updateSd' => [
            UpdateSdRequest::make()
                ->withSdIdentifier(self::SD_UUID)
                ->withInternalReference('26BEDWNW9JD1TN')
                ->withActivityType(ActivityType::IMPORT)
                ->withCountryOfActivity('BE')
                ->addCommodity(self::fullSdCommodity()),
            'sd.xsd',
            $sd,
            'UpdateSdRequest',
        ];
        yield 'withdrawSd' => [WithdrawSdRequest::make()->withSdIdentifier(self::SD_UUID), 'sd.xsd', $sd, 'WithdrawSdRequest'];
        yield 'getSd' => [
            GetSdRequest::make()->addUuid(self::SD_UUID, 2)->addUuid(self::DDS_UUID),
            'sd.xsd',
            $sd,
            'GetSdRequest',
        ];
        yield 'getSdByInternalReference' => [
            GetSdByInternalReferenceRequest::make()->withInternalReference('REF-0001000087'),
            'sd.xsd',
            $sd,
            'GetSdByInternalReferenceRequest',
        ];
    }

    #[DataProvider('requestProvider')]
    public function testBodyPayloadIsValidAgainstOfficialXsd(
        AbstractV3Request $request,
        string $xsd,
        string $namespace,
        string $rootElement,
    ): void {
        $request->validate();

        $payload = $this->extractBodyPayload($request->toSoapXml($this->createConfig()));
        $root = $payload->documentElement;

        $this->assertInstanceOf(\DOMElement::class, $root);
        $this->assertSame($namespace, $root->namespaceURI);
        $this->assertSame($rootElement, $root->localName);
        $this->assertValidAgainst($payload, self::XSD_DIR . '/' . $xsd);
    }

    #[DataProvider('requestProvider')]
    public function testBodyIdentityHeaderIsValidAgainstOfficialXsd(AbstractV3Request $request): void
    {
        $xml = $request->withOperatorAccessIdentifier('ABC123')->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml));

        $bodyIdentities = $doc->getElementsByTagNameNS(Xml::TRACESNT_BODY_NS_V3, 'BodyIdentity');
        $this->assertSame(1, $bodyIdentities->length);

        $bodyIdentity = $bodyIdentities->item(0);
        $this->assertInstanceOf(\DOMElement::class, $bodyIdentity);
        $this->assertSame('Header', $bodyIdentity->parentNode?->localName);

        $identifier = $bodyIdentity->firstElementChild;
        $this->assertInstanceOf(\DOMElement::class, $identifier);
        $this->assertSame('OperatorAccessIdentifier', $identifier->localName);
        $this->assertNull($identifier->namespaceURI, 'OperatorAccessIdentifier must be unqualified');
        $this->assertSame('ABC123', $identifier->textContent);

        $standalone = new \DOMDocument();
        $standalone->appendChild($standalone->importNode($bodyIdentity, true));
        $this->assertValidAgainst($standalone, self::XSD_DIR . '/body.xsd');
    }

    #[DataProvider('requestProvider')]
    public function testNoBodyIdentityHeaderByDefault(AbstractV3Request $request): void
    {
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($request->toSoapXml($this->createConfig())));

        $this->assertSame(0, $doc->getElementsByTagNameNS(Xml::TRACESNT_BODY_NS_V3, 'BodyIdentity')->length);
        $this->assertSame(1, $doc->getElementsByTagNameNS(Xml::TRACESNT_BASE_NS, 'WebServiceClientId')->length);
        $this->assertSame(1, $doc->getElementsByTagNameNS(Xml::WSSE_NS, 'UsernameToken')->length);
    }

    public function testSchemaHarnessRejectsInvalidPayload(): void
    {
        // Negative control: an invalid UUID must be rejected by the schema.
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML(
            '<sd:WithdrawSdRequest xmlns:sd="' . Xml::EUDR_SD_NS_V3 . '"><sd:sdIdentifier>not-a-uuid</sd:sdIdentifier></sd:WithdrawSdRequest>',
        ));

        $previous = libxml_use_internal_errors(true);

        try {
            $this->assertFalse($doc->schemaValidate(self::XSD_DIR . '/sd.xsd'));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private function extractBodyPayload(string $soapXml): \DOMDocument
    {
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($soapXml));

        $body = $doc->getElementsByTagNameNS(Xml::SOAP_NS, 'Body')->item(0);
        $this->assertInstanceOf(\DOMElement::class, $body);

        $payload = $body->firstElementChild;
        $this->assertInstanceOf(\DOMElement::class, $payload);

        $out = new \DOMDocument();
        $out->appendChild($out->importNode($payload, true));

        // Re-parse so that namespace resolution matches what the server sees on the wire.
        $reparsed = new \DOMDocument();
        $this->assertTrue($reparsed->loadXML((string) $out->saveXML()));

        return $reparsed;
    }

    private function assertValidAgainst(\DOMDocument $doc, string $xsd): void
    {
        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $valid = $doc->schemaValidate($xsd);
            $errors = array_map(
                static fn (\LibXMLError $e): string => trim($e->message),
                libxml_get_errors(),
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $this->assertTrue($valid, "XSD validation failed:\n" . implode("\n", $errors) . "\n\n" . $doc->saveXML());
    }
}
