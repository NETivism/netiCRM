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
 * This class handle activity view mode
 *
 */
class CRM_Activity_Form_ActivityView extends CRM_Core_Form {

  public $_mailing_id;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    //get the activity values
    $activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $context = CRM_Utils_Request::retrieve('context', 'String', $this);
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);

    //check for required permissions, CRM-6264
    if ($activityId &&
      !CRM_Activity_BAO_Activity::checkPermission($activityId, CRM_Core_Action::VIEW)
    ) {
      return CRM_Core_Error::statusBounce(ts('You do not have permission to access this page.'));
    }

    $session = CRM_Core_Session::singleton();
    if ($context != 'home') {
      $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$cid}&selectedChild=activity");
    }
    else {
      $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
    }

    $session->pushUserContext($url);

    $params = ['id' => $activityId];
    CRM_Activity_BAO_Activity::retrieve($params, $defaults);

    //set activity type name and description to template

    list($activityTypeName, $activityTypeDescription, $activityTypeMachineName) = CRM_Core_BAO_OptionValue::getActivityTypeDetails($defaults['activity_type_id']);

    $this->assign('activityTypeName', $activityTypeName);
    if ($activityTypeMachineName != 'SMS') {
      $this->assign('activityTypeDescription', $activityTypeDescription);
    }

    if (CRM_Utils_Array::value('mailingId', $defaults)) {
      $this->_mailing_id = CRM_Utils_Array::value('source_record_id', $defaults);

      $mailingReport = &CRM_Mailing_BAO_Mailing::report($this->_mailing_id, TRUE);
      CRM_Mailing_BAO_Mailing::getMailingContent($mailingReport, $this);
      $this->assign('mailingReport', $mailingReport);
    }

    $allActivityStatus = CRM_Core_PseudoConstant::activityStatus();
    if ($defaults['status_id']) {
      $defaults['status'] = $allActivityStatus[$defaults['status_id']];
    }

    foreach ($defaults as $key => $value) {
      if (substr($key, -3) != '_id') {
        $values[$key] = $value;
      }
    }


    $values['attachment'] = CRM_Core_BAO_File::attachmentInfo('civicrm_activity',
      $activityId
    );
    $this->assign('values', $values);

    if (in_array($activityTypeMachineName, explode(',', CRM_Mailing_BAO_Transactional::ALLOWED_ACTIVITY_TYPES))) {
      $this->assign('is_transactional', TRUE);
      $mailingEvents = CRM_Mailing_Event_BAO_Transactional::getEventsByActivity($activityId);
      if (!empty($mailingEvents)) {
        $mailingEvents = CRM_Mailing_Event_BAO_Transactional::formatMailingEvents($mailingEvents);
        $this->assign('mailing_events', $mailingEvents);
      }
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    $this->addButtons([
        ['type' => 'next',
          'name' => ts('Done'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
      ]
    );
  }
}

