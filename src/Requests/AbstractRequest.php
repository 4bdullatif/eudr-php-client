<?php

declare(strict_types=1);

namespace Eudr\Requests;

use Eudr\Config\Config;
use Eudr\Support\Xml;
use Eudr\Support\XmlBuilder;

abstract class AbstractRequest implements Request
{
    private const SECURITY_EXPIRY_SECONDS = 60;

    protected function createBuilder(Config $config): XmlBuilder
    {
        $builder = new XmlBuilder();

        $builder->addNamespace('v4', Xml::TRACESNT_BASE_NS);

        $this->addSecurityHeader($builder, $config);

        return $builder;
    }

    public function getSoapAction(): ?string
    {
        return null;
    }

    protected function addSecurityHeader(XmlBuilder $builder, Config $config): void
    {
        $credentials = $config->credentials;
        $doc = $builder->getDocument();
        $header = $builder->getHeader();

        $nonceBytes = random_bytes(16);
        $nonce = base64_encode($nonceBytes);
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $expires = gmdate('Y-m-d\TH:i:s\Z', time() + self::SECURITY_EXPIRY_SECONDS);
        $passwordDigest = base64_encode(sha1($nonceBytes . $created . $credentials->authKey, true));

        // wsse:Security
        $security = $doc->createElementNS(Xml::WSSE_NS, 'wsse:Security');
        $security->setAttributeNS(Xml::XMLNS_NS, 'xmlns:wsu', Xml::WSU_NS);
        $security->setAttributeNS(Xml::SOAP_NS, 'soapenv:mustUnderstand', '1');
        $header->appendChild($security);

        // wsu:Timestamp
        $timestampId = 'TS-' . XmlBuilder::generateUuid();
        $timestamp = $doc->createElementNS(Xml::WSU_NS, 'wsu:Timestamp');
        $timestamp->setAttribute('wsu:Id', $timestampId);
        $security->appendChild($timestamp);

        $createdEl = self::createElementWithText($doc, Xml::WSU_NS, 'wsu:Created', $created);
        $timestamp->appendChild($createdEl);

        $expiresEl = self::createElementWithText($doc, Xml::WSU_NS, 'wsu:Expires', $expires);
        $timestamp->appendChild($expiresEl);

        // wsse:UsernameToken
        $tokenId = 'UsernameToken-' . XmlBuilder::generateUuid();
        $usernameToken = $doc->createElementNS(Xml::WSSE_NS, 'wsse:UsernameToken');
        $usernameToken->setAttribute('wsu:Id', $tokenId);
        $security->appendChild($usernameToken);

        $usernameEl = self::createElementWithText($doc, Xml::WSSE_NS, 'wsse:Username', $credentials->username);
        $usernameToken->appendChild($usernameEl);

        $passwordEl = self::createElementWithText($doc, Xml::WSSE_NS, 'wsse:Password', $passwordDigest);
        $passwordEl->setAttribute('Type', Xml::WSSE_PASSWORD_DIGEST);
        $usernameToken->appendChild($passwordEl);

        $nonceEl = self::createElementWithText($doc, Xml::WSSE_NS, 'wsse:Nonce', $nonce);
        $nonceEl->setAttribute('EncodingType', Xml::WSSE_BASE64_ENCODING);
        $usernameToken->appendChild($nonceEl);

        $createdEl2 = self::createElementWithText($doc, Xml::WSU_NS, 'wsu:Created', $created);
        $usernameToken->appendChild($createdEl2);

        // WebServiceClientId (in TracesNT base namespace)
        $clientIdEl = self::createElementWithText($doc, Xml::TRACESNT_BASE_NS, 'v4:WebServiceClientId', $credentials->clientId);
        $header->appendChild($clientIdEl);
    }

    private static function createElementWithText(\DOMDocument $doc, string $ns, string $qualifiedName, string $value): \DOMElement
    {
        $el = $doc->createElementNS($ns, $qualifiedName);
        $el->appendChild($doc->createTextNode($value));

        return $el;
    }
}
