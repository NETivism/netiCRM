<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
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
    static function &getItem( $group, $path, $componentID = null ) {
        $dao = new CRM_Core_DAO_Cache( );

        $dao->group_name = $group;
        $dao->path  = $path;
        $dao->component_id = $componentID;

        $data = null;
        if ( $dao->find( true ) ) {
            $data = unserialize( $dao->data );
        }
        return $data;
    }

    static function setItem( &$data,
                             $group, $path, $componentID = null ) {
        $dao = new CRM_Core_DAO_Cache( );

        $dao->group_name = $group;
        $dao->path  = $path;
        $dao->component_id = $componentID;

        $dao->find( true );
        $dao->data = serialize( $data );
        $dao->save( );
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

}