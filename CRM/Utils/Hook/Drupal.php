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
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id: $
 *
 */

require_once 'CRM/Utils/Hook.php';
class CRM_Utils_Hook_Drupal extends CRM_Utils_Hook {
  static function invoke($numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5,
    $fnSuffix
  ) {
    static $functions = array();
    $result = array();
    // copied from user_module_invoke
    if (function_exists('module_list')) {
      $procceed = FALSE;
      $functions[$fnSuffix] = array();
      $r = FALSE;
      foreach (module_list() as $module) {
        $fnName = "{$module}_{$fnSuffix}";
        if (isset($functions[$fnSuffix][$fnName])) {
          if (!empty($functions[$fnSuffix][$fnName])) {
            $r = self::runHook($fnName, $numParams, $arg1, $arg2, $arg3, $arg4, $arg5);
          }
        }
        elseif (function_exists($fnName)) {
          $functions[$fnSuffix][$fnName] = TRUE;
          $r = self::runHook($fnName, $numParams, $arg1, $arg2, $arg3, $arg4, $arg5);
        }
        else {
          $functions[$fnSuffix][$fnName] = FALSE;
        }
        if (is_array($r)) {
          $result = array_merge($result, $r);
        }
      }
    }
    return empty($result) ? TRUE : $result;
  }

  static function runHook($fnName, $numParams, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5) {
    if ($numParams == 1) {
      $fResult = $fnName($arg1);
    }
    elseif ($numParams == 2) {
      $fResult = $fnName($arg1, $arg2);
    }
    elseif ($numParams == 3) {
      $fResult = $fnName($arg1, $arg2, $arg3);
    }
    elseif ($numParams == 4) {
      $fResult = $fnName($arg1, $arg2, $arg3, $arg4);
    }
    elseif ($numParams == 5) {
      $fResult = $fnName($arg1, $arg2, $arg3, $arg4, $arg5);
    }
    return $fResult;
  }
}

