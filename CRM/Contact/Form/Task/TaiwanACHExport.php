<?php
class CRM_Contact_Form_Task_TaiwanACHExport extends CRM_Contact_Form_Task {
  function preProcess() {
    parent::preProcess();
    // get selector defined form values
    $this->_formValues = $this->get('formValues');
    $this->_hasProblem = FALSE;
    if (empty(count($this->_additionalIds))) {
      $this->_hasProblem = TRUE;
      $messages[] = ts('Sorry. No results found.');
    }
    else {
      $messages[] = ts('Number of selected contributions: %1', array(1 => count($this->_additionalIds)));
    }
    // Check is same processor id
    $achDatas = CRM_Contribute_BAO_TaiwanACH::getTaiwanACHDatas($this->_additionalIds);
    foreach ($achDatas as $achData) {
      if (empty($achData['processor_id'])) {
        $this->_hasProblem = TRUE;
        $messages[] = ts('The ACH you selected has no payment processor setting.');
      }
      if (empty($processor_id)) {
        $processor_id = $achData['processor_id'];
      }
      else if ($processor_id != $achData['processor_id']) {
        $this->_hasProblem = TRUE;
        $messages[] = ts('All ACH you selected needs same payment processor setting.');
        break;
      }
    }

    if ($this->_hasProblem) {
      $message = implode('<br>', $messages);
      CRM_Core_Error::statusBounce($message);
    }
    else if(!empty($messages)) {
      foreach ($messages as $message) {
        CRM_Core_Session::setStatus($message);
      }
    }
  }

  public function buildQuickForm() {
    if (!$this->_hasProblem) {
      $options = array(
        '' => ts('-- select --'),
        'bank' => ts('Bank'),
        'postoffice' => ts('Post Office'),
      );
      $this->addSelect('payment_type', ts('Payment Instrument'), $options, NULL, TRUE);

      $this->addDateTime('datetime', ts('Output Time'), TRUE, array('formatType' => 'searchDate'));

      $options = array(
        'txt' => 'txt'.' - '.ts('Format that submit to bank.'),
        'xlsx' => 'xlsx'.' - '.ts('Format that human can read.'),
      );
      $this->addSelect('export_format', ts("Format"), $options, NULL, TRUE);
      $this->addDefaultButtons(ts('Export'));

      // add rules
      $this->addFormRule(array('CRM_Contact_Form_Task_TaiwanACHExportVerification', 'formRule'), $this);
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

  function setDefaultValues() {
    $defaults = array(
      'datetime' => date('Y-m-d'),
      'datetime_time' => date('H:i:s'),
    );
    if (!empty($this->_formValues['payment_type'])) {
      $defaults['payment_type'] = $this->_formValues['payment_type'];
      $paymentTypeEle = $this->getElement('payment_type');
      $paymentTypeEle->freeze();
    }
    return $defaults;
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
    $values = $this->exportValues();
    $this->_exportParams = array(
      'date' => date('Ymd', strtotime($values['datetime'])),
      'time' => date('His', strtotime($values['datetime_time'])),
    );
  }
}