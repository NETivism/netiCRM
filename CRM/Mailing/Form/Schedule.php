<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.1                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */




/**
 *
 */
class CRM_Mailing_Form_Schedule extends CRM_Core_Form {

  public $_searchBasedMailing;
  public $_mailingID;
  public $_scheduleFormOnly;
  public $_recipientsCount;
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {

    if (CRM_Mailing_Info::workflowEnabled() &&
      !CRM_Core_Permission::check('schedule mailings')
    ) {
      $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'reset=1&scheduled=false');
      CRM_Utils_System::redirect($url);
    }

    //when user come from search context.

    $this->_searchBasedMailing = CRM_Contact_Form_Search::isSearchContext($this->get('context'));

    $this->_mailingID = $this->get('mailing_id');
    $this->_scheduleFormOnly = FALSE;
    if (!$this->_mailingID) {
      $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, TRUE);
      $this->_scheduleFormOnly = TRUE;
    }
    if ($this->_scheduleFormOnly) {
      $this->_recipientsCount = CRM_Mailing_BAO_Recipients::mailingSize($this->_mailingID);
    }
    else {
      $this->_recipientsCount = $this->get('count');
    }
    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->_mailingID;
    $mailing->find(TRUE);
    if ($mailing->name) {
      CRM_Utils_System::setTitle($mailing->name);
    }
  }

  /**
   * This function sets the default values for the form.
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    $defaults = [];
    $this->assign('count', $this->_recipientsCount);
    $defaults['now'] = 1;
    return $defaults;
  }

  /**
   * Build the form for the last step of the mailing wizard
   *
   * @param
   *
   * @return void
   * @access public
   */
  public function buildQuickform() {
    $this->addDateTime('start_date', ts('Schedule Mailing'), FALSE, ['formatType' => 'mailing']);

    $this->addElement('checkbox', 'now', ts('Send Immediately'));

    $this->addFormRule(['CRM_Mailing_Form_Schedule', 'formRule'], $this);

    $disabled = [];
    if ($this->_recipientsCount <= 0) {
      $disabled = ['disabled' => 'disabled'];
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
      if (CRM_Utils_Rule::qfKey($qfKey)){
        $this->assign('backURL', CRM_Utils_System::url("civicrm/mailing/send", "_qf_Group_display=true&qfKey=".$qfKey));
      }
    }

    if ($this->_scheduleFormOnly) {
      $title = ts('Schedule Mailing') . ' - ' . CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing',
        $this->_mailingID,
        'name'
      );
      CRM_Utils_System::setTitle($title);
      $buttons = [
        ['type' => 'next',
          'name' => ts('Submit Mailing'),
          'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
          'isDefault' => TRUE,
          'js' => $disabled,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ];
    }
    else {
      //FIXME : currently we are hiding save an continue later when
      //search base mailing, we should handle it when we fix CRM-3876
      if ($this->_searchBasedMailing) {
        $buttons = [
          ['type' => 'back',
            'name' => ts('<< Previous'),
          ],
          [
            'type' => 'next',
            'name' => ts('Submit Mailing'),
            'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
            'isDefault' => TRUE,
            'js' => $disabled,
          ],
        ];
      }
      else {
        $buttons = [
          ['type' => 'back',
            'name' => ts('<< Previous'),
          ],
          [
            'type' => 'next',
            'name' => ts('Submit Mailing'),
            'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
            'isDefault' => TRUE,
            'js' => ['data' => 'submit-once'] + $disabled,
          ],
          [
            'type' => 'cancel',
            'name' => ts('Continue Later'),
          ],
        ];
      }
    }
    $this->addButtons($buttons);

    if (CRM_Mailing_Info::workflowEnabled() &&
      $this->_scheduleFormOnly
    ) {
      // add the preview elements
      $preview = [];
      $preview['type'] = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $this->_mailingID, 'body_html') ? 'html' : 'text';
      $preview['subject'] = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing',
        $this->_mailingID,
        'subject'
      );
      $preview['viewURL'] = CRM_Utils_System::url('civicrm/mailing/view', "reset=1&id={$this->_mailingID}");


      $preview['attachment'] = CRM_Core_BAO_File::attachmentInfo('civicrm_mailing',
        $this->_mailingID
      );

      $this->assign_by_ref('preview', $preview);
    }
  }

  /**
   * Form rule to validate the date selector and/or if we should deliver
   * immediately.
   *
   * Warning: if you make changes here, be sure to also make them in
   * Retry.php
   *
   * @param array $params     The form values
   *
   * @return boolean          True if either we deliver immediately, or the
   *                          date is properly set.
   * @static
   */
  public static function formRule($params, $files, $self) {
    if (!empty($params['_qf_Schedule_submit'])) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $self->get('ssID');
      if ($ssID && $self->_searchBasedMailing) {
        if ($self->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($self->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($self->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status = ts("Your mailing has been saved. You can continue later by clicking the 'Continue' action to resume working on it.<br /> From <a href='%1'>Draft and Unscheduled Mailings</a>.", [1 => $draftURL]);
        CRM_Core_Session::setStatus($status);

        //replace user context to search.
        $context = $self->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }

        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, "force=1&reset=1&ssID={$ssID}");
        CRM_Utils_System::redirect($url);
      }
      else {
        CRM_Core_Session::setStatus(ts("Your mailing has been saved. Click the 'Continue' action to resume working on it."));
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        CRM_Utils_System::redirect($url);
      }
    }
    if (isset($params['now']) || CRM_Utils_Array::value('_qf_Schedule_back', $params) == '<< Previous') {
      return TRUE;
    }

    if (CRM_Utils_Date::format(CRM_Utils_Date::processDate($params['start_date'],
          $params['start_date_time']
        )) < CRM_Utils_Date::format(date('YmdHi00'))) {
      return [
        'start_date' =>
        ts('Start date cannot be earlier than the current time.'),
      ];
    }
    return TRUE;
  }

  /**
   * Process the posted form values.  Create and schedule a mailing.
   *
   * @param
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    $params = [];

    $params['mailing_id'] = $ids['mailing_id'] = $this->_mailingID;

    if (empty($params['mailing_id'])) {
      CRM_Core_Error::fatal(ts('Could not find a mailing id'));
    }

    foreach ([
        'now', 'start_date', 'start_date_time',
      ] as $parameter) {
      $params[$parameter] = $this->controller->exportValue($this->_name,
        $parameter
      );
    }

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $ids['mailing_id'];
    if ($mailing->find(TRUE)) {
      $job = new CRM_Mailing_BAO_Job();
      $job->mailing_id = $mailing->id;
      $job->is_test = 0;
      if ($job->find(TRUE) && !empty($job->start_date)) {
        CRM_Core_Error::fatal(ts('A job for this mailing already exists'));
      }

      if (empty($mailing->is_template)) {
        $job->status = 'Scheduled';
        if ($params['now']) {
          $job->scheduled_date = date('YmdHis');
        }
        else {
          $job->scheduled_date = CRM_Utils_Date::processDate($params['start_date'] . ' ' . $params['start_date_time']);
        }
        $job->end_date = null;
        $job->save();
      }

      // set approval details if workflow is not enabled
      /*
      if (!CRM_Mailing_Info::workflowEnabled()) {
        $session = CRM_Core_Session::singleton();
        $mailing->approver_id = $session->get('userID');
        $mailing->approval_date = date('YmdHis');
        $mailing->approval_status_id = 1;
      }
      else {
        // reset them in case this mailing was rejected
        $mailing->approver_id = 'null';
        $mailing->approval_date = 'null';
        $mailing->approval_status_id = 'null';
      }

      if ($mailing->approval_date) {
        $mailing->approval_date = CRM_Utils_Date::isoToMysql($mailing->approval_date);
      }
      */


      // also set the scheduled_id
      $session = CRM_Core_Session::singleton();
      $mailing->scheduled_id = $session->get('userID');
      $mailing->scheduled_date = date('YmdHis');
      $mailing->created_date = CRM_Utils_Date::isoToMysql($mailing->created_date);
      $mailing->save();
    }

    //when user perform mailing from search context
    //redirect it to search result CRM-3711.
    $ssID = $this->get('ssID');
    $gid = $this->get('smog');
    if ($ssID &&
      $this->_searchBasedMailing &&
      !CRM_Mailing_Info::workflowEnabled()
    ) {
      if (!empty($gid)) { // special case for group search
        return $this->controller->setDestination(CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid='.$gid));
      }
      elseif ($this->_action == CRM_Core_Action::BASIC) {
        $fragment = 'search';
      }
      elseif ($this->_action == CRM_Core_Action::PROFILE) {
        $fragment = 'search/builder';
      }
      elseif ($this->_action == CRM_Core_Action::ADVANCED) {
        $fragment = 'search/advanced';
      }
      else {
        $fragment = 'search/custom';
      }
      $context = $this->get('context');
      if (!CRM_Contact_Form_Search::isSearchContext($context)) {
        $context = 'search';
      }
      $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
      if (CRM_Utils_Rule::qfKey($qfKey)) {
        $urlParams .= "&qfKey=$qfKey";
      }

      $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, $urlParams);
      return $this->controller->setDestination($url);
    }

    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/mailing/browse/scheduled',
        'reset=1&scheduled=true'
      ));
  }

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Schedule or Send');
  }
}

