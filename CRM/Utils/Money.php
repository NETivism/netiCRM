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
 * Money utilities for formatting and converting monetary values.
 */
class CRM_Utils_Money {

  /**
   * Cached map of currency ISO codes to their symbols.
   *
   * @var array<string, string>|null
   */
  public static $_currencySymbols = NULL;

  /**
   * Format a monetary string based on the amount, currency code, and format string.
   *
   * The format string supports the following placeholders:
   * - %a - the formatted amount
   * - %C - the currency ISO code (e.g., 'USD') if provided
   * - %c - the currency symbol (e.g., '$') if available
   *
   * When $format is 'chinese' and $currency is 'TWD', the amount is
   * converted to traditional Chinese numerals via toTaiwanDollar().
   *
   * @param float|string $amount     The monetary amount to display (e.g., 1234.56).
   * @param string|null  $currency   The three-letter ISO currency code (e.g., 'USD').
   *                                 Defaults to the site default currency.
   * @param string|null  $format     The desired currency format string. Defaults to
   *                                 the configured moneyformat. Use 'chinese' for
   *                                 traditional Chinese numeral output (TWD only).
   * @param bool         $onlyNumber If TRUE, return only the formatted numeric amount
   *                                 without currency symbol or code.
   *
   * @return string The formatted monetary string, or empty string if amount is null.
   *
   * @throws CRM_Core_Exception If $currency is not a valid currency code.
   */
  public static function format($amount, $currency = NULL, $format = NULL, $onlyNumber = FALSE) {

    if (CRM_Utils_System::isNull($amount)) {
      return '';
    }

    // toTaiwanDollar() is executed only when $format is 'chinese' and $currency is 'TWD'
    // otherwise unset $format value from 'chinese' to NULL to output the original amount
    if ($format == 'chinese') {
      if ($currency == 'TWD') {
        return self::toTaiwanDollar($amount);
      }
      else {
        $format = NULL;
      }
    }

    $config = CRM_Core_Config::singleton();

    if (!$format) {
      $format = $config->moneyformat;
    }

    if ($onlyNumber) {
      $amount = self::formatNumericByFormat($amount, $config->moneyvalueformat);
      return $amount;
    }

    if (!self::$_currencySymbols) {

      $currencySymbolName = CRM_Core_PseudoConstant::currencySymbols('name');
      $currencySymbol = CRM_Core_PseudoConstant::currencySymbols();

      self::$_currencySymbols = array_combine($currencySymbolName, $currencySymbol);
    }

    if (!$currency) {
      $currency = $config->defaultCurrency;
    }

    // ensure $currency is a valid currency code
    // for backwards-compatibility, also accept one space instead of a currency
    if ($currency != ' ' && !CRM_Utils_Array::arrayKeyExists($currency, self::$_currencySymbols)) {
      throw new CRM_Core_Exception("Invalid currency \"{$currency}\"");
    }

    // money_format() exists only in certain PHP install (CRM-650)
    $amount = self::formatNumericByFormat($amount, $config->moneyvalueformat);

    $rep = [
      ',' => $config->monetaryThousandSeparator,
      '.' => $config->monetaryDecimalPoint,
    ];

    // If it contains tags, means that HTML was passed and the
    // amount is already converted properly,
    // so don't mess with it again.
    if (strip_tags($amount) === $amount) {
      $money = strtr($amount, $rep);
    }
    else {
      $money = $amount;
    }

    $replacements = [
      '%a' => $money,
      '%C' => $currency,
      '%c' => CRM_Utils_Array::value($currency, self::$_currencySymbols, $currency),
    ];

    return strtr($format, $replacements);
  }

  /**
   * Format the numeric part of a currency value according to the given format.
   *
   * This is an internal helper that applies number_format() based on the
   * value format string. Wrapper functions handle separator conversions
   * and rounding.
   *
   * @param float|string $amount      The numeric amount to format. Non-numeric
   *                                  values are returned as-is.
   * @param string       $valueFormat The format specifier (e.g., '%!i' for 2
   *                                  decimal places, '%!.0n' for no decimals).
   *
   * @return string The formatted numeric string.
   */
  protected static function formatNumericByFormat($amount, $valueFormat) {
    if (!is_numeric($amount)) {
      return $amount;
    }
    $formatted = $amount;
    switch ($valueFormat) {
      case '%!i':
        $formatted = number_format((float)$amount, 2);
        break;
      case '%!.0n':
      default:
        $formatted = number_format((float)$amount, 0);
        break;
    }
    return $formatted;
  }

  /**
   * Convert a numeric amount to traditional Chinese numeral representation (Taiwan Dollar).
   *
   * Produces an HTML string with Chinese numerals and unit markers
   * (e.g., "壹仟貳佰參拾肆元整") suitable for formal financial documents.
   * The amount is floored to an integer before conversion.
   * Supports amounts up to 8 digits (i.e., less than 100,000,000).
   *
   * @param float|int|string $amount The monetary amount to convert.
   *
   * @return string HTML string with Chinese numerals and unit span elements,
   *                or empty string if amount exceeds 8 digits.
   */
  public static function toTaiwanDollar($amount) {
    $amount = floor($amount);
    $amount = (string) $amount;

    $num = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $cht = ['零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖'];
    $amt = str_replace($num, $cht, $amount);
    $amt = preg_split('/(?<!^)(?!$)/u', $amt);
    $amt = array_reverse($amt);
    $unit = ['元整', '拾', '佰', '仟', '萬', '拾萬', '佰萬', '仟萬'];
    $output = '';

    if (count($amt) > 0 && count($amt) < 9) {
      foreach ($amt as $k => $v) {
        $class = $k == 0 ? "unit unit-start" : 'unit';

        if ($k == 0) {
          if (count($amt) == 1) {
            $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
          }
          else {
            if ($v != '零') {
              $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
            else {
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
          }
        }

        if ($k > 0 && $k < 4) {
          if ($v != '零') {
            $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
          }
          else {
            if ($k == 0) {
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
            else {
              if (isset($amt[$k - 1])) {
                if ($amt[$k - 1] != '零') {
                  $output = $v . $output;
                }
              }
            }
          }
        }

        if ($k == 4) {
          if ($v != '零') {
            $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
          }
          else {
            if ($amt[$k - 1] != '零') {
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $v . $output;
            }
            else {
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
          }
        }

        if ($k > 4) {
          if ($v != '零') {
            $output = $v . '<span class="' . $class . '">' . str_replace("萬", "", $unit[$k]) . '</span>' . $output;
          }
          else {
            if (isset($amt[$k - 1])) {
              if ($amt[$k - 1] != '零') {
                $output = $v . $output;
              }
            }
          }
        }
      }
    }

    return $output;
  }
}
