Official XSD schemas of the EUDR V3 operator services, downloaded from
`https://acceptance.eudr.webcloud.ec.europa.eu/tracesnt/ws/EUDRDueDiligenceStatementServiceV3?xsd=N`
and `.../EUDRSimplifiedDeclarationServiceV3?xsd=N` (identical to production at the time of download).

Only `schemaLocation` attributes were rewritten to point at the local files. Used by the test suite to
validate generated V3 request bodies.

| File        | Namespace                                                                    | Source   |
|-------------|------------------------------------------------------------------------------|----------|
| dds.xsd     | http://ec.europa.eu/tracesnt/certificate/eudr/due-diligence-statement/v3     | DDS xsd=4 |
| sd.xsd      | http://ec.europa.eu/tracesnt/certificate/eudr/simplified-declaration/v3      | SD xsd=4  |
| common.xsd  | http://ec.europa.eu/tracesnt/certificate/eudr/common/v3                      | xsd=3     |
| error.xsd   | http://ec.europa.eu/sanco/tracesnt/error/v01                                 | xsd=5     |
| udt.xsd     | urn:un:unece:uncefact:data:standard:UnqualifiedDataType:21                   | xsd=7     |
| body.xsd    | http://ec.europa.eu/tracesnt/body/v3                                         | xsd=2     |
