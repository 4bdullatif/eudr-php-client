<?php

declare(strict_types=1);

namespace Eudr\Tests\Unit\Support;

use Eudr\Exceptions\XmlException;
use Eudr\Support\SoapEnvelope;
use Eudr\Tests\TestCase;

final class SoapEnvelopeTest extends TestCase
{
    public function testParseReturnsEnvelopeWithBody(): void
    {
        $xml = $this->fixture('submit_response_v2.xml');
        $envelope = SoapEnvelope::parse($xml);

        $this->assertInstanceOf(\SimpleXMLElement::class, $envelope->getBody());
    }

    public function testGetFaultReturnsNullForSuccessResponse(): void
    {
        $xml = $this->fixture('submit_response_v2.xml');
        $envelope = SoapEnvelope::parse($xml);

        $this->assertNull($envelope->getFault());
    }

    public function testGetFaultReturnsErrorResponseForSoapFault(): void
    {
        $xml = $this->fixture('soap_fault.xml');
        $envelope = SoapEnvelope::parse($xml);

        $fault = $envelope->getFault();
        $this->assertNotNull($fault);
        $this->assertSame('soapenv:Server', $fault->faultCode);
        $this->assertSame('Internal Server Error', $fault->faultString);
    }

    public function testParseThrowsOnInvalidXml(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Failed to parse XML response');
        SoapEnvelope::parse('not xml at all');
    }

    public function testParseThrowsOnMissingSoapBody(): void
    {
        $this->expectException(XmlException::class);
        $this->expectExceptionMessage('Could not find SOAP Body');
        SoapEnvelope::parse('<?xml version="1.0"?><root/>');
    }

    public function testParseOnceReusesBody(): void
    {
        $xml = $this->fixture('retrieve_response_v2.xml');
        $envelope = SoapEnvelope::parse($xml);

        // Call getBody and getFault — both should use the same parsed object
        $body1 = $envelope->getBody();
        $fault = $envelope->getFault();
        $body2 = $envelope->getBody();

        $this->assertNull($fault);
        $this->assertSame($body1, $body2);
    }
}
