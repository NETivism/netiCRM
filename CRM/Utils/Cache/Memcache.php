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
require_once 'CRM/Core/BAO/Cache.php';

class CRM_Utils_Cache_Memcache {

    /**
     * The host name of the memcached server
     *
     * @var string
     */
    protected $_host;

    /**
     * The port on which to connect on
     *
     * @var int
     */
    protected $_port;

    /**
     * The default timeout to use
     *
     * @var int
     */
    protected $_timeout;

    /**
     * The actual memcache object
     *
     * @var resource
     */
    protected $_cache;

    /**
     * Constructor
     *
     * @param string  $host      the memcached server host
     * @param int     $port      the memcached server port
     * @param int     $timeout   the default timeout
     *
     * @return void
     */
    function __construct( $host      = 'localhost',
                          $port      = 11211,
                          $timeout   = 3600 ) {
        $this->_host    = $host;
        $this->_port    = $port;
        $this->_timeout = $timeout;
        
        $this->_cache = new Memcache( );
        
        if ( ! $this->_cache->connect( $this->_host, $this->_port ) ) {
            // dont use fatal here since we can go in an infinite loop
            echo 'Could not connect to Memcached server';
            CRM_Utils_System::civiExit( );
        }
        $this->_prefix = defined( 'MEMCACHE_PREFIX' ) ? MEMCACHE_PREFIX : '';
    }

    function set( $key, &$value ) {
        $k = $key;
        $key = $this->_prefix."_".$key;
        if ( ! $this->_cache->set( $key, $value, false, $this->_timeout ) ) {
            return false;
        }
        $dummy = array('dummy');
        CRM_Core_BAO_Cache::setItem( $dummy, 'memcache index', substr($k, 0, 255));

        return true;
    }

    function &get( $key ) {
        $key = $this->_prefix."_".$key;
        $result =& $this->_cache->get( $key );
        return $result;
    }

    function delete( $key ) {
        $wildcard = strpos($key, '*');
        if($wildcard !== false){
          // search for delete cache record
          $search = str_replace('*', '%', $key);
          $q = CRM_Core_DAO::executeQuery( "SELECT path FROM civicrm_cache WHERE path LIKE '$search'");
          while($q->fetch()){
            $k = $this->_prefix."_".$q->path;
            $this->_cache->delete( $k );
          }
          return true;
        }
        else{
          $key = $this->_prefix."_".$key;
          return $this->_cache->delete( $key );
        }
    }

    function flush( ) {
        return $this->_cache->flush( );
    }
        
}


