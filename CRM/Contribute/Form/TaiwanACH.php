<?php

class CRM_Contribute_Form_TaiwanACH extends CRM_Core_Form {

  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_action = NULL;

  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if ($this->_contactId) {
      $this->assign('contact_id', $this->_contactId);
      list($displayName) = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactId);
      $this->assign('displayName', $displayName);
    }
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'add');
    $this->assign('action', $this->_action);

    if ($this->_action & CRM_Core_Action::ADD) {
      $this->_id = NULL;
    }

    //CRM_Custom_Form_Customdata::preProcess($this);
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH', 'formRule'), $this);
  }

  function buildQuickForm() {
    if (empty($this->_contactId) && $this->_action & CRM_Core_Action::ADD) {
      CRM_Contact_Form_NewContact::buildQuickForm($this);
    }
    $this->addSelect('ach_contribution_page_id', ts('Contribution Page'), CRM_Contribute_PseudoConstant::contributionPage(), NULL, TRUE);
    $this->addMoney('ach_total_amount', ts('Total Amount'), TRUE);
    $this->addSelect('ach_payment_type', ts('ACH').' - '.ts('Payment Instrument'), array(
      '' => ts('-- select --'),
      'bank' => ts('Bank'),
      'postoffice' => ts('Post Office'),
    ), NULL, TRUE);

    $bankCode = array(
      '000808' => 'E.Sun Bank',
      '000700' => 'Chunghwa Post',
    );
    $this->addSelect('ach_bank_code', ts('Bank Identification Number'), array('' => ts('-- select --')) + $bankCode);

    $this->addSelect('ach_postoffice_acc_type', ts('Post Office Account Type'), array(
      '' => ts('-- select --'),
      1 => ts('bank book'),
      2 => ts('postal transfer'),
    ));

    $this->add('text', 'ach_bank_branch', ts('Bank Branch'), NULL, TRUE);
    $this->add('text', 'ach_bank_account', ts('ACH').' - '.ts('Bank Account Number'), NULL, TRUE);
    $this->add('text', 'ach_identifier_number', ts('ACH').' - '.ts('Legal Identifier').'/'.ts('SIC Code'), NULL, TRUE);

    //CRM_Custom_Form_Customdata::buildQuickForm($this);
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();

    //check if contact is selected in standalone mode
    if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
      $errors['contact[1]'] = ts('Please select a contact or create new contact');
    }

    if ($fields['ach_payment_type'] == 'bank' && empty($fields['ach_bank_code'])) {
      $errors['ach_bank_code'] = ts('%1 is a required field.', array(1 => ts('Bank Code')));
    }
    if ($fields['ach_payment_type'] == 'postoffice' && empty($fields['ach_postoffice_acc_type'])) {
      $errors['ach_postoffice_acc_type'] = ts('%1 is a required field.', array(1 => ts('Post Office Account Type')));
    }

    return $errors;
  }
  function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    // set the contact, when contact is selected
    if (CRM_Utils_Array::value('contact_select_id', $submittedValues)) {
      $this->_contactId = $submittedValues['contact_select_id'][1];
    }
    
  }
}
