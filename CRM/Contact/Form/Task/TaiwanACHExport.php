<?php
class CRM_Contact_Form_Task_TaiwanACHExport extends CRM_Contact_Form_Task {

  protected $_achDatas = array();

  protected $_paymentType = NULL;

  protected $_hasProblem = NULL;

  protected $_formValues = array();

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
    $this->_achDatas = CRM_Contribute_BAO_TaiwanACH::getTaiwanACHDatas($this->_additionalIds);
    $achDatas = &$this->_achDatas;
    $unverificationIds = array();
    foreach ($this->_additionalIds as $key => $recurringId) {
      $achData = $achDatas[$recurringId];
      // Check processor_id
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

      // Check payment type
      if (empty($achData['payment_type'])) {
        $this->_hasProblem = TRUE;
        $messages[] = ts('The ACH you selected has no payment type.');
      }
      if (empty($paymentType)) {
        $paymentType = $achData['payment_type'];
      }
      else if ($paymentType != $achData['payment_type']) {
        $this->_hasProblem = TRUE;
        $messages[] = ts('All ACH you selected needs same payment type.');
        break;
      }
    }

    $this->_paymentType = $achData['payment_type'];

    $messages = array_unique($messages);
    if ($this->_hasProblem) {
      $message = CRM_Utils_Array::implode('<br>', $messages);
       return CRM_Core_Error::statusBounce($message);
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
        'ACH Bank' => ts('Bank'),
        'ACH Post' => ts('Post Office'),
      );
      $ele = $this->addSelect('payment_type', ts('Payment Instrument'), $options, NULL, TRUE);
      if (!empty($this->_paymentType)) {
        $this->setDefaults(array('payment_type' => $this->_paymentType));
        $ele->freeze();
      }

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

  public function formRule($fields, $files, $self) {
    $errors = array();
    if (!empty($fields['payment_type'])) {
      $paymentType = $fields['payment_type'];
      $ids = CRM_Utils_Array::implode(",", $self->_additionalIds);
      $dao = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_contribution_taiwanach WHERE payment_type = %1 AND contribution_recur_id IN ($ids)", array(
        1 => array($paymentType, 'String'),
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