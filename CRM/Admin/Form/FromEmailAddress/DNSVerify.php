<?php

class CRM_Admin_Form_FromEmailAddress_DNSVerify extends CRM_Admin_Form_FromEmailAddress {

  /**
   * SPF status
   *
   * @var string
   */
  private $_spfStatus;

  /**
   * DKIM status
   *
   * @var string
   */
  private $_dkimStatus;

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Verify %1', array(1 => ts('Domain')));
  }

  /**
   * Preprocess Form
   *
   * @return void
   */
  function preProcess() {
    $this->set('action', CRM_Core_Action::UPDATE);
    parent::preProcess();

    if ($this->_values['filter'] & self::VALID_SPF) {
      $this->_spfStatus = TRUE;
    }
    else {
      $this->_spfStatus = FALSE;
    }

    if ($this->_values['filter'] & self::VALID_DKIM) {
      $this->_dkimStatus = TRUE;
    }
    else {
      $this->_dkimStatus = FALSE;
    }

    $this->addFormRule(array('CRM_Admin_Form_FromEmailAddress_DNSVerify', 'formRule'), $this);
  }

  /**
   * Rules called by addFormRule above
   *
   * @param array $fields the input form values
   * @param array $files  the uploaded files if any
   * @param object $self   current form object.
   *
   * @return array array of errors / empty array.
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    // verify on every submission
    if (!empty($self->_values['email'])) {
      $errorMsg = array();
      list($user, $domain) = explode('@', trim($self->_values['email']));
      $result = CRM_Utils_Mail::checkSPF($domain);
      $filter = $self->_values['filter'];
      if ($result !== TRUE) {
        $failReason = $result;
        $errorMsg['spf'] = ts('Your %1 validation failed.', array(1 => 'SPF')).' '.ts("Reason").": ".$failReason;
        $filter = $filter & ~(self::VALID_SPF);
      }
      else {
        $filter = $filter | self::VALID_SPF;
        $self->assign('spf_status', TRUE);
      }

      $result = CRM_Utils_Mail::checkDKIM($domain);
      if ($result === FALSE) {
        $errorMsg['dkim'] = ts('Your %1 validation failed.', array(1 => 'DKIM'));
        $filter = $filter & ~(self::VALID_DKIM);
      }
      else {
        $filter = $filter | self::VALID_DKIM;
        $self->assign('dkim_status', TRUE);
      }

      if (!empty($errorMsg)) {
        $errors['qfKey'] = CRM_Utils_Array::implode('<br>', $errorMsg);
      }

      // save validation result
      if ($filter !== $self->_values['filter']) {
        $self->_values['filter'] = $filter;
        $self->saveValues();
      }
    }
    return $errors;
  }


  /**
   * This function sets the default values for the form. MobileProvider that in edit/view mode
   * the default values are retrieved from the database
   */
  function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }

  /**
   * Function to actually build the form
   */
  public function buildQuickForm() {
    $this->assign_by_ref('values', $this->_values);

    $this->assign('spf_status', $this->_spfStatus);
    $this->assign('dkim_status', $this->_dkimStatus);

    $spfRecord = CRM_Utils_Mail::getSPF($this->_values['email']);
    if (!empty($spfRecord)) {
      $record = array();
      foreach($spfRecord as $spf) {
        $record[] = $spf['host'].' '.$spf['type'].' '.$spf['txt'];
      }
      $this->assign('spf_record', CRM_Utils_Array::implode("\n", $record));
    }
    else {
      $this->assign('spf_record', ts('None'));
    }

    $dkimRecord= CRM_Utils_Mail::getDKIM($this->_values['email']);
    if (!empty($dkimRecord)) {
      $record = $dkimRecord[0]['host'].' '.$dkimRecord[0]['type'].' '.$dkimRecord[0]['target'];
      $this->assign('dkim_record', $record);
    }
    else {
      $this->assign('dkim_record', ts('None'));
    }

    if ($this->_spfStatus && $this->_dkimStatus) {
      $this->addButtons(array(
          array(
            'type' => 'back',
            'name' => ts('<< Previous'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'next',
            'name' => ts('Next >>'),
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      $this->addButton('refresh', ts('Refresh'));
      $this->addButtons(array(
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
  }

  /**
   * Function to process the form
   */
  public function postProcess() {
    $buttonName = $this->controller->getButtonName();
    // prevent prev button save values
    if ($buttonName == '_qf_DNSVerify_refresh' || $buttonName == '_qf_DNSVerify_next') {
      $this->_values['filter'] = $this->_values['filter'] | self::VALID_SPF | self::VALID_DKIM;
      $this->saveValues();
    }
  }
}