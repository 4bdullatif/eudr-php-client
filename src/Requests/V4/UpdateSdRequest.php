<?php

declare(strict_types=1);

namespace Eudr\Requests\V4;

use Eudr\Config\Config;
use Eudr\Exceptions\ValidationException;
use Eudr\Support\V4\Rules;
use Eudr\Support\Xml;

/**
 * updateSd — update an AVAILABLE Simplified Declaration (V4).
 *
 * The statement fully replaces the previous content.
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
        return Xml::EUDR_SD_NS_V4 . '/updateSd';
    }

    public function validate(): void
    {
        if ($this->sdIdentifier === null || $this->sdIdentifier === '') {
            throw new ValidationException('SD identifier is required for update');
        }

        Rules::uuid($this->sdIdentifier, 'SD identifier');

        // Unlike V3 (and V4 amendDds), the V4 SD schema no longer documents a ban on grouping
        // during update, so grouped declarations are not blocked client-side here.
        parent::validate();
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
