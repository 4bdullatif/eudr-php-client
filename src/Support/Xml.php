<?php

declare(strict_types=1);

namespace Eudr\Support;

final class Xml
{
    public const SOAP_NS = 'http://schemas.xmlsoap.org/soap/envelope/';
    public const XMLNS_NS = 'http://www.w3.org/2000/xmlns/';

    public const EUDR_SUBMISSION_NS_V1 = 'http://ec.europa.eu/tracesnt/certificate/eudr/submission/v1';
    public const EUDR_MODEL_NS_V1 = 'http://ec.europa.eu/tracesnt/certificate/eudr/model/v1';
    public const EUDR_RETRIEVAL_NS_V1 = 'http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v1';

    public const EUDR_SUBMISSION_NS_V2 = 'http://ec.europa.eu/tracesnt/certificate/eudr/submission/v2';
    public const EUDR_MODEL_NS_V2 = 'http://ec.europa.eu/tracesnt/certificate/eudr/model/v2';
    public const EUDR_RETRIEVAL_NS_V2 = 'http://ec.europa.eu/tracesnt/certificate/eudr/retrieval/v2';

    public const TRACESNT_BASE_NS = 'http://ec.europa.eu/sanco/tracesnt/base/v4';
    public const TRACESNT_ERROR_NS = 'http://ec.europa.eu/sanco/tracesnt/error/v01';
    public const EUDR_ECHO_NS = 'http://ec.europa.eu/tracesnt/eudr/echo';

    public const WSSE_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    public const WSU_NS = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    public const WSSE_PASSWORD_DIGEST = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordDigest';
    public const WSSE_BASE64_ENCODING = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    private function __construct()
    {
    }
}
