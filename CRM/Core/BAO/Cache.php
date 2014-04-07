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

require_once 'CRM/Core/DAO/Cache.php';

/**
 * BAO object for crm_log table
 */

class CRM_Core_BAO_Cache extends CRM_Core_DAO_Cache
{
    static $_cache = NULL;
    static function &getItem( $group, $path, $componentID = null ) {
        if (self::$_cache === NULL) {
          self::$_cache = array();
        }
        $argString = "CRM_CT_{$group}_{$path}_{$componentID}";
        if (!array_key_exists($argString, self::$_cache)) {
          $cache = CRM_Utils_Cache::singleton();
          self::$_cache[$argString] = $cache->get($argString);
          if (!self::$_cache[$argString]) {
            $dao = new CRM_Core_DAO_Cache();

            $dao->group_name   = $group;
            $dao->path         = $path;
            $dao->component_id = $componentID;

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

    static function setItem( &$data, $group, $path, $componentID = null ) {
      if (self::$_cache === NULL) {
        self::$_cache = array();
      }

      $dao = new CRM_Core_DAO_Cache();

      $dao->group_name   = $group;
      $dao->path         = $path;
      $dao->component_id = $componentID;

      // get a lock so that multiple ajax requests on the same page
      // dont trample on each other
      // CRM-11234
      $lockName = "civicrm.cache.{$group}_{$path}._{$componentID}";
      $lock = new CRM_Core_Lock($lockName);
      if (!$lock->isAcquired()) {
        CRM_Core_Error::fatal();
      }

      $dao->find(TRUE);
      $dao->data = serialize($data);
      $dao->created_date = date('YmdHis');
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

    static function deleteGroup( $group = null ) {
        $dao = new CRM_Core_DAO_Cache( );
        
        if ( ! empty( $group ) ) {
            $dao->group_name = $group;
        }
        $dao->delete( );

        // also reset ACL Cache
        require_once 'CRM/ACL/BAO/Cache.php';
        CRM_ACL_BAO_Cache::resetCache( );
    }

    static function storeSessionToCache( $names,
                                         $resetSession = true ) {
        // CRM_Core_Error::debug_var( 'names in store', $names );
        foreach ( $names as $key => $sessionName ) {
            if ( is_array( $sessionName ) ) {
                if ( ! empty( $_SESSION[$sessionName[0]][$sessionName[1]] ) ) {
                    self::setItem( $_SESSION[$sessionName[0]][$sessionName[1]],
                                   'CiviCRM Session',
                                   "{$sessionName[0]}_{$sessionName[1]}" );
                    // CRM_Core_Error::debug_var( "session value for: {$sessionName[0]}_{$sessionName[1]}",
                    // $_SESSION[$sessionName[0]][$sessionName[1]] );
                    if ( $resetSession ) {
                        $_SESSION[$sessionName[0]][$sessionName[1]] = null;
                        unset( $_SESSION[$sessionName[0]][$sessionName[1]] );
                    }
                }
            } else {
                if ( ! empty( $_SESSION[$sessionName] ) ) {
                    self::setItem( $_SESSION[$sessionName],
                                   'CiviCRM Session',
                                   $sessionName );
                    // CRM_Core_Error::debug_var( "session value for: {$sessionName}",
                    // $_SESSION[$sessionName] );
                    if ( $resetSession ) {
                        $_SESSION[$sessionName] = null;
                        unset( $_SESSION[$sessionName] );
                    }
                }
            }
        }

        // CRM_Core_Error::debug_var( 'SESSION STATE STORE', $_SESSION );
        self::cleanupCache( );
    }

    static function restoreSessionFromCache( $names ) {
        // CRM_Core_Error::debug_var( 'names in restore', $names );
        foreach ( $names as $key => $sessionName ) {
            if ( is_array( $sessionName ) ) {
                $value = self::getItem( 'CiviCRM Session',
                                        "{$sessionName[0]}_{$sessionName[1]}" );
                if ( $value ) {
                    // CRM_Core_Error::debug( "session value for: {$sessionName[0]}_{$sessionName[1]}", $value ); 
                    $_SESSION[$sessionName[0]][$sessionName[1]] = $value;
                } else {
                    // CRM_Core_Error::debug_var( "session value for: {$sessionName[0]}_{$sessionName[1]} is null", $value );
                }
            } else {
                $value = self::getItem( 'CiviCRM Session',
                                        $sessionName );
                if ( $value ) {
                    // CRM_Core_Error::debug( "session value for: {$sessionName}", $value );
                    $_SESSION[$sessionName] = $value;
                } else {
                    // CRM_Core_Error::debug_var( "session value for: {$sessionName} is null", $value );
                }
            }
        }

        // CRM_Core_Error::debug_var( 'SESSION STATE RESTORE', $_SESSION );
        // CRM_Core_Error::debug_var( 'REQUEST', $_REQUEST );
    }

    static function cleanupCache( ) {
        // clean up the session cache every $cacheCleanUpNumber probabilistically
        $cacheCleanUpNumber     = 1396;

        // clean up all sessions older than $cacheTimeIntervalDays days
        $cacheTimeIntervalDays  = 2;

        if ( mt_rand( 1, 100000 ) % 1396 == 0 ) {
            $sql = "
DELETE FROM civicrm_cache
WHERE       group_name = 'CiviCRM Session'
AND         created_date < date_sub( NOW( ), INTERVAL $cacheTimeIntervalDays day )
";
            CRM_Core_DAO::executeQuery( $sql );
        }
    }
                                         
}
