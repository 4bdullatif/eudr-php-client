<?php

declare(strict_types=1);

namespace Eudr\Enums\V4;

/**
 * Economic operator identifier types accepted by the V4 API (IdentifierTypeType).
 */
enum IdentifierType: string
{
    /** Economic Operators Registration and Identification number. */
    case EORI = 'eori';
    /** VAT identification number. */
    case VAT = 'vat';
    /** Global location number. */
    case GLN = 'gln';
    /** Tax Identification Number. */
    case TIN = 'tin';
    /** Central Business Register. */
    case CBR = 'cbr';
    /** Company Identification Number. */
    case CIN = 'cin';
    /** D-U-N-S Number. */
    case DUNS = 'duns';
    /** National company number. */
    case COMPANY_NUMBER = 'comp_num';
    /** Registration number in official national company register. */
    case COMPANY_REGISTER = 'comp_reg';
    /** Other National Identifier. */
    case OTHER_NATIONAL_IDENTIFIER = 'oni';
}
