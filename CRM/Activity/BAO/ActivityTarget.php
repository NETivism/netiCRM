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

require_once 'CRM/Activity/DAO/ActivityTarget.php';

/**
 * This class is for activity assignment functions
 *
 */
class CRM_Activity_BAO_ActivityTarget extends CRM_Activity_DAO_ActivityTarget
{

    /**
     * class constructor
     */
    function __construct( ) 
    {
        parent::__construct( );
    }

    /**
     * funtion to add activity target
     *
     * @param array  $activity_id           (reference ) an assoc array of name/value pairs
     * @param array  $target_contact_id     (reference ) the array that holds all the db ids
     *
     * @return object activity type of object that is added
     * @access public
     * 
     */
    public function create( &$params ) 
    {
        require_once 'CRM/Activity/BAO/ActivityTarget.php';
        $target =& new CRM_Activity_BAO_ActivityTarget();

        $target->copyValues( $params );
        return $target->save();
    }

    /**
     * function to retrieve id of target contact by activity_id
     *
     * @param int    $id  ID of the activity
     * 
     * @return mixed
     * 
     * @access public
     * 
     */
    static function retrieveTargetIdsByActivityId( $activity_id ) 
    {
        $targetArray = array();
        require_once 'CRM/Utils/Rule.php';
        if ( ! CRM_Utils_Rule::positiveInteger( $activity_id ) ) {
            return $targetArray;
        }

        $target =& new CRM_Activity_BAO_ActivityTarget( );
        $target->activity_id = $activity_id;
        $target->find();
        $count = 1;
        while ( $target->fetch() ) {
            $targetArray[$count] = $target->target_contact_id;
            $count++;
        }
        return $targetArray;
    }

    /**
     * function to retrieve names of target contact by activity_id
     *
     * @param int    $id  ID of the activity
     * 
     * @return array
     * 
     * @access public
     * 
     */
    static function getTargetNames( $activity_id ) 
    {
        $queryParam = array();
        $query = "SELECT contact_a.id, contact_a.sort_name 
                  FROM civicrm_contact contact_a 
                  LEFT JOIN civicrm_activity_target 
                         ON civicrm_activity_target.target_contact_id = contact_a.id
                  WHERE civicrm_activity_target.activity_id = {$activity_id}";
        $dao = CRM_Core_DAO::executeQuery($query,$queryParam);
        $targetNames = array();
        while ( $dao->fetch() ) {
            $targetNames[$dao->id] =  $dao->sort_name;
        }

        return $targetNames;
    }

}


