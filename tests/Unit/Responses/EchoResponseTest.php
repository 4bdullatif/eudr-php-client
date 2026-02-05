<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Responses;

use Eudr\Exceptions\XmlException;
use Eudr\Responses\EchoResponse;
use Eudr\Tests\TestCase;

final class EchoResponseTest extends TestCase
{
    public function testIsSuccessReturnsTrueWhenResultNonEmpty(): void
    {
        $response = new EchoResponse(result: 'hello');
        $this->assertTrue($response->isSuccess());
    }

    public function testIsSuccessReturnsFalseWhenResultEmpty(): void
    {
        $response = new EchoResponse(result: '');
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlParsesEudrEchoResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:EudrEchoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/eudr/echo">hello world</ns2:EudrEchoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = EchoResponse::fromXml($xml);
        $this->assertSame('hello world', $response->result);
        $this->assertTrue($response->isSuccess());
    }

    public function testFromXmlParsesEmptyEchoResponse(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:EudrEchoResponse xmlns:ns2="http://ec.europa.eu/tracesnt/eudr/echo"></ns2:EudrEchoResponse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $response = EchoResponse::fromXml($xml);
        $this->assertSame('', $response->result);
        $this->assertFalse($response->isSuccess());
    }

    public function testFromXmlThrowsWhenResponseNotFound(): void
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
            <soapenv:Body>
                <ns2:SomethingElse xmlns:ns2="http://ec.europa.eu/tracesnt/eudr/echo">data</ns2:SomethingElse>
            </soapenv:Body>
        </soapenv:Envelope>';

        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Could not find echo response');
        EchoResponse::fromXml($xml);
    }

    public function testFromXmlThrowsOnInvalidXml(): void
    {
        $this->expectException(XmlException::class);
        EchoResponse::fromXml('not xml at all');
    }
}
