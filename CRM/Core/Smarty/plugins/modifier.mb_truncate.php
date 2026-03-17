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
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Truncate a string to a certain length, multibyte version.
 *
 * @param string $string input string
 * @param int $length maximum length
 * @param string $etc string to append if truncated
 * @param bool $break_words whether to split in the middle of a word
 *
 * @return string truncated string
 */
function smarty_modifier_mb_truncate(
  $string,
  $length = 80,
  $etc = '...',
  $break_words = FALSE
) {
  if (function_exists('mb_internal_encoding') and function_exists('mb_strlen') and function_exists('mb_substr')) {
    mb_internal_encoding('UTF-8');
    $strlen = 'mb_strlen';
    $substr = 'mb_substr';
  }
  else {
    $strlen = 'strlen';
    $substr = 'substr';
  }

  if ($length == 0) {

    return '';

  }

  if ($strlen($string) > $length) {
    $length -= $strlen($etc);
    if (!$break_words) {
      $string = preg_replace('/\s+?(\S+)?$/', '', $substr($string, 0, $length + 1));
    }

    return $substr($string, 0, $length) . $etc;
  }
  else {
    return $string;
  }
}

/* vim: set expandtab: */
