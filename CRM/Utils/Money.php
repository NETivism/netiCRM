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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

/**
 * Money utilties
 */
class CRM_Utils_Money {
  static $_currencySymbols = NULL;

  /**
   * format a monetary string
   *
   * Format a monetary string basing on the amount provided,
   * ISO currency code provided and a format string consisting of:
   *
   * %a - the formatted amount
   * %C - the currency ISO code (e.g., 'USD') if provided
   * %c - the currency symbol (e.g., '$') if available
   *
   * @param float  $amount    the monetary amount to display (1234.56)
   * @param string $currency  the three-letter ISO currency code ('USD')
   * @param string $format    the desired currency format
   *
   * @return string  formatted monetary string
   *
   * @static
   */
  static function format($amount, $currency = NULL, $format = NULL, $onlyNumber = FALSE) {

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
   * Format numeric part of currency by the passed in format.
   *
   * This is envisaged as an internal function, with wrapper functions defining valueFormat
   * into easily understood functions / variables and handling separator conversions and
   * rounding.
   *
   * @param string $amount
   * @param string $valueFormat
   *
   * @return string
   */
  protected static function formatNumericByFormat($amount, $valueFormat) {
    if (!is_numeric($amount)) {
      return $amount;
    }
    $formatted = $amount;
    switch($valueFormat) {
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


  static function toTaiwanDollar($amount) {
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
              if (isset($amt[$k-1])) {
                if ($amt[$k-1] != '零') {
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
            if ($amt[$k-1] != '零') {
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
            if (isset($amt[$k-1])) {
              if ($amt[$k-1] != '零') {
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

