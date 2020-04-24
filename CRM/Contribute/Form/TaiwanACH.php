<?php

class CRM_Contribute_Form_TaiwanACH extends CRM_Core_Form {

  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_recurringId = NULL;
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
    if ($this->_id) {
      $this->_recurringId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_TaiwanACH', $this->_id, 'contribution_recur_id');
    }
    $this->_processors = CRM_Core_PseudoConstant::paymentProcessor(False, False, 'payment_processor_type = "TaiwanACH"');
    if (empty($this->_processors)) {
      CRM_Core_Error::fatal("You need setup TaiwanACH Payment Processor first.");
    }

    $this->set('type', 'Contribution');
    CRM_Custom_Form_CustomData::preProcess($this);
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH', 'formRule'), $this);
  }

  function buildQuickForm() {
    if (empty($this->_contactId) && $this->_action & CRM_Core_Action::ADD) {
      CRM_Contact_Form_NewContact::buildQuickForm($this);
    }
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    foreach($pages as $pid => $page) {
      $pages[$pid] = $page." (ID:$pid)";
    }
    $this->addSelect('ach_contribution_page_id', ts('Contribution Page'), $pages, NULL, TRUE);
    $this->addMoney('ach_amount', ts('Total Amount'), TRUE);
    $this->addSelect('ach_payment_type', ts('ACH').' - '.ts('Payment Instrument'), array(
      '' => ts('-- select --'),
      'bank' => ts('Bank'),
      'postoffice' => ts('Post Office'),
    ), NULL, TRUE);

    $this->addSelect('ach_processor_id', ts('Payment Processor'), $this->_processors, NULL, TRUE);

    $bankCode = CRM_Contribute_PseudoConstant::taiwanACH();
    $this->addSelect('ach_bank_code', ts('Bank Identification Number'), array('' => ts('-- select --')) + $bankCode);

    $this->addSelect('ach_postoffice_acc_type', ts('Post Office Account Type'), array(
      '' => ts('-- select --'),
      1 => ts('bank book'),
      2 => ts('postal transfer'),
    ));

    $this->addSelect('ach_stamp_verification', ts('Stamp Verification Status'), CRM_Contribute_PseudoConstant::taiwanACHStampVerification());

    $this->add('text', 'ach_bank_branch', ts('Bank Branch'));
    $this->add('text', 'ach_bank_account', ts('ACH').' - '.ts('Bank Account Number'), NULL, TRUE);
    $this->add('text', 'ach_identifier_number', ts('ACH').' - '.ts('Legal Identifier').'/'.ts('SIC Code'), NULL, TRUE);

    CRM_Custom_Form_CustomData::buildQuickForm($this);

    $this->addButtons(array(
        array('type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
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

  function setDefaultValues() {
    $defaults = array();
    if ($this->_id && $this->_recurringId) {
      $achValues = CRM_Contribute_BAO_TaiwanACH::getValue($this->_recurringId);
      foreach($achValues as $idx => $val) {
        if ($idx == 'data') {
          foreach($val as $fld => $valCustom) {
            if (strstr($fld, 'custom_')) {
              $defaults[$fld.'_-'] = $valCustom;
            }
          }
        }
        else {
          $defaults['ach_'.$idx] = $val;
        }
      }
    }
    return $defaults;
  }

  function postProcess() {
    $submittedValues = $this->controller->exportValues($this->_name);

    // set the contact, when contact is selected
    if (CRM_Utils_Array::value('contact_select_id', $submittedValues)) {
      $this->_contactId = $submittedValues['contact_select_id'][1];
    }

    $params = array();
    if ($this->_id) {
      $params['id'] = $this->_id;
    }
    $params['contact_id'] = $this->_contactId;
    foreach($submittedValues as $key => $value) {
      if (in_array($key, array('hidden_custom', 'MAX_FILE_SIZE', 'qfKey', 'contact', 'contact_select_id', 'profiles'))) {
        continue;
      }
      if (strstr($key, 'custom_')) {
        $key = preg_replace('/^(custom_\d+)(.*)$/', '$1', $key);
        $params['data'][$key] = $value;
        continue;
      }
      if (strstr($key, 'ach')) {
        $key = preg_replace('/^ach_/', '', $key);
      }
      $params[$key] = $value;
    }
    if ($this->_action) {
      $params['contribution_status_id'] = 2;
    }
    $result = CRM_Contribute_BAO_TaiwanACH::add($params);
    if ($result->contribution_recur_id) {
      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contributionrecur', 'reset=1&id='.$result->contribution_recur_id.'&cid='.$result->contact_id));
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/taiwanach', 'reset=1'));
    }
  }
}
