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

require_once 'CRM/Event/Form/Task.php';
require_once "CRM/Event/BAO/Participant.php";

/**
 * This class provides the functionality to delete a group of
 * participations. This class provides functionality for the actual
 * deletion.
 */
class CRM_Event_Form_Task_Delete extends CRM_Event_Form_Task {

  /**
   * Are we operating in "single mode", i.e. deleting one
   * specific participation?
   *
   * @var boolean
   */
  protected $_single = FALSE;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {

    //check for delete
    if (!CRM_Core_Permission::checkActionPermission('CiviEvent', CRM_Core_Action::DELETE)) {
       return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page'));
    }
    parent::preProcess();
    foreach ($this->_participantIds as $participantId) {
      if (CRM_Event_BAO_Participant::isPrimaryParticipant($participantId)) {
        $this->assign('additionalParticipants', TRUE);
      }
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $deleteParticipants = array(
      1 => ts('Delete this participant record along with associated participant record(s).'),
      2 => ts('Delete only this participant record.'),
    );


    $this->addRadio('delete_participant', NULL, $deleteParticipants, NULL, '<br />');
    $this->setDefaults(array('delete_participant' => 1));
    $this->assign("additionalParticipant", $additionalParticipant);

    $this->addDefaultButtons(ts('Delete Participations'), 'done');
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    require_once "CRM/Event/BAO/Participant.php";

    if (CRM_Utils_Array::value('delete_participant', $params) == 2) {
      $links = array();
      foreach ($this->_participantIds as $participantId) {
        $additionalId = (CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId));
        $participantLinks = (CRM_Event_BAO_Participant::getAdditionalParticipantUrl($additionalId));
      }
    }
    $deletedParticipants = 0;
    foreach ($this->_participantIds as $participantId) {
      if (CRM_Utils_Array::value('delete_participant', $params) == 1) {
        if (CRM_Event_BAO_Participant::isPrimaryParticipant($participantId)) {
          $additionalIds = (CRM_Event_BAO_Participant::getAdditionalParticipantIds($participantId));
          foreach ($additionalIds as $value) {
            CRM_Event_BAO_Participant::deleteParticipant($value);
            $deletedParticipants++;
          }
          CRM_Event_BAO_Participant::deleteParticipant($participantId);
          $deletedParticipants++;
        }
      }
      else {
        CRM_Event_BAO_Participant::deleteParticipant($participantId);
        $deletedParticipants++;
      }
    }

    $status = array(
      ts('Deleted Participation(s): %1', array(1 => $deletedParticipants)),
      ts('Total Selected Participation(s): %1', array(1 => $deletedParticipants)),
    );


    if (!empty($participantLinks)) {
      $status[] = ts('The following participants no longer have an event fee recorded. You can edit their registration and record a replacement contribution by clicking the links below:') . '<br>' . $participantLinks;
    }
    CRM_Core_Session::setStatus($status);
  }
}

