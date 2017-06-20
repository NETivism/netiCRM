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

/**
 * class to represent the actions that can be performed on a group of contacts
 * used by the search forms
 *
 */
require_once 'CRM/Contact/BAO/ContactType.php';
class CRM_Contact_Task {
  CONST GROUP_CONTACTS = 1, REMOVE_CONTACTS = 2, TAG_CONTACTS = 3, REMOVE_TAGS = 4, EXPORT_CONTACTS = 5, EMAIL_CONTACTS = 6, SMS_CONTACTS = 7, DELETE_CONTACTS = 8, HOUSEHOLD_CONTACTS = 9, ORGANIZATION_CONTACTS = 10, RECORD_CONTACTS = 11, MAP_CONTACTS = 12, SAVE_SEARCH = 13, SAVE_SEARCH_UPDATE = 14, PRINT_CONTACTS = 15, LABEL_CONTACTS = 16, BATCH_UPDATE = 17, ADD_EVENT = 18, PRINT_FOR_CONTACTS = 19, EMAIL_UNHOLD = 22, RESTORE = 23, DELETE_PERMANENTLY = 24;

  /**
   * the task array
   *
   * @var array
   * @static
   */
  static $_tasks = NULL;

  /**
   * the optional task array
   *
   * @var array
   * @static
   */
  static $_optionalTasks = NULL;

