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

require_once 'CRM/Event/Form/ManageEvent.php';

/**
 * This class is to build the form for Deleting Group
 */
class CRM_Event_Form_ManageEvent_Delete extends CRM_Event_Form_ManageEvent {

  /**
   * page title
   *
   * @var string
   * @protected
   */
  protected $_title;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();

    if ($this->_isTemplate) {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'template_title');
    }
    else {
      $this->_title = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_id, 'title');
    }

    require_once 'CRM/Event/BAO/Event.php';
    if (!CRM_Event_BAO_Event::checkPermission($this->_id, CRM_Core_Permission::DELETE)) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }
  }

  /**
   * Function to actually build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->assign('title', $this->_title);

    $buttons = array(
      array(
        'type' => 'next',
        'name' => $this->_isTemplate ? ts('Delete Event Template') : ts('Delete Event'),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ),
    );
    $this->addButtons($buttons);
  }

  /**
   * Process the form when submitted
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    require_once 'CRM/Event/DAO/Participant.php';
    $participant = new CRM_Event_DAO_Participant();
    $participant->event_id = $this->_id;

    if ($participant->find()) {
      $searchURL = CRM_Utils_System::url('civicrm/event/search', 'reset=1');
      CRM_Core_Session::setStatus(ts('This event cannot be deleted because there are participant records linked to it. If you want to delete this event, you must first find the participants linked to this event and delete them. You can use use <a href=\'%1\'> CiviEvent >> Find Participants page </a>.',
          array(1 => $searchURL)
        ));
      return;
    }
    CRM_Event_BAO_Event::del($this->_id);
    if ($this->_isTemplate) {
      CRM_Core_Session::setStatus(ts("Event template '%1' has been deleted.", array(1 => $this->_title)));
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/eventTemplate', 'reset=1'));
    }
    else {
      CRM_Core_Session::setStatus(ts("Event '%1' has been deleted.", array(1 => $this->_title)));
    }
  }
}

