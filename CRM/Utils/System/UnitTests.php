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
 * Helper authentication class for unit tests
 */
class CRM_Utils_System_UnitTests {
  function setTitle($title, $pageTitle = NULL) {
    return;
  }

  static function authenticate($name, $password) {
    $retVal = [1, 1, 12345];
    return $retVal;
  }

  static function appendBreadCrumb($breadCrumbs) {
    return;
  }

  static function resetBreadCrumb() {
    return;
  }

  static function addHTMLHead($head) {
    return;
  }

  static function mapConfigToSSL() {
    global $base_url;
    $base_url = str_replace('http://', 'https://', $base_url);
  }

  static function postURL($action) {
    return;
  }

  function url($path = NULL, $query = NULL, $absolute = TRUE, $fragment = NULL, $htmlize = TRUE) {
    $config = CRM_Core_Config::singleton();
    static $script = 'index.php';

    if (isset($fragment)) {
      $fragment = '#' . $fragment;
    }

    if (!isset($config->useFrameworkRelativeBase)) {
      $base = parse_url($config->userFrameworkBaseURL);
      $config->useFrameworkRelativeBase = $base['path'];
    }
    $base = $absolute ? $config->userFrameworkBaseURL : $config->useFrameworkRelativeBase;

    $separator = $htmlize ? '&amp;' : '&';

    if (!$config->cleanURL) {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $script . '?q=' . $path . $separator . $query . $fragment;
        }
        else {
          return $base . $script . '?q=' . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
    else {
      if (isset($path)) {
        if (isset($query)) {
          return $base . $path . '?' . $query . $fragment;
        }
        else {
          return $base . $path . $fragment;
        }
      }
      else {
        if (isset($query)) {
          return $base . $script . '?' . $query . $fragment;
        }
        else {
          return $base . $fragment;
        }
      }
    }
  }

  static function getUserID($user) {
    //FIXME: look here a bit closer when testing UFMatch


    // this puts the appropriate values in the session, so
    // no need to return anything
    CRM_Core_BAO_UFMatch::synchronize($user, TRUE, 'Standalone', 'Individual');
  }

  static function getAllowedToLogin($user) {
    return TRUE;
  }

  static function setMessage($message) {
    return;
  }

  public static function isUserLoggedIn() {
    return;
  }

  static function permissionDenied() {
     return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
  }

  static function logout() {
    session_destroy();
    header("Location:index.php");
  }

  static function getUFLocale() {
    return NULL;
  }

  static function languageNegotiationURL($url, $addLanguagePart = TRUE, $removeLanguagePart = FALSE) {
    return NULL;
  }

  static function cmsRootPath() {
    if (defined('DRUPAL_ROOT')) {
      return DRUPAL_ROOT;
    }
    $cmsRoot = $valid = NULL;
    if (!empty($_SERVER['PWD'])) {
      $scriptPath = $_SERVER['PWD'];
    }
    else {
      $scriptPath = $_SERVER['SCRIPT_FILENAME'];
    }
    $pathVars = explode('/', str_replace('\\', '/', $scriptPath));

    //might be windows installation.
    $firstVar = array_shift($pathVars);
    if ($firstVar) {
      $cmsRoot = $firstVar;
    }

    //start w/ csm dir search.
    foreach ($pathVars as $var) {
      $cmsRoot .= "/$var";
      $cmsIncludePath = "$cmsRoot/includes";
      //stop as we found bootstrap.
      if (file_exists("$cmsIncludePath/bootstrap.inc")) {
        $valid = TRUE;
        break;
      }
    }

    if ($valid) {
      define('DRUPAL_ROOT', $cmsRoot);
      return $cmsRoot;
    }
  }

  /**
   * Get logged user id, unit test always anonymous user
   *
   * @return int 0
   */
  public static function getLoggedInUfID() {
    return 0;
  }

  function notFound(){
    return;
  }

  function cmsDir($type) {
    $config = CRM_Core_Config::singleton();
    $version = $config->userSystem->version;
    if (empty($version)) {
      $version = 7;
    }
    if (function_exists('file_directory_temp') && function_exists('variable_get')) {
      switch($type) {
        case 'temp':
          return file_directory_temp();
        case 'public':
          if ($version >= 6 && $version < 7){
            return file_directory_path();
          }
          if ($version >= 7 && $version < 8) {
            return variable_get('file_public_path', 'sites/default/files');
          }
          if ($version >= 8 ) {
            return \Drupal\Core\StreamWrapper\PublicStream::basePath();
          }
        case 'private':
          if ($version >= 6 && $version < 7){
            return FALSE;
          }
          if ($version >= 7 && $version < 8) {
            return variable_get('file_private_path', '');
          }
          if ($version >= 8 ) {
            return \Drupal\Core\StreamWrapper\PrivateStream::basePath();
          }
      }
    }
    return FALSE;
  }
}

