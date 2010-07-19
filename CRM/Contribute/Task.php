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

/**
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms
 *
 */
class CRM_Contribute_Task {
    const
        DELETE_CONTRIBUTIONS =  1,
        PRINT_CONTRIBUTIONS  =  2,
        EXPORT_CONTRIBUTIONS =  3,
        BATCH_CONTRIBUTIONS  =  4,
        EMAIL_CONTACTS       =  5,
        UPDATE_STATUS        =  6,
        PDF_RECEIPT          =  7;

    /**
     * the task array
     *
     * @var array
     * @static
     */
    static $_tasks = null;

    /**
     * the optional task array
     *
     * @var array
     * @static
     */
    static $_optionalTasks = null;

    /**
     * These tasks are the core set of tasks that the user can perform
     * on a contact / group of contacts
     *
     * @return array the set of tasks for a group of contacts
     * @static
     * @access public
     */
    static function &tasks()
    {
        if (!(self::$_tasks)) {
            self::$_tasks = array(
                                  3 => ts( 'Export Contributions'   ),
                                  1 => ts( 'Delete Contributions'   ),
                                  5 => ts( 'Send Email to Contacts' ),
                                  7 => ts( 'Print Contribution Receipts' ),
                                  6 => ts( 'Update Pending Contribution Status' ),
                                  4 => ts( 'Batch Update Contributions Via Profile' ),
                                  );
            
            //CRM-4418, check for delete 
            if ( !CRM_Core_Permission::check( 'delete in CiviContribute' ) ) {
                unset( self::$_tasks[1] );
            }
        }
        return self::$_tasks;
    }

    /**
     * show tasks selectively based on the permission level
     * of the user
     *
     * @param int $permission
     *
     * @return array set of tasks that are valid for the user
     * @access public
     */
    static function &permissionedTaskTitles( $permission ) 
    {
        $allTasks = self::tasks( );
        if ( ( $permission == CRM_Core_Permission::EDIT ) 
             || CRM_Core_Permission::check( 'edit contributions' ) ) {
            return $allTasks; 
        } else {
            $tasks = array( 
                           3  => self::$_tasks[3],
                           5  => self::$_tasks[5],
                           7  => self::$_tasks[7],
                           );
            
            //CRM-4418,
            if ( CRM_Core_Permission::check( 'delete in CiviContribute' ) ) {
                $tasks[1] = self::$_tasks[1]; 
            }
            
            return $tasks;
        }
    }
}


