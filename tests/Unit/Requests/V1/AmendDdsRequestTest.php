<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Requests\V1;

use Eudr\Data\Commodity;
use Eudr\Data\Producer;
use Eudr\Data\SpeciesInfo;
use Eudr\Enums\ActivityType;
use Eudr\Enums\OperatorType;
use Eudr\Exceptions\ValidationException;
use Eudr\Requests\V1\AmendDdsRequest;
use Eudr\Support\Xml;
use Eudr\Tests\TestCase;

final class AmendDdsRequestTest extends TestCase
{
    public function testMakeReturnsInstance(): void
    {
        $request = AmendDdsRequest::make();
        $this->assertInstanceOf(AmendDdsRequest::class, $request);
    }

    public function testGetEndpointReturnsV1SubmissionService(): void
    {
        $request = AmendDdsRequest::make();
        $this->assertSame('EUDRSubmissionServiceV1', $request->getEndpoint());
    }

    public function testGetSoapActionReturnsAmendAction(): void
    {
        $request = AmendDdsRequest::make();
        $this->assertSame(
            Xml::EUDR_SUBMISSION_NS_V1 . '/amendDds',
            $request->getSoapAction()
        );
    }

    public function testValidateRequiresDdsIdentifier(): void
    {
        $request = AmendDdsRequest::make()
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS identifier is required for amend');
        $request->validate();
    }

    public function testValidateRequiresEmptyDdsIdentifier(): void
    {
        $request = AmendDdsRequest::make()
            ->withDdsIdentifier('')
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('DDS identifier is required for amend');
        $request->validate();
    }

    public function testValidateDelegatesToParentRequiresOperatorType(): void
    {
        $request = AmendDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123')
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Operator type is required');
        $request->validate();
    }

    public function testValidateDelegatesToParentRequiresActivityType(): void
    {
        $request = AmendDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123')
            ->withOperatorType(OperatorType::OPERATOR)
            ->addCommodity($this->createValidCommodity());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Activity type is required');
        $request->validate();
    }

    public function testValidateDelegatesToParentRequiresCommodities(): void
    {
        $request = AmendDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123')
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

    public function testToSoapXmlProducesValidXmlWithAmendStatementRequest(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $doc = new \DOMDocument();
        $result = $doc->loadXML($xml);
        $this->assertTrue($result, 'Generated XML should be valid');

        $this->assertStringContainsString('v1:AmendStatementRequest', $xml);
        $this->assertStringContainsString('v1:ddsIdentifier', $xml);
        $this->assertStringContainsString('EUDR.2024.DE.123', $xml);
    }

    public function testToSoapXmlContainsCorrectNamespaces(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString(Xml::EUDR_SUBMISSION_NS_V1, $xml);
        $this->assertStringContainsString(Xml::EUDR_MODEL_NS_V1, $xml);
    }

    public function testToSoapXmlContainsOperatorTypeAndStatement(): void
    {
        $request = $this->createValidRequest();
        $xml = $request->toSoapXml($this->createConfig());

        $this->assertStringContainsString('v1:operatorType', $xml);
        $this->assertStringContainsString('OPERATOR', $xml);
        $this->assertStringContainsString('v1:statement', $xml);
    }

    public function testWithDdsIdentifierIsImmutable(): void
    {
        $request1 = AmendDdsRequest::make();
        $request2 = $request1->withDdsIdentifier('EUDR.2024.DE.123');

        $this->assertNotSame($request1, $request2);
    }

    public function testWithOperatorTypeIsImmutable(): void
    {
        $request1 = AmendDdsRequest::make();
        $request2 = $request1->withOperatorType(OperatorType::OPERATOR);

        $this->assertNotSame($request1, $request2);
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

    private function createValidRequest(): AmendDdsRequest
    {
        return AmendDdsRequest::make()
            ->withDdsIdentifier('EUDR.2024.DE.123')
            ->withOperatorType(OperatorType::OPERATOR)
            ->withActivityType(ActivityType::IMPORT)
            ->addCommodity($this->createValidCommodity());
    }
}
