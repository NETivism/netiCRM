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
 * Provides advisory locking mechanism to prevent concurrent execution of critical operations
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Core_Lock {

  public $_timeout;
  // lets have a 1 second timeout for now
  public const TIMEOUT = 1;

  protected $_hasLock = FALSE;

  protected $_name;
  /**
   * Class constructor.
   *
   * @param string $name The name of the lock.
   * @param int|null $timeout The timeout in seconds.
   */
  public function __construct($name, $timeout = NULL) {
    $config = CRM_Core_Config::singleton();
    $dsnArray = DB::parseDSN($config->dsn);
    $database = $dsnArray['database'];
    $domainID = CRM_Core_Config::domainID();
    $this->_name = $database . '.' . $domainID . '.' . $name;
    $this->_timeout = $timeout ? $timeout : self::TIMEOUT;

    $this->acquire();
  }

  /**
   * Class destructor. Releases the lock if held.
   */
  public function __destruct() {
    $this->release();
  }

  /**
   * Acquire the lock.
   *
   * @return bool True if lock acquired.
   */
  public function acquire() {
    if (!$this->_hasLock) {
      $query = "SELECT GET_LOCK( %1, %2 )";
      $params = [1 => [$this->_name, 'String'],
        2 => [$this->_timeout, 'Integer'],
      ];
      $res = CRM_Core_DAO::singleValueQuery($query, $params);
      if ($res) {
        $this->_hasLock = TRUE;
      }
    }
    return $this->_hasLock;
  }

  /**
   * Release the lock.
   *
   * @return int|null Result of the release query.
   */
  public function release() {
    if ($this->_hasLock) {
      $this->_hasLock = FALSE;

      $query = "SELECT RELEASE_LOCK( %1 )";
      $params = [1 => [$this->_name, 'String']];
      return CRM_Core_DAO::singleValueQuery($query, $params);
    }
  }

  /**
   * Check if the lock is currently held by this instance.
   *
   * @return bool True if lock is held.
   */
  public function isAcquired() {
    return $this->_hasLock;
  }

  /**
   * Check if a named lock is free.
   *
   * @param string $name The name of the lock.
   *
   * @return bool|int|null Result of the check.
   */
  public static function isFree($name) {
    $config = CRM_Core_Config::singleton();
    $dsnArray = DB::parseDSN($config->dsn);
    $database = $dsnArray['database'];
    $domainID = CRM_Core_Config::domainID();
    $name = $database . '.' . $domainID . '.' . $name;
    $query = "SELECT IS_FREE_LOCK( %1 )";
    $params = [1 => [$name, 'String']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  /**
   * Check if a named lock is used.
   *
   * @param string $name The name of the lock.
   *
   * @return string|null Connection ID using the lock, or null if free.
   */
  public static function isUsed($name) {
    $config = CRM_Core_Config::singleton();
    $dsnArray = DB::parseDSN($config->dsn);
    $database = $dsnArray['database'];
    $domainID = CRM_Core_Config::domainID();
    $name = $database . '.' . $domainID . '.' . $name;
    $query = "SELECT IS_USED_LOCK( %1 )";
    $params = [1 => [$name, 'String']];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }
}
