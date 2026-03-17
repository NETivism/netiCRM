<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * Native gettext implementation for CiviCRM internationalization
 *
 * @copyright CiviCRM LLC (c) 2004-2011
 *
 * Convenience class for PHP-Gettext compatibility.
 */
class CRM_Core_I18n_NativeGettext {
  /**
   * Translate a string using gettext.
   *
   * @param string $string The string to translate.
   *
   * @return string The translated string.
   */
  public function translate($string) {
    return gettext($string);
  }

  /**
   * Translate a string with context.
   *
   * Based on php-gettext, since native gettext does not support this as is.
   *
   * @param string $context The context.
   * @param string $text The string to translate.
   *
   * @return string The translated string.
   */
  public function pgettext($context, $text) {
    $key = $context . chr(4) . $text;
    $ret = $this->translate($key);

    if (strpos($ret, "\004") !== FALSE) {
      return $text;
    }
    else {
      return $ret;
    }
  }

  /**
   * Translate a plural string.
   *
   * @param string $text The singular form.
   * @param string $plural The plural form.
   * @param int $count The count.
   *
   * @return string The translated string.
   */
  public function ngettext($text, $plural, $count) {
    return ngettext($text, $plural, $count);
  }
}
