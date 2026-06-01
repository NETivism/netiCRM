<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * Add thousands separator to numeric strings using PHP number_format().
 *
 * EXAMPLE: {$number|crmNumberFormat:2:',':' '} for French notation - 1234.56 becomes 1 234,56
 *
 * @param float|string $number numeric value to be formatted
 * @param int|null $decimals number of decimal places
 * @param string|null $dec_point decimal point character
 * @param string|null $thousands_sep thousands separator character
 *
 * @return string formatted string or empty
 */
function smarty_modifier_crmNumberFormat($number, $decimals = NULL, $dec_point = NULL, $thousands_sep = NULL) {
  // If $decimals is negative, treat it as 0 to mimic PHP 8.2 behavior
  if ($decimals < 0) {
    $decimals = 0;
  }
  if (is_numeric($number)) {
    // Both dec_point AND thousands_sep are required if one is specified (this is how number_format works)
    if ($dec_point && $thousands_sep) {
      return number_format((float)$number, $decimals, $dec_point, $thousands_sep);
    }
    else {
      return number_format((float)$number, $decimals);
    }
  }
  else {
    return '';
  }
}
