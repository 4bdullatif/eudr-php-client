<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses\V1;

use Eudr\Exceptions\XmlException;
use Eudr\Responses\V1\SubmitDdsResponse;
use Eudr\Tests\TestCase;

final class SubmitDdsResponseTest extends TestCase
{
    public function testFromXmlParsesFixture(): void
    {
        $xml = $this->fixture('submit_response_v1.xml');
        $response = SubmitDdsResponse::fromXml($xml);

        $this->assertSame('EUDR.2024.DE.9876543210', $response->ddsIdentifier);
        $this->assertSame('2024-05-10T08:15:00Z', $response->timestamp);
    }

    public function testIsSuccessReturnsTrueWhenIdentifierPresent(): void
    {
        $response = new SubmitDdsResponse('EUDR.2024.DE.123', '2024-05-10T08:15:00Z');
        $this->assertTrue($response->isSuccess());
    }

    public function testIsSuccessReturnsFalseWhenIdentifierEmpty(): void
    {
        $response = new SubmitDdsResponse('');
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlThrowsOnInvalidXml(): void
    {
        $this->expectException(XmlException::class);
        SubmitDdsResponse::fromXml('not xml at all');
    }

    public function testFromXmlThrowsOnMissingSoapBody(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Could not find SOAP Body');
        SubmitDdsResponse::fromXml('<?xml version="1.0"?><root/>');
    }

    public function testFromXmlHandlesEmptyResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:SubmitStatementResponse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1">
                </ns2:SubmitStatementResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = SubmitDdsResponse::fromXml($xml);
        $this->assertSame('', $response->ddsIdentifier);
        $this->assertNull($response->timestamp);
    }

    public function testFromXmlThrowsWhenSubmitStatementResponseMissing(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:SomethingElse xmlns:ns2="http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1">
                </ns2:SomethingElse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Could not find SubmitStatementResponse in SOAP body');
        SubmitDdsResponse::fromXml($xml);
    }
}
