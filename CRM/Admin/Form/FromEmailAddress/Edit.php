<?php

class CRM_Admin_Form_FromEmailAddress_Edit extends CRM_Admin_Form_FromEmailAddress {
  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle() {
    return ts('Configure');
  }

  /**
   * Preprocess Form
   *
   * @return void
   */
  function preProcess() {
    parent::preProcess();
    if ($this->_defaultFrom === trim($this->_values['email'])) {
      $this->controller->set('skipEmailVerify', TRUE);
      $this->set('skipEmailVerify', TRUE);
    }
    $this->addFormRule(array('CRM_Admin_Form_FromEmailAddress_Edit', 'formRule'), $this);
  }

  /**
   * Rules called by addFormRule above
   *
   * @param array $fields the input form values
   * @param array $files  the uploaded files if any
   * @param array $self   current form object.
   *
   * @return array array of errors / empty array.
   */
  static function formRule($fields, $files, $self) {
    $errors = array();
    if (!empty($fields['from'])) {
      if (preg_match('/["<>]/', $fields['from'])) {
        $errors['from'] = ts('Email from name cannot have special character [<, >, "].');
      }
    }
    if (!empty($fields['email'])) {
      if (!CRM_Utils_Rule::email($fields['email'])) {
        $errors['email'] = ts('Email is not valid.');
      }
      if (!CRM_Utils_Mail::checkMailProviders($fields['email'])) {
        $errors['email'] = ts('Do not use free mail address as mail sender. (eg. %1)', array(1 => str_replace('|', ', ', CRM_Utils_Mail::DMARC_MAIL_PROVIDERS)));
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
    if (!empty($this->_id) && !empty($this->_values)) {
      $defaults['from'] = $this->_values['from'];
      $defaults['email'] = $this->_values['email'];
      $defaults['description'] = $this->_values['description'];
    }
    return $defaults;
  }

  /**
   * Function to actually build the form
   */
  public function buildQuickForm() {
    $this->addTextfield('from', ts('From Name'), NULL, TRUE);

    $emailEle = $this->addTextfield('email', ts('From Email Address'), array('class' => 'huge'), TRUE);
    if ((trim($this->_values['email']) === $this->_defaultFrom) || ($this->_values['filter'] & self::VALID_EMAIL)) {
      $this->assign('email_status', TRUE);
      $emailEle->freeze();
    }
    else {
      $this->assign('default_from_value', $this->_defaultFrom);
      $this->assign('default_from_target', 'email');
    }
    $this->assign('mail_providers', str_replace('|', ', ', CRM_Utils_Mail::DMARC_MAIL_PROVIDERS));

    $this->addTextarea('description', ts('Description'));

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  /**
   * Function to process the form
   */
  public function postProcess() {
    $this->_values['from'] = $this->exportValue('from');
    $this->_values['email'] = $this->exportValue('email');
    if ($this->get('skipEmailVerify')) {
      $this->_values['filter'] = $this->_values['filter'] | self::VALID_EMAIL;
    }
    $this->saveValues();

    if (!$this->_values['filter'] & self::VALID_EMAIL && !$this->get('skipEmailVerify')) {
      CRM_Admin_Form_FromEmailAddress::sendValidationEmail($this->_values['email'], $this->_id);
    }
  }
}



