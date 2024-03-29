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

require_once 'CRM/Core/StateMachine.php';
require_once 'CRM/Core/Action.php';
require_once 'CRM/Contact/Task.php';
class CRM_Contact_StateMachine_Search extends CRM_Core_StateMachine {

  /**
   * The task that the wizard is currently processing
   *
   * @var string
   * @protected
   */
  protected $_task;

  /**
   * class constructor
   */
  function __construct($controller, $action = CRM_Core_Action::NONE) {
    parent::__construct($controller, $action);

    $this->_pages = array();
    if ($action == CRM_Core_Action::ADVANCED) {
      $this->_pages['CRM_Contact_Form_Search_Advanced'] = NULL;
      list($task, $result) = $this->taskName($controller, 'Advanced');
    }
    elseif ($action == CRM_Core_Action::PROFILE) {
      $this->_pages['CRM_Contact_Form_Search_Builder'] = NULL;
      list($task, $result) = $this->taskName($controller, 'Builder');
    }
    elseif ($action == CRM_Core_Action::COPY) {
      $this->_pages['CRM_Contact_Form_Search_Custom'] = NULL;
      list($task, $result) = $this->taskName($controller, 'Custom');
    }
    else {
      $this->_pages['CRM_Contact_Form_Search_Basic'] = NULL;
      list($task, $result) = $this->taskName($controller, 'Basic');
    }
    $this->_task = $task;
    if (is_array($task)) {
      foreach ($task as $t) {
        $this->_pages[$t] = NULL;
      }
    }
    else {
      $this->_pages[$task] = NULL;
    }

    if ($result) {
      $this->_pages['CRM_Contact_Form_Task_Result'] = NULL;
    }

    $this->addSequentialPages($this->_pages);
  }

  /**
   * Determine the form name based on the action. This allows us
   * to avoid using  conditional state machine, much more efficient
   * and simpler
   *
   * @param CRM_Core_Controller $controller the controller object
   *
   * @return string the name of the form that will handle the task
   * @access protected
   */
  function taskName($controller, $formName = 'Search') {
    // total hack, check POST vars and then session to determine stuff
    // fix value if print button is pressed
    if (CRM_Utils_Array::value('_qf_' . $formName . '_next_print', $_POST)) {
      $value = CRM_Contact_Task::PRINT_CONTACTS;
    }
    else {
      $value = CRM_Utils_Array::value('task', $_POST);
    }
    if (!isset($value)) {
      $value = $this->_controller->get('task');
    }
    $this->_controller->set('task', $value);

    if ($value) {
      $componentMode = $this->_controller->get('component_mode');
      require_once 'CRM/Contact/Form/Search.php';

      $modeValue = CRM_Contact_Form_Search::getModeValueCommon($componentMode);
      require_once (str_replace('_', DIRECTORY_SEPARATOR, $modeValue['taskClassName']) . '.php');
      $taskClassName = $modeValue['taskClassName'];

      // build tasks for custom class
      if ($formName == 'Custom') {
        $customSearchClass = $controller->get('customSearchClass');
        if (method_exists($customSearchClass, 'tasks')) {
          $tasks = $customSearchClass::tasks();
          if (!empty($tasks)) {
            CRM_Contact_Task::initTasks($tasks);
          }
        }
      }
      return $taskClassName::getTask( $value );
    }
    else {
      return CRM_Contact_Task::getTask($value);
    }
  }

  /**
   * return the form name of the task
   *
   * @return string
   * @access public
   */
  function getTaskFormName() {
    if (is_array($this->_task)) {
      // return first page
      return CRM_Utils_String::getClassName($this->_task[0]);
    }
    else {
      return CRM_Utils_String::getClassName($this->_task);
    }
  }
}