  static function initTasks() {
    if (!self::$_tasks) {
      self::$_tasks = array(
        1 => array('title' => ts('Add Contacts to Group'),
          'class' => 'CRM_Contact_Form_Task_AddToGroup',
          'optgroup' => 'Group',
        ),
        2 => array('title' => ts('Remove Contacts from Group'),
          'class' => 'CRM_Contact_Form_Task_RemoveFromGroup',
          'optgroup' => 'Group',
        ),
        3 => array('title' => ts('Tag Contacts (assign tags)'),
          'class' => 'CRM_Contact_Form_Task_AddToTag',
          'optgroup' => 'Tags',
        ),
        4 => array('title' => ts('Untag Contacts (remove tags)'),
          'class' => 'CRM_Contact_Form_Task_RemoveFromTag',
          'optgroup' => 'Tags',
        ),
        5 => array('title' => ts('Export Contacts'),
          'class' => array('CRM_Export_Form_Select',
            'CRM_Export_Form_Map',
          ),
          'result' => FALSE,
          'optgroup' => 'Contact Information',
        ),
        6 => array('title' => ts('Send Email to Contacts'),
          'class' => 'CRM_Contact_Form_Task_Email',
          'result' => TRUE,
          'optgroup' => 'Send Mailing',
        ),
        7 => array('title' => ts('Send SMS to Contacts'),
          'class' => 'CRM_Contact_Form_Task_SMS',
          'result' => TRUE,
          'optgroup' => 'Send Mailing',
        ),
        8 => array('title' => ts('Delete Contacts'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
          'optgroup' => 'Delete Contact',
        ),
        11 => array('title' => ts('Record Activity for Contacts'),
          'class' => 'CRM_Activity_Form_Activity',
          'optgroup' => 'Contact Information',
        ),
        13 => array('title' => ts('New Smart Group'),
          'class' => 'CRM_Contact_Form_Task_SaveSearch',
          'result' => TRUE,
          'optgroup' => 'Group',
        ),
        14 => array('title' => ts('Update Smart Group'),
          'class' => 'CRM_Contact_Form_Task_SaveSearch_Update',
          'result' => TRUE,
          'optgroup' => 'Group',
        ),
        15 => array('title' => ts('Print Contacts'),
          'class' => 'CRM_Contact_Form_Task_Print',
          'result' => FALSE,
          'optgroup' => 'Print',
        ),
        16 => array('title' => ts('Mailing Labels'),
          'class' => 'CRM_Contact_Form_Task_Label',
          'result' => TRUE,
          'optgroup' => 'Print',
        ),
        17 => array('title' => ts('Batch Update via Profile'),
          'class' => array('CRM_Contact_Form_Task_PickProfile',
            'CRM_Contact_Form_Task_Batch',
          ),
          'result' => TRUE,
          'optgroup' => 'Contact Information',
        ),
        19 => array('title' => ts('Print PDF Letter for Contacts'),
          'class' => 'CRM_Contact_Form_Task_PDF',
          'result' => TRUE,
          'optgroup' => 'Print',
        ),
        22 => array('title' => ts('Unhold Emails'),
          'class' => 'CRM_Contact_Form_Task_Unhold',
          'optgroup' => 'Send Mailing',
        ),
        self::RESTORE => array(
          'title' => ts('Restore Contacts'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
          'optgroup' => 'Delete Contact',
        ),
        self::DELETE_PERMANENTLY => array(
          'title' => ts('Delete Permanently'),
          'class' => 'CRM_Contact_Form_Task_Delete',
          'result' => FALSE,
          'optgroup' => 'Delete Contact',
        ),
      );
      if (CRM_Contact_BAO_ContactType::isActive('Household')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('Household');
        self::$_tasks[9] = array('title' => ts('Add Contacts to %1',
            array(1 => $label)
          ),
          'class' => 'CRM_Contact_Form_Task_AddToHousehold',
          'optgroup' => 'Contact Information',
        );
      }
      if (CRM_Contact_BAO_ContactType::isActive('Organization')) {
        $label = CRM_Contact_BAO_ContactType::getLabel('Organization');
        self::$_tasks[10] = array('title' => ts('Add Contacts to %1',
            array(1 => $label)
          ),
          'class' => 'CRM_Contact_Form_Task_AddToOrganization',
          'optgroup' => 'Contact Information',
        );
      }
      if (CRM_Core_Permission::check('merge duplicate contacts')) {
        self::$_tasks[21] = array('title' => ts('Merge Contacts'),
          'class' => 'CRM_Contact_Form_Task_Merge',
          'result' => TRUE,
          'optgroup' => 'Contact Information',
        );
      }
      //CRM-4418, check for delete
      if (!CRM_Core_Permission::check('delete contacts')) {
        unset(self::$_tasks[8]);
      }

      //show map action only if map provider and key is set
      $config = CRM_Core_Config::singleton();

      if ($config->mapProvider && ($config->mapProvider == 'Google' || $config->mapAPIKey)) {
        self::$_tasks[12] = array('title' => ts('Map Contacts'),
          'class' => 'CRM_Contact_Form_Task_Map',
          'result' => FALSE,
          'optgroup' => 'Contact Information',
        );
      }

      if (CRM_Core_Permission::access('CiviEvent')) {
        self::$_tasks[18] = array('title' => ts('Add Contacts to Event'),
          'class' => 'CRM_Event_Form_Participant',
          'optgroup' => 'Event',
        );
      }

      if (CRM_Core_Permission::access('CiviMail')) {
        self::$_tasks[20] = array('title' => ts('Schedule/Send a Mass Mailing'),
          'class' => array('CRM_Mailing_Form_Group',
            'CRM_Mailing_Form_Settings',
            'CRM_Mailing_Form_Upload',
            'CRM_Mailing_Form_Test',
            'CRM_Mailing_Form_Schedule',
          ),
          'result' => FALSE,
          'optgroup' => 'Send Mailing',
        );
      }

      self::$_tasks += CRM_Core_Component::taskList();

      require_once 'CRM/Utils/Hook.php';
      CRM_Utils_Hook::searchTasks('contact', self::$_tasks);

      asort(self::$_tasks);
    }
  }

  /**
   * These tasks are the core set of tasks that the user can perform
   * on a contact / group of contacts
   *
   * @return array the set of tasks for a group of contacts
   * @static
   * @access public
   */
  static function &taskTitles() {
    self::initTasks();

    $titles = self::$_tasks;

    // hack unset update saved search and print contacts
    unset($titles[14]);
    unset($titles[15]);

    $config = CRM_Core_Config::singleton();

    require_once 'CRM/Utils/Mail.php';
    if (!CRM_Utils_Mail::validOutBoundMail()) {
      unset($titles[6]);
      unset($titles[20]);
    }

    if (!in_array('CiviSMS', $config->enableComponents)) {
      unset($titles[7]);
    }

    // CRM-6806
    if (!CRM_Core_Permission::check('access deleted contacts')) {
      unset($titles[self::DELETE_PERMANENTLY]);
    }
    $finalTitles = array();
    $others = array();
    foreach ($titles as $id => $value) {
      $titles[$id] = $value['title'];
      if (!empty($value['optgroup'])) {
        $optgroup = ts($value['optgroup']);
        $finalTitles[$optgroup][$id] = $value['title'];
      }
      else {
        $optgroup = ts('Other');
        $others[$optgroup][$id] = $value['title'];
      }
    }
    if (!empty($others)) {
      $finalTitles += $others;
    }

    return $finalTitles;
  }

  /**
   * show tasks selectively based on the permission level
   * of the user
   *
   * @param int $permission
   * @param bool $deletedContacts  are these tasks for operating on deleted contacts?
   *
   * @return array set of tasks that are valid for the user
   * @access public
   */
  static function &permissionedTaskTitles($permission, $deletedContacts = FALSE) {
    $tasks = array();
    if ($deletedContacts) {
      if (CRM_Core_Permission::check('access deleted contacts')) {
        $tasks = array(
          self::RESTORE => self::$_tasks[self::RESTORE]['title'],
          self::DELETE_PERMANENTLY => self::$_tasks[self::DELETE_PERMANENTLY]['title'],
        );
      }
    }
    elseif ($permission == CRM_Core_Permission::EDIT) {
      $tasks = self::taskTitles();
      // we remove delete permanently in normal interface anyway.
      if (isset($tasks[self::DELETE_PERMANENTLY])) {
        unset($tasks[self::DELETE_PERMANENTLY]);
      }
    }
    else {
      $tasks = array(
        5 => self::$_tasks[5]['title'],
        6 => self::$_tasks[6]['title'],
        12 => self::$_tasks[12]['title'],
        16 => self::$_tasks[16]['title'],
      );
      if (!self::$_tasks[12]['title']) {
        //usset it, No edit permission and Map provider info
        //absent, drop down shows blank space
        unset($tasks[12]);
      }
      //user has to have edit permission to delete contact.
      //CRM-4418, lets keep delete for View and Edit so user can tweak ACL
      //             if ( CRM_Core_Permission::check( 'delete contacts' ) ) {
      //                 $tasks[8] = self::$_tasks[8]['title'];
      //             }
    }

    return $tasks;
  }

  /**
   * These tasks get added based on the context the user is in
   *
   * @return array the set of optional tasks for a group of contacts
   * @static
   * @access public
   */
  static function &optionalTaskTitle() {
    $tasks = array(
      14 => self::$_tasks[14]['title'],
    );
    return $tasks;
  }

  static function getTask($value) {
    self::initTasks();

    if (!CRM_Utils_Array::value($value, self::$_tasks)) {
      // make it the print task by default
      $value = 15;
    }
    return array(CRM_Utils_Array::value('class', self::$_tasks[$value]),
      CRM_Utils_Array::value('result', self::$_tasks[$value]),
    );
  }
}

