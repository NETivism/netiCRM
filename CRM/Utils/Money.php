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

    if ($currency == 'TWD' && $format == 'chinese') {
      return self::toChinese($amount);
    }

    $config = CRM_Core_Config::singleton();

    if (!$format) {
      $format = $config->moneyformat;
    }

    if ($onlyNumber) {
      // money_format() exists only in certain PHP install (CRM-650)
      if (is_numeric($amount) and function_exists('money_format')) {
        $amount = money_format($config->moneyvalueformat, $amount);
      }
      return $amount;
    }

    if (!self::$_currencySymbols) {
      require_once "CRM/Core/PseudoConstant.php";
      $currencySymbolName = CRM_Core_PseudoConstant::currencySymbols('name');
      $currencySymbol = CRM_Core_PseudoConstant::currencySymbols();

      self::$_currencySymbols = array_combine($currencySymbolName, $currencySymbol);
    }

    if (!$currency) {
      $currency = $config->defaultCurrency;
    }

    if (!$format) {
      $format = $config->moneyformat;
    }

    setlocale(LC_MONETARY, 'en_US.utf8', 'en_US', 'en_US.utf8', 'en_US', 'C');
    // money_format() exists only in certain PHP install (CRM-650)
    if (is_numeric($amount) && function_exists('money_format')) {
      $amount = money_format($config->moneyvalueformat, $amount);
    }

    $rep = array(',' => $config->monetaryThousandSeparator,
      '.' => $config->monetaryDecimalPoint,
    );

    // If it contains tags, means that HTML was passed and the
    // amount is already converted properly,
    // so don't mess with it again.
    if (strip_tags($amount) === $amount) {
      $money = strtr($amount, $rep);
    }
    else {
      $money = $amount;
    }


    $replacements = array(
      '%a' => $money,
      '%C' => $currency,
      '%c' => CRM_Utils_Array::value($currency, self::$_currencySymbols, $currency),
    );
    return strtr($format, $replacements);
  }

  static function toChinese($amount) {
    $amount = floor($amount);
    $amount = (string) $amount;

    $num = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $cht = array('零', '壹', '貳', '參', '肆', '伍', '陸', '柒', '捌', '玖');
    $amt = str_replace($num, $cht, $amount);
    $amt = preg_split('/(?<!^)(?!$)/u', $amt);
    $amt = array_reverse($amt);
    $unit = array('元整', '拾', '佰', '仟', '萬', '拾萬', '佰萬', '仟萬');
    $output = '';

    // 個位數～八位數，九位數以下（億）
    if (count($amt) > 0 && count($amt) < 9) {
      foreach ($amt as $k => $v) {
        $class = $k == 0 ? "unit unit-start" : 'unit';

        // 當前位數為個位數
        if ($k == 0) {
          // 如果只有個位數
          if (count($amt) == 1) {
            // 印出值和單位
            $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
          }
          // 如果有兩位數以上
          else {
            // 如果值不等於零
            if ($v != '零') {
              // 印出值和單位
              $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
            // 如果值等於零
            else {
              // 印出單位
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
          }
        }

        // 當前位數為二到四位數
        if ($k > 0 && $k < 4) {
          // 如果值不等於零，印出值和單位
          if ($v != '零') {
            $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
          }
          // 如果值等於零
          else {
            // 如果位於個位數
            if ($k == 0) {
              // 只印單位
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
            // 如果當前位數不是個位數
            else {
              // 如果此位數有前一位數（代表當前位數為二位數以上）
              if (isset($amt[$k-1])) {
                // 如果前一位數不為零
                if ($amt[$k-1] != '零') {
                  // 只印出值不印單位，例如『101』為『壹佰「零」壹元整』
                  $output = $v . $output;
                }
                // 如果前一位數也為零，則不將當前位數印出
              }
            }
          }
        }

        // 當前位數為五位數（萬）
        if ($k == 4) {
          if ($v != '零') {
            $output = $v . '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
          }
          else {
            // 如果前一位數不為零
            if ($amt[$k-1] != '零') {
              // 印出值和單位，不過值是接在單位後面，例如『101,000』為『壹拾萬零壹仟元整』
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $v . $output;
            }
            // 如果前一位數也為零，則只印出單位「萬」
            else {
              $output = '<span class="' . $class . '">' . $unit[$k] . '</span>' . $output;
            }
          }
        }

        // 當前位數為六到八位數
        if ($k > 4) {
          // 如果值不等於零，印出值和去掉「萬」字的單位
          if ($v != '零') {
            $output = $v . '<span class="' . $class . '">' . str_replace("萬", "", $unit[$k]) . '</span>' . $output;
          }
          // 如果值等於零
          else {
            // 如果此位數有前一位數（代表當前位數為二位數以上）
            if (isset($amt[$k-1])) {
              // 如果前一位數不為零
              if ($amt[$k-1] != '零') {
                // 只印出值不印單位，例如『101』為『壹佰「零」壹元整』
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

