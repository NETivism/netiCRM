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
 * Checks for available CiviCRM version updates from the upstream release feed
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id: $
 *
 */

class CRM_Utils_VersionCheck {
  // timeout for when the connection or the server is slow
  public const LATEST_VERSION_AT = '',
    // relative to $civicrm_root
    CHECK_TIMEOUT = 5, LOCALFILE_NAME = 'civicrm-version.txt',
    // relative to $config->uploadDir
    CACHEFILE_NAME = 'latest-version-cache.txt',
    // cachefile expiry time (in seconds) - a week
    CACHEFILE_EXPIRE = 604800;

  /**
   * Singleton instance of this class.
   *
   * @var CRM_Utils_VersionCheck|null
   */
  private static $_singleton = NULL;

  /**
   * The version of the current (local) installation.
   *
   * @var string|null
   */
  public $localVersion = NULL;

  /**
   * The latest version of CiviCRM.
   *
   * @var string|null
   */
  public $latestVersion = NULL;

  /**
   * Class constructor.
   */
  public function __construct() {
  }

  /**
   * Return the singleton instance of CRM_Utils_VersionCheck.
   *
   * @return CRM_Utils_VersionCheck
   */
  public static function &singleton() {
    if (!isset(self::$_singleton)) {
      self::$_singleton = new CRM_Utils_VersionCheck();
    }
    return self::$_singleton;
  }

  /**
   * Get the latest version number if it is newer than the local one.
   *
   * @return string|null The newer version string, or NULL if versions are equal or unknown.
   */
  public function newerVersion() {
    return NULL;
  }

  /**
   * Error handler callback used to suppress download errors.
   *
   * @param int    $errorNumber The error level.
   * @param string $errorString The error message.
   *
   * @return void
   */
  public static function downloadError($errorNumber, $errorString) {
    return;
  }
}
