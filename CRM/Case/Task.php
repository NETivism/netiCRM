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
class CRM_Case_Task
{
    const
        DELETE_CASES   = 1,
        PRINT_CASES    = 2,
        EXPORT_CASES   = 3,
        RESTORE_CASES  = 4;
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
        if ( !self::$_tasks ) {
            self::$_tasks = array(
                                  1     => ts( 'Delete Cases' ),
                                  3     => ts( 'Export Cases' ),
                                  4     => ts( 'Restore Cases'),
                                  );
            //CRM-4418, check for delete 
            if ( !CRM_Core_Permission::check( 'delete in CiviCase' ) ) {
                unset( self::$_tasks[1] );
            }
        }

        asort( self::$_tasks );
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
        $tasks = array( );
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
             || CRM_Core_Permission::check( 'edit cases' ) ) {
            return $allTasks; 
        } else {
            $tasks = array( 
                           3  => self::$_tasks[3]
                           );
            //CRM-4418,
            if ( CRM_Core_Permission::check( 'delete in CiviCase' ) ) {
                $tasks[1] = self::$_tasks[1]; 
            }
            return $tasks;
        }
    }
}

