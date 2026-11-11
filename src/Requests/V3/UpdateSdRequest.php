<?php

declare(strict_types=1);

namespace Eudr\Requests\V3;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V3\Rules;
use Eudr\Support\Xml;

/**
 * updateSd — update an AVAILABLE Simplified Declaration (V3).
 *
 * The statement fully replaces the previous content. Grouped declarations cannot be set on update.
 */
final class UpdateSdRequest extends AbstractSdStatementRequest
{
    private ?string $sdIdentifier = null;

    private function __construct()
    {
        parent::__construct();
    }

    public static function make(): static
    {
        return new self();
    }

    /**
     * UUID of the Simplified Declaration to update (as returned by submitSd).
     */
    public function withSdIdentifier(string $sdIdentifier): static
    {
        $clone = clone $this;
        $clone->sdIdentifier = $sdIdentifier;
        return $clone;
    }

    public function getSoapAction(): string
    {
        return Xml::EUDR_SD_NS_V3 . '/updateSd';
    }

    public function validate(): void
    {
        if ($this->sdIdentifier === null || $this->sdIdentifier === '') {
            throw new ValidationException('SD identifier is required for update');
        }

        Rules::uuid($this->sdIdentifier, 'SD identifier');

        parent::validate();

        $this->assertNoGroupedDeclarations('update');
    }

    public function toSoapXml(Config $config): string
    {
        $builder = $this->createSdBuilder($config);

        $request = $builder->createElement('sd:UpdateSdRequest');
        $builder->getBody()->appendChild($request);

        self::appendText($builder, $request, 'sd:sdIdentifier', $this->sdIdentifier ?? '');
        $this->appendStatement($builder, $request, true);

        return $builder->toString();
    }
}
