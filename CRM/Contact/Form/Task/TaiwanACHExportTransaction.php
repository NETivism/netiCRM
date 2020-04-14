<?php

class CRM_Contact_Form_Task_TaiwanACHExportTransaction extends CRM_Contact_Form_Task {
  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts("Export ACH Transaction File"));
    // get selector defined form values
    $formValues = $this->get('formValues');
    $this->_hasProblem = FALSE;
    if (empty($formValues['payment_type'])) {
      $this->_hasProblem = TRUE;
      CRM_Core_Session::setStatus(ts('You need to search payment type of ACH then you can export data.'));
    }
    elseif (empty(count($this->_additionalIds))) {
      $this->_hasProblem = TRUE;
      CRM_Core_Session::setStatus(ts('Sorry. No results found.'));
    }
    else {
      CRM_Core_Session::setStatus(ts('Number of selected contributions: %1', array(1 => count($this->_additionalIds))));
    }
  }

  public function buildQuickForm() {
    if (!$this->_hasProblem) {
      $formValues = $this->get('formValues');
      $options = array(
        'bank' => ts('Bank'),
        'postoffice' => ts('Post Office'),
      );
      $ele = $this->addSelect('payment_type', ts('Payment Instrument'), $options, NULL, TRUE);
      $this->setDefaults(array('payment_type' => $formValues['payment_type']));
      $ele->freeze();

      $options = array(
        'txt' => 'txt'.' - '.ts('Format that submit to bank.'),
        'xlst' => 'xlst'.' - '.ts('Format that human can read.'),
      );
      $this->addSelect('export_format', ts("Format"), $options, NULL, TRUE);
      $this->addDefaultButtons(ts('Export'));
      
      // add rules
      $this->addFormRule(array('CRM_Contact_Form_Task_TaiwanACHExportTransaction', 'formRule'), $this);
    }
    else {
      $buttons = array();
      $buttons[] = array(
        'type' => 'back',
        'name' => ts('Previous'),
      );
      $this->addButtons($buttons);
    }
  }

  public function formRule($fields, $files, $self) {
    $errors = array();
    if (!empty($fields['payment_type'])) {
      $paymentType = $fields['payment_type'];
      $dao = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_contribution_taiwanach WHERE payment_type = %1 AND contribution_recur_id IN (%2)", array(
        1 => array($paymentType, 'String'),
        2 => array(implode(",", $self->_additionalIds), 'String'),
      ));
      $dao->fetch();
      if ($dao->N != count($self->_additionalIds)) {
        $diff = count($self->_additionalIds) - $dao->N;
        $errors['payment_type'] = ts("%1 ACH records you selected didn't match the payemnt type you choose.", array(
          1 => $diff
        ));
      }
    }
    return $errors;
  }
  public function postProcess() {
    // $this->_contactIds  <== contact id
    // $this->_additionalIds <== recurring id
  }
}