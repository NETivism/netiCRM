<?php

class CRM_Event_Form_Registration_ParticipantCheckin extends CRM_Event_Form_Registration {

  public $_csString;
  public $_contactId;
  public $_statusId;
  public $_event;
  public $_perm;
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
       return CRM_Core_Error::statusBounce(ts('You do not have access to this page'));
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
       return CRM_Core_Error::statusBounce(ts("Something wrong with your check-in url. Contact event manager for further assistant."), $eventInfoUrl);
      return;
    }

    $this->_perm = CRM_Core_Permission::check("edit event participants");
    if (!$this->_perm) {
       return CRM_Core_Error::statusBounce(ts("You can only check in event with event manager."), $eventInfoUrl);
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
      $participantStatusPositive = CRM_Event_PseudoConstant::participantStatus( null, "class = 'Positive'" );
      $attendedStatusId = array_search('Attended', $participantStatus);
      $this->assign('participant_id', $this->_participantId);
      $this->assign('contact_id', $this->_contactId);
      if (empty($participantStatusPositive[$this->_statusId])){
        $this->assign('status', ts($participantStatus[$this->_statusId]));
        $this->assign("status_message", ts("This participant is not registered."));
        $this->addRule("qfKey", ts('This participant is not registered.'), 'integer');
        $this->assign("pending", 1);
      }
      elseif ($this->_statusId == $attendedStatusId) {
        $this->assign('status', ts($participantStatus[$this->_statusId]));
        $this->assign("status_message", ts("This participant already checked in before. No further action needed."));
        $this->addRule("qfKey", ts('This participant already checked in before. No further action needed.'), 'integer');
        // find check-in date in activity
        $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Event Registration', 'name');
        $checkinDate = CRM_Core_DAO::singleValueQuery("SELECT activity_date_time FROM civicrm_activity WHERE activity_type_id = %1 AND subject LIKE '%Attended%' AND source_record_id = %2 ORDER BY activity_date_time DESC LIMIT 1", array(
          1 => array($activityTypeId, 'Integer'),
          2 => array($this->_participantId, 'Integer'),
        ));
        $this->assign("checkin_date", $checkinDate);
        $this->assign("attended", 1);
      }
      else {
        // show status change to manager
        $this->assign('status_before', ts($participantStatus[$this->_statusId]));
        $this->assign('status_after', ts($participantStatus[$attendedStatusId]));
        $buttons = array(array(
          'type' => 'next',
          'name' => ts('Confirm'),
        ));
        $this->addButtons($buttons);
        $this->assign("status_message", ts("Press confirm to process check-in for this participant."));
        $this->assign("current_date", date('Y-m-d H:i:s'));
        $session = CRM_Core_Session::singleton();
        $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
          "reset=1&participantId={$this->_participantId}&cs={$this->_csString}"
        ));
      }
    }
  }

  public function postProcess() {
    $participantStatus = CRM_Event_PseudoConstant::participantstatus();
    $attendedStatusId = array_search('Attended', $participantStatus);
    $params = array(
      'id' => $this->_participantId,
      'status_id' => $attendedStatusId,
    );
    CRM_Event_BAO_Participant::create($params);
    $displayName = CRM_Core_DAO::getFieldValue("CRM_Contact_DAO_Contact", $this->_contactId, 'display_name');
    CRM_Core_Session::setStatus(ts("%1 status has been updated to %2.", array(
      1 => $displayName,
      2 => ts("Attended"),
    )));
  }
}