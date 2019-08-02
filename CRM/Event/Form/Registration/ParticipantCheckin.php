<?php

class CRM_Event_Form_Registration_ParticipantCheckin extends CRM_Event_Form_Registration {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    // required parameter from url
    $this->_participantId = CRM_Utils_Request::retrieve('participantId', 'Positive', $this, TRUE);
    $this->_csString = CRM_Utils_Request::retrieve('cs', 'String', $this, TRUE);

    // prepare parameter of participant and event
    $params = array('id' => $this->_participantId);
    $values = array();
    CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Participant', $params, $values, array('contact_id', 'event_id', 'status_id', 'register_date'));
    $this->assign('participant', $values);
    $this->_eventId = $values['event_id'];
    $this->_contactId = $values['contact_id'];
    $this->_statusId = $values['status_id'];
    if (empty($this->_eventId) || empty($this->_contactId)) {
      CRM_Core_Error::fatal(ts('You do not have access to this page'));
      return;
    }
    $params = array('id' => $this->_eventId);
    $values = array();
    CRM_Core_DAO::commonRetrieve('CRM_Event_DAO_Event', $params, $values, array('title'));
    $this->_event = $values;
    CRM_Utils_System::setTitle(ts("Check In").' - '.$values['title']);

    // check permissions
    $eventInfoUrl = CRM_Utils_System::url('civicrm/event/info', "reset=1&id=".$this->_eventId);
    if (!CRM_Contact_BAO_Contact_Permission::validateChecksumContact($this->_contactId, $this)) {
      CRM_Core_Error::statusBounce(ts("Something wrong with your check-in url. Contact event manager for further assistant."), $eventInfoUrl);
      return;
    }

    $this->_perm = CRM_Core_Permission::check("edit event participants");
    if (!$this->_perm) {
      CRM_Core_Error::statusBounce(ts("You can only check in event with event manager."), $eventInfoUrl);
      return;
    }
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    if (CRM_Core_Permission::check("edit event participants")) {
      $displayName = CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $this->_contactId, 'display_name');
      $this->assign("display_name", $displayName); 
      $this->assign("event", $this->_event);
      $participantStatus = CRM_Event_PseudoConstant::participantstatus();
      $attendedStatusId = array_search('Attended', $participantStatus);
      $currentStatus = $participantStatus[$this->_statusId];
      if ($this->_statusId != $attendedStatusId) {
        // show status change to manager
        $this->assign('status_before', ts($participantStatus[$this->_statusId]));
        $this->assign('status_after', ts($participantStatus[$attendedStatusId]));
        $buttons = array(array(
          'type' => 'next',
          'name' => ts('Confirm'),
        ));
        $this->addButtons($buttons);
        $this->assign("status_message", ts("Press confirm to process check-in for this participant."));
        $this->assign("current_date", CRM_Utils_Date::customFormat(date('YmdHis')));
        $this->assign("checked", 0);
      }
      else {
        $this->assign("status_message", ts("This participant already checked in before. No further action needed."));
        $this->assign("checked", 1);
      }
    }
  }

  public function postProcess() {

  }
}