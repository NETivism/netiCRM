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
 * $Id: $
 *
 */

require_once 'CRM/Core/Config.php';
class CRM_Utils_VersionCheck {
  // timeout for when the connection or the server is slow
  CONST LATEST_VERSION_AT = '',
  // relative to $civicrm_root
  CHECK_TIMEOUT = 5, LOCALFILE_NAME = 'civicrm-version.txt',
  // relative to $config->uploadDir
  CACHEFILE_NAME = 'latest-version-cache.txt',
  // cachefile expiry time (in seconds) - a week
  CACHEFILE_EXPIRE = 604800;

  /**
   * We only need one instance of this object, so we use the
   * singleton pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = NULL;

  /**
   * The version of the current (local) installation
   *
   * @var string
   */
  var $localVersion = NULL;

  /**
   * The latest version of CiviCRM
   *
   * @var string
   */
  var $latestVersion = NULL;

  /**
   * Class constructor
   *
   * @access private
   */
  function __construct() {
  }

  /**
   * Static instance provider
   *
   * Method providing static instance of CRM_Utils_VersionCheck,
   * as in Singleton pattern
   *
   * @return CRM_Utils_VersionCheck
   */
  static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_VersionCheck();
    }
    return self::$_singleton;
  }

  /**
   * Get the latest version number if it's newer than the local one
   *
   * @return string|null  returns the newer version's number or null if the versions are equal
   */
  function newerVersion() {
    return NULL;
  }

  /**
   * A dummy function required for suppressing download errors
   */
  static function downloadError($errorNumber, $errorString) {
    return;
  }
}

