<?php

class CRM_Contribute_Form_TaiwanACH extends CRM_Core_Form {
  public $_context;
  public $_processors;
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;

  function preProcess() {
    $this->_contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $this->_context = CRM_Utils_Request::retrieve('context', 'String', $this);

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
      $this->_contributionRecurId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_TaiwanACH', $this->_id, 'contribution_recur_id');
    }
    $this->_processors = CRM_Core_PseudoConstant::paymentProcessor(False, False, 'payment_processor_type = "TaiwanACH"');
    if (empty($this->_processors)) {
      CRM_Core_Error::fatal("You need setup TaiwanACH Payment Processor first.");
    }

    $this->set('type', 'Contribution');
    CRM_Custom_Form_CustomData::preProcess($this);
    $this->addFormRule(['CRM_Contribute_Form_TaiwanACH', 'formRule'], $this);
  }

  function buildQuickForm() {
    if ($this->_action & CRM_Core_Action::ADD) {
      if (empty($this->_contactId)) {
        CRM_Contact_Form_NewContact::buildQuickForm($this);
      }
    }
    $pages = CRM_Contribute_PseudoConstant::contributionPage();
    foreach($pages as $pid => $page) {
      $pages[$pid] = $page." (ID:$pid)";
    }
    $this->addSelect('ach_contribution_page_id', ts('Contribution Page'), $pages, NULL, TRUE);
    $this->addMoney('ach_amount', ts('Total Amount'), TRUE);
    $this->addSelect('ach_payment_type', ts('ACH Type').' - '.ts('Payment Instrument'), [
      '' => ts('-- select --'),
      'ACH Bank' => ts('Bank'),
      'ACH Post' => ts('Post Office'),
    ], NULL, TRUE);

    $this->addSelect('ach_processor_id', ts('Payment Processor'), $this->_processors, NULL, TRUE);

    $bankCode = CRM_Contribute_PseudoConstant::taiwanACH();
    $this->addSelect('ach_bank_code', ts('Bank Identification Number'), ['' => ts('-- select --')] + $bankCode);

    $this->addSelect('ach_postoffice_acc_type', ts('Post Office Account Type'), [
      '' => ts('-- select --'),
      1 => ts('Bank book'),
      2 => ts('Postal transfer'),
    ]);

    $stampVerification = CRM_Contribute_PseudoConstant::taiwanACHStampVerification();
    if ($this->_action & CRM_Core_Action::ADD) {
      unset($stampVerification[2]);
    }
    $this->addSelect('ach_stamp_verification', ts('Stamp Verification Status'), $stampVerification);

    $this->add('text', 'ach_bank_branch', ts('Bank Branch'));
    $this->add('text', 'ach_bank_account', ts('ACH').' - '.ts('Account Number'), NULL, TRUE);
    $this->add('text', 'ach_identifier_number', ts('ACH').' - '.ts('Legal Identifier').'/'.ts('SIC Code'), NULL, TRUE);
    $this->addCheckbox('is_custom_order_number', '', [ts('Migrate from other ACH system?') => 1]);
    $this->add('text', 'ach_order_number', ts('ACH').' - '.ts('User Number'));

    CRM_Custom_Form_CustomData::buildQuickForm($this);

    $this->addButtons([
        ['type' => 'upload',
          'name' => ts('Save'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  public static function formRule($fields, $files, $self) {
    $errors = [];

    //check if contact is selected in standalone mode
    if (isset($fields['contact_select_id'][1]) && !$fields['contact_select_id'][1]) {
      $errors['contact[1]'] = ts('Please select a contact or create new contact');
    }

    if ($fields['ach_payment_type'] == 'ACH Bank' && empty($fields['ach_bank_code'])) {
      $errors['ach_bank_code'] = ts('%1 is a required field.', [1 => ts('Bank Code')]);
    }
    if ($fields['ach_payment_type'] == 'ACH Post' && empty($fields['ach_postoffice_acc_type'])) {
      $errors['ach_postoffice_acc_type'] = ts('%1 is a required field.', [1 => ts('Post Office Account Type')]);
    }

    if($fields['ach_identifier_number']) {
      $err = FALSE;
      if(strlen($fields['ach_identifier_number']) != 10 && strlen($fields['ach_identifier_number']) != 8) {
        $err = TRUE;
      }
      if (!preg_match('/[a-z]{0,2}[0-9]{8,9}/i', $fields['ach_identifier_number'])) {
        $err = TRUE;
      }
      if ($err) {
        $errors['ach_identifier_number'] = ts('%1 has error on format.', [1 => ts('ACH').' - '.ts('Legal Identifier')]);
      }
    }

    if(!empty($fields['is_custom_order_number']) && empty($fields['ach_order_number'])) {
      $errors['ach_order_number'] = ts('%1 is a required field.', [1 => ts('User Number')]);
    }

    return $errors;
  }

  function setDefaultValues() {
    $defaults = [];
    if ($this->_id && $this->_contributionRecurId) {
      $achValues = CRM_Contribute_BAO_TaiwanACH::getValue($this->_contributionRecurId);
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
      $defaults['currency'] = $achValues['currency'];

      return $defaults;
    }
    elseif($this->_contactId) {
      $legalIdentitifer = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $this->_contactId, 'legal_identifier');
      if (!empty($legalIdentitifer)) {
        $defaults['ach_identifier_number'] = $legalIdentitifer;
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

    $params = [];
    if ($this->_contributionRecurId) {
      $params['contribution_recur_id'] = $this->_contributionRecurId;
    }
    $params['contact_id'] = $this->_contactId;
    foreach($submittedValues as $key => $value) {
      if (in_array($key, ['hidden_custom', 'MAX_FILE_SIZE', 'qfKey', 'contact', 'contact_select_id', 'profiles'])) {
        continue;
      }
      if (strstr($key, 'custom_')) {
        $key = preg_replace('/^(custom_\d+)(.*)$/', '$1', $key);
        $params['data'][$key] = $value;
        continue;
      }
      if (strstr($key, 'ach_')) {
        $key = preg_replace('/^ach_/', '', $key);
      }
      $params[$key] = $value;
    }
    // recur_status_id default is 2 or recur value
    $params['contribution_status_id'] = 2;
    if (($this->_action & CRM_Core_Action::UPDATE) && !empty($this->_contributionRecurId)) {
      $params['contribution_status_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionRecur', $this->_contributionRecurId, 'contribution_status_id');
      $stampVerification = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_TaiwanACH', $this->_contributionRecurId, 'stamp_verification', 'contribution_recur_id');
    }
    // if stampVerification Change, check status_id and stamp verification.
    if ($stampVerification != $params['stamp_verification']) {
      if($params['contribution_status_id'] == 2) {
        if ($params['stamp_verification'] == 1) {
          $params['contribution_status_id'] = 5;
        }
      }
      else if ($params['stamp_verification'] == 0 || $params['stamp_verification'] == 2){
        $params['contribution_status_id'] = 2;
      }
    }
    $result = CRM_Contribute_BAO_TaiwanACH::add($params);
    if ($result->contribution_recur_id) {
      $session = CRM_Core_Session::singleton();
      if ($this->_context == 'contribution') {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid='.$result->contact_id.'&selectedChild=contribute'));
      }
      else {
        $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view/contributionrecur', 'reset=1&id='.$result->contribution_recur_id.'&cid='.$result->contact_id));
      }
    }
    else {
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contribute/taiwanach', 'reset=1'));
    }
  }
}
