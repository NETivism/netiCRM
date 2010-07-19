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
class CRM_Event_Task
{
    const
        DELETE_EVENTS                     =     1,
        PRINT_EVENTS                      =     2,
        EXPORT_EVENTS                     =     3,
        BATCH_EVENTS                      =     4,
        CANCEL_REGISTRATION               =     5,
        EMAIL_CONTACTS                    =     6,
        // Value for SAVE_SEARCH is set as 13 in accordance with CRM_Contact_Task::SAVE_SEARCH
        SAVE_SEARCH                       =     13,
        SAVE_SEARCH_UPDATE                =     14,
        PARTICIPANT_STATUS                =     15;

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
                                  1     => ts( 'Delete Participants'                   ),
                                  3     => ts( 'Export Participants'                   ),
                                  4     => ts( 'Batch Update Participants Via Profile' ),
                                  5     => ts( 'Cancel Registration'                   ),
                                  13    => ts( 'New Smart Group'                       ),
                                  6     => ts( 'Send Email to Contacts'                ), 
                                  15    => ts( 'Change Participant Status'             ),
                                  );
            
            //CRM-4418, check for delete 
            if ( !CRM_Core_Permission::check( 'delete in CiviEvent' ) ) {
                unset( self::$_tasks[1] );
            }
        }
        
        asort(self::$_tasks);        
        return self::$_tasks;
    }

    /**
     * These tasks get added based on the context the user is in
     *
     * @return array the set of optional tasks for a group of contacts
     * @static
     * @access public
     */
    static function &optionalTaskTitle()
    {
        $tasks = array(
                       14    => ts( 'Update Smart Group')
                       );
        return $tasks;
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
             || CRM_Core_Permission::check( 'edit event participants' ) ) {
            return $allTasks; 
        } else {
            $tasks = array( 
                           3  => self::$_tasks[3],
                           6  => self::$_tasks[6]
                           );
            
            //CRM-4418,
            if ( CRM_Core_Permission::check( 'delete in CiviEvent' ) ) {
                $tasks[1] = self::$_tasks[1]; 
            }
            return $tasks;
        }
    }
}

