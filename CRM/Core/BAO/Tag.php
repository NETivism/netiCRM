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

require_once 'CRM/Core/DAO/Tag.php';

class CRM_Core_BAO_Tag extends CRM_Core_DAO_Tag {

    /**
     * class constructor
     */
    function __construct( ) {
        parent::__construct( );
    }

    /**
     * Takes a bunch of params that are needed to match certain criteria and
     * retrieves the relevant objects. Typically the valid params are only
     * contact_id. We'll tweak this function to be more full featured over a period
     * of time. This is the inverse function of create. It also stores all the retrieved
     * values in the default array
     * 
     * @param array $params      (reference ) an assoc array of name/value pairs
     * @param array $defaults    (reference ) an assoc array to hold the flattened values
     * 
     * @return object     CRM_Core_DAO_Tag object on success, otherwise null
     * @access public
     * @static
     */
    static function retrieve( &$params, &$defaults ) {
        $tag =& new CRM_Core_DAO_Tag( );
        $tag->copyValues( $params );
        if ( $tag->find( true ) ) {
            CRM_Core_DAO::storeValues( $tag, $defaults );
            return $tag;
        }
        return null;
    }

    function getTree () {
        if (!isset ($this->tree)) {
            $this->buildTree();
        }
        return $this->tree;
    }
	
    function buildTree() {
        $sql = "SELECT civicrm_tag.id, civicrm_tag.parent_id,civicrm_tag.name FROM civicrm_tag order by parent_id,name;";
        $dao =& CRM_Core_DAO::executeQuery( $sql );

        $orphan = array();
        while ( $dao->fetch( ) ) {
            if (!$dao->parent_id) {
                $this->tree[$dao->id]['name'] = $dao->name;
            } else {
                if (array_key_exists($dao->parent_id,$this->tree)) {
                    $parent =& $this->tree[$dao->parent_id];
                    if (!isset ($this->tree[$dao->parent_id]['children']) ) {
                        $this->tree[$dao->parent_id]['children'] = array();
                    }
                }
                else {
                    //3rd level tag
                    if (!array_key_exists($dao->parent_id,$orphan)) {
                        $orphan[$dao->parent_id]=array('children'=> array());
                    }
                    $parent=& $orphan[$dao->parent_id];
                }
                $parent['children'][$dao->id] = array ('name'=>$dao->name);
            }
        }
        if (sizeof($orphan)) {
            //hang the 3rd level lists at the right place
            foreach ($this->tree as &$level1) {
                if ( ! isset ( $level1['children'] ) ) {
                    continue;
                }

                foreach ( $level1['children'] as $key => &$level2 ) {
                    if ( array_key_exists( $key,$orphan ) ) {
                        $level2['children']= $orphan[$key]['children'];
                    }
                }
            }
        }
    }

    /**
     * Function to delete the tag 
     *
     * @param int $id   tag id
     *
     * @return boolean
     * @access public
     * @static
     *
     */
    static function del ( $id ) {
        // delete all crm_entity_tag records with the selected tag id
        require_once 'CRM/Core/DAO/EntityTag.php';
        $entityTag =& new CRM_Core_DAO_EntityTag( );
        $entityTag->tag_id = $id;
        if ( $entityTag->find( ) ) {
            while ( $entityTag->fetch() ) {
                $entityTag->delete();
            }
        }
        
        // delete from tag table
        $tag =& new CRM_Core_DAO_Tag( );
        $tag->id = $id;
        if ( $tag->delete( ) ) {
            CRM_Core_Session::setStatus( ts('Selected Tag has been Deleted Successfuly.') );
            return true;
        }
        return false;
    }

    /**
     * takes an associative array and creates a contact object
     * 
     * The function extract all the params it needs to initialize the create a
     * contact object. the params array could contain additional unused name/value
     * pairs
     * 
     * @param array  $params         (reference) an assoc array of name/value pairs
     * @param array  $ids            (reference) the array that holds all the db ids
     * 
     * @return object    CRM_Core_DAO_Tag object on success, otherwise null
     * @access public
     * @static
     */
    static function add( &$params, &$ids ) {
        if ( ! self::dataExists( $params ) ) {
            return null;
        }

        $tag               =& new CRM_Core_DAO_Tag( );
        $tag->copyValues( $params );
        $tag->id = CRM_Utils_Array::value( 'tag', $ids );

        $tag->save( );
        
        CRM_Core_Session::setStatus( ts('The tag \'%1\' has been saved.', array(1 => $tag->name)) );
        
        return $tag;
    }

    /**
     * Check if there is data to create the object
     *
     * @param array  $params         (reference ) an assoc array of name/value pairs
     *
     * @return boolean
     * @access public
     * @static
     */
    static function dataExists( &$params ) {
        
        if ( !empty( $params['name'] ) ) {
            return true;
        }
        
        return false;
    }
}


