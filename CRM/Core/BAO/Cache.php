<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * BAO object for civicrm_cache table. This is a database cache and is persisted across sessions. Typically we use
 * this to store meta data (like profile fields, custom fields etc).
 *
 * The group_name column is used for grouping together all cache elements that logically belong to the same set.
 * Thus all session cache entries are grouped under 'CiviCRM Session'. This allows us to delete all entries of
 * a specific group if needed.
 *
 * The path column allows us to differentiate between items in that group. Thus for the session cache, the path is
 * the unique form name for each form (per user)
 */
class CRM_Core_BAO_Cache extends CRM_Core_DAO_Cache {
  /**
   * @var array ($cacheKey => $cacheValue)
   */
  static $_cache = NULL;

  /**
   * Retrieve an item from the DB cache
   *
   * @param string $group (required) The group name of the item
   * @param string $path  (required) The path under which this item is stored
   * @param int    $componentID The optional component ID (so componenets can share the same name space)
   * @param int $createdTime minimal create date time in int or mysql datetime format
   *
   * @return mixed The data if present in cache, else null
   * @static
   * @access public
   */
  static function &getItem($group, $path, $componentID = NULL, $createdTime = 0) {
    if (self::$_cache === NULL) {
      self::$_cache = [];
    }
    $argString = "CRM_CT_{$group}_{$path}_{$componentID}";
    if (!CRM_Utils_Array::arrayKeyExists($argString, self::$_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      self::$_cache[$argString] = $cache->get($argString);
      if (!self::$_cache[$argString]) {
        $dao = new CRM_Core_DAO_Cache();

        $dao->group_name = $group;
        $dao->path = $path;
        $dao->component_id = $componentID;
        if ($createdTime) {
          if (is_numeric($createdTime)) {
            $createdTime = CRM_Utils_Type::escape($createdTime, 'Positive');
            if ($createdTime) {
              $dao->whereAdd("UNIX_TIMESTAMP(created_date) >= $createdTime");
            }
          }
          else {
            $createdTime = CRM_Utils_Type::escape($createdTime, 'Date');
            if ($createdTime) {
              $dao->whereAdd("created_date >= '$createdTime'");
            }
          }
        }

        $data = NULL;
        if ($dao->find(TRUE)) {
          $data = unserialize($dao->data);
        }
        $dao->free();
        self::$_cache[$argString] = $data;
        $cache->set($argString, self::$_cache[$argString]);
      }
    }
    return self::$_cache[$argString];
  }

  /**
   * Retrieve all items in a group
   *
   * @param string $group (required) The group name of the item
   * @param int    $componentID The optional component ID (so componenets can share the same name space)
   *
   * @return object The data if present in cache, else null
   * @static
   * @access public
   */
  static function &getItems($group, $componentID = NULL) {
    if (self::$_cache === NULL) {
      self::$_cache = [];
    }

    $argString = "CRM_CT_CI_{$group}_{$componentID}";
    if (!CRM_Utils_Array::arrayKeyExists($argString, self::$_cache)) {
      $cache = CRM_Utils_Cache::singleton();
      self::$_cache[$argString] = $cache->get($argString);
      if (!self::$_cache[$argString]) {
        $dao = new CRM_Core_DAO_Cache();

        $dao->group_name   = $group;
        $dao->component_id = $componentID;
        $dao->find();

        $result = []; // array($path => $data)
        while ($dao->fetch()) {
          $result[$dao->path] = unserialize($dao->data);
        }
        $dao->free();

        self::$_cache[$argString] = $result;
        $cache->set($argString, self::$_cache[$argString]);
      }
    }

    return self::$_cache[$argString];
  }

  /**
   * Set Cache Item
   *
   * @param mixed $data referenced data to be save into cache, will be serialized
   * @param string $group cache group name
   * @param string $path unique cache id based on group name
   * @param int $componentID component of this cache belong to
   * @param int $expired unix timestamp indicate this cache to be expire after this date
   * @return void
   */
  static function setItem(&$data, $group, $path, $componentID = NULL, $expired = NULL) {
    if (self::$_cache === NULL) {
      self::$_cache = [];
    }

    $dao = new CRM_Core_DAO_Cache();

    $dao->group_name = $group;
    $dao->path = $path;
    $dao->component_id = $componentID;

    // get a lock so that multiple ajax requests on the same page
    // dont trample on each other
    // CRM-11234
    $lockName = "ch.{$group}_{$path}_{$componentID}";
    if (strlen($lockName) > 64) {
      $lockName = substr($lockName, 0, 64);
    }
    $lock = new CRM_Core_Lock($lockName);
    if (!$lock->isAcquired()) {
      CRM_Core_Error::fatal("Trying acquire lock failed: $lockName");
    }

    $dao->find(TRUE);
    $dao->data = serialize($data);
    $dao->created_date = date('YmdHis');
    if (!empty($expired) && is_numeric($expired)) {
      $dao->expired_date = date('YmdHis', $expired);
    }
    $dao->save();

    $lock->release();

    $dao->free();

    // cache coherency - refresh or remove dependent caches

    $argString = "CRM_CT_{$group}_{$path}_{$componentID}";
    $cache = CRM_Utils_Cache::singleton();
    $data = unserialize($dao->data);
    self::$_cache[$argString] = $data;
    $cache->set($argString, $data);

    $argString = "CRM_CT_CI_{$group}_{$componentID}";
    unset(self::$_cache[$argString]);
    $cache->delete($argString);
  }

  static function deleteItem($group, $path, $componentID = NULL) {
    $dao = new CRM_Core_DAO_Cache();
    $dao->group_name = $group;
    $dao->path = $path;
    $dao->component_id = $componentID;

    // get a lock so that multiple ajax requests on the same page
    // dont trample on each other
    // CRM-11234
    $lockName = "civicrm.cache.{$group}_{$path}._{$componentID}";
    $lock = new CRM_Core_Lock($lockName);
    if (!$lock->isAcquired()) {
      CRM_Core_Error::fatal("Trying acquire lock failed: $lockName");
    }

    $success = FALSE;
    if ($dao->find(TRUE)) {
      $dao->delete();
      $success = TRUE;
    }
    $lock->release();
    $dao->free();

    return $success;
  }

  static function deleteGroup($group = NULL) {
    $dao = new CRM_Core_DAO_Cache();

    if (!empty($group)) {
      $dao->group_name = $group;
    }
    $dao->delete();

    // also reset ACL Cache

    CRM_ACL_BAO_Cache::resetCache();
  }

  static function storeSessionToCache($names, $resetSession = TRUE) {
    // CRM_Core_Error::debug_var( 'names in store', $names );
    foreach ($names as $key => $sessionName) {
      if (is_array($sessionName)) {
        if (!empty($_SESSION[$sessionName[0]][$sessionName[1]])) {
          $expired = $_SESSION[$sessionName[0]][$sessionName[1]]['expired'] ?? CRM_REQUEST_TIME+86400;
          self::setItem($_SESSION[$sessionName[0]][$sessionName[1]], 'CiviCRM Session', "{$sessionName[0]}_{$sessionName[1]}", NULL, $expired);
          // CRM_Core_Error::debug_var( "session value for: {$sessionName[0]}_{$sessionName[1]}",
          // $_SESSION[$sessionName[0]][$sessionName[1]] );
          if ($resetSession) {
            $_SESSION[$sessionName[0]][$sessionName[1]] = NULL;
            unset($_SESSION[$sessionName[0]][$sessionName[1]]);
          }
        }
      }
      else {
        if (!empty($_SESSION[$sessionName])) {
          $expired = $_SESSION[$sessionName]['expired'] ?? CRM_REQUEST_TIME+86400;
          self::setItem($_SESSION[$sessionName], 'CiviCRM Session', $sessionName, NULL, $expired);
          // CRM_Core_Error::debug_var( "session value for: {$sessionName}",
          // $_SESSION[$sessionName] );
          if ($resetSession) {
            $_SESSION[$sessionName] = NULL;
            unset($_SESSION[$sessionName]);
          }
        }
      }
    }

    // CRM_Core_Error::debug_var( 'SESSION STATE STORE', $_SESSION );
    self::cleanupSessionCache();
  }

  static function restoreSessionFromCache($names) {
    // CRM_Core_Error::debug_var( 'names in restore', $names );
    foreach ($names as $key => $sessionName) {
      if (is_array($sessionName)) {
        $value = self::getItem('CiviCRM Session',
          "{$sessionName[0]}_{$sessionName[1]}"
        );
        if ($value) {
          // CRM_Core_Error::debug( "session value for: {$sessionName[0]}_{$sessionName[1]}", $value );
          $_SESSION[$sessionName[0]][$sessionName[1]] = $value;
        }
        else {
          // CRM_Core_Error::debug_var( "session value for: {$sessionName[0]}_{$sessionName[1]} is null", $value );
        }
      }
      else {
        $value = self::getItem('CiviCRM Session',
          $sessionName
        );
        if ($value) {
          // CRM_Core_Error::debug( "session value for: {$sessionName}", $value );
          $_SESSION[$sessionName] = $value;
        }
        else {
          // CRM_Core_Error::debug_var( "session value for: {$sessionName} is null", $value );
        }
      }
    }

    // CRM_Core_Error::debug_var( 'SESSION STATE RESTORE', $_SESSION );
    // CRM_Core_Error::debug_var( 'REQUEST', $_REQUEST );
  }

  /**
   * Clean up session in cache
   *
   * @param boolean $force force to execute cleanup, not base on probability
   * @param boolean $all clean up all session cache in one run, but calc expired / created date
   * @return void
   */
  static function cleanupSessionCache($force = FALSE, $all = FALSE) {
    // clean up the session cache every $cacheCleanUpNumber probabilistically
    $cacheCleanUpNumber = 757;

    // clean up all sessions older than $cacheTimeIntervalDays days
    $cacheTimeIntervalDays = 2;

    if (mt_rand(1, 100000) % $cacheCleanUpNumber == 0 || $force) {
      if ($all) {
        $sql = "DELETE FROM civicrm_cache WHERE group_name = 'CiviCRM Session'";
        CRM_Core_DAO::executeQuery($sql);
      }
      else {
        // clean up based on expired date
        $sql = "DELETE FROM civicrm_cache WHERE group_name = 'CiviCRM Session' AND expired_date IS NOT NULL AND expired_date < NOW()";
        CRM_Core_DAO::executeQuery($sql);

        // clean up based on created date
        $sql = "DELETE FROM civicrm_cache WHERE group_name = 'CiviCRM Session' AND created_date < date_sub( NOW( ), INTERVAL $cacheTimeIntervalDays day )";
        CRM_Core_DAO::executeQuery($sql);
      }
    }
  }

  static function getItemCreatedDate($group, $path, $componentID = NULL) {
    $dao = new CRM_Core_DAO_Cache();
    $dao->group_name = $group;
    $dao->path = $path;
    $dao->component_id = $componentID;
    if ($dao->find(TRUE)) {
      return $dao->created_date;
    }
  }
}

