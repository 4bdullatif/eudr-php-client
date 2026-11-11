<?php

declare(strict_types=1);

namespace Eudr\Requests\V4\Concerns;

use Eudr\Data\V4\GoodsMeasure;
use Eudr\Data\V4\RepresentedOperator;
use Eudr\Support\V4\Rules;
use Eudr\Support\XmlBuilder;

/**
 * XML builders for the V4 common/v4 types (prefix "eudrCommon").
 *
 * @internal
 */
trait BuildsV4Xml
{
    protected static function appendText(XmlBuilder $builder, \DOMElement $parent, string $name, string $value): void
    {
        $parent->appendChild($builder->createElement($name, $value));
    }

    /**
     * EconomicOperatorIdentificationType: children live in the common namespace.
     */
    protected static function appendRepresentedOperator(
        XmlBuilder $builder,
        \DOMElement $parent,
        string $elementName,
        RepresentedOperator $operator,
    ): void {
        $opEl = $builder->createElement($elementName);
        $parent->appendChild($opEl);

        foreach ($operator->referenceNumbers as $referenceNumber) {
            $refEl = $builder->createElement('eudrCommon:operatorReferenceNumber');
            $opEl->appendChild($refEl);

            self::appendText($builder, $refEl, 'eudrCommon:identifierType', $referenceNumber->type->value);
            self::appendText($builder, $refEl, 'eudrCommon:identifierValue', $referenceNumber->value);
        }

        if ($operator->address !== null) {
            $address = $operator->address;
            $addressEl = $builder->createElement('eudrCommon:operatorAddress');
            $opEl->appendChild($addressEl);

            self::appendText($builder, $addressEl, 'eudrCommon:country', $address->country);
            self::appendText($builder, $addressEl, 'eudrCommon:street', $address->street);
            self::appendText($builder, $addressEl, 'eudrCommon:postalCode', $address->postalCode);
            self::appendText($builder, $addressEl, 'eudrCommon:city', $address->city);

            if ($address->fullAddress !== null) {
                self::appendText($builder, $addressEl, 'eudrCommon:fullAddress', $address->fullAddress);
            }
        }

        if ($operator->summaryAddress !== null) {
            $summaryEl = $builder->createElement('eudrCommon:summaryAddress');
            $opEl->appendChild($summaryEl);

            if ($operator->summaryAddress->name !== null) {
                self::appendText($builder, $summaryEl, 'eudrCommon:name', $operator->summaryAddress->name);
            }

            if ($operator->summaryAddress->country !== null) {
                self::appendText($builder, $summaryEl, 'eudrCommon:country', $operator->summaryAddress->country);
            }
        }

        if ($operator->email !== null) {
            self::appendText($builder, $opEl, 'eudrCommon:operatorEmail', $operator->email);
        }

        if ($operator->phone !== null) {
            self::appendText($builder, $opEl, 'eudrCommon:operatorPhone', $operator->phone);
        }

        self::appendText($builder, $opEl, 'eudrCommon:operatorName', $operator->name);
    }

    /**
     * CommercialDescriptionType: descriptionOfGoods + goodsMeasure (both in the common namespace).
     */
    protected static function appendDescriptors(
        XmlBuilder $builder,
        \DOMElement $parent,
        string $elementName,
        string $description,
        GoodsMeasure $measure,
    ): void {
        $descriptorsEl = $builder->createElement($elementName);
        $parent->appendChild($descriptorsEl);

        self::appendText($builder, $descriptorsEl, 'eudrCommon:descriptionOfGoods', $description);

        $measureEl = $builder->createElement('eudrCommon:goodsMeasure');
        $descriptorsEl->appendChild($measureEl);

        if ($measure->percentageEstimationOrDeviation !== null) {
            self::appendText(
                $builder,
                $measureEl,
                'eudrCommon:percentageEstimationOrDeviation',
                Rules::decimal($measure->percentageEstimationOrDeviation, 3),
            );
        }

        if ($measure->netWeight !== null) {
            self::appendText($builder, $measureEl, 'eudrCommon:netWeight', Rules::decimal($measure->netWeight, 6));
        }

        if ($measure->supplementaryUnit !== null) {
            self::appendText($builder, $measureEl, 'eudrCommon:supplementaryUnit', Rules::decimal($measure->supplementaryUnit, 6));
        }

        if ($measure->supplementaryUnitQualifier !== null) {
            self::appendText($builder, $measureEl, 'eudrCommon:supplementaryUnitQualifier', $measure->supplementaryUnitQualifier);
        }
    }
}
