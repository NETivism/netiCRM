<?php

class CRM_Contact_Form_Task_TaiwanACHExportTransaction extends CRM_Contact_Form_Task_TaiwanACHExport {
  function preProcess() {
    parent::preProcess();
    $this->_exportParams = array();
    CRM_Utils_System::setTitle(ts("Export ACH Transaction File"));
    $isError = FALSE;
    $unverified = array();
    $notInProcess = array();
    $msgs = array();
    foreach ($this->_achDatas as $id => $achData) {
      if ($achData['contribution_status_id'] != 5) {
        $notInProcess[] = $achData['id'];
        $isError = TRUE;
      }
      if ($achData['stamp_verification'] != 1) {
        $unverified[] = $achData['id'];
        $isError = TRUE;
      }
    }
    if (!empty($isError)) {
      if (!empty($notInProcess)) {
        $msgs[] = ts('All selected recurrings must be in process. There are %1 recurrings not in process.', array(1 => count($notInProcess)));
      }
      if (!empty($unverified)) {
        $msgs[] = ts('All selected recurrings must be verified. There are %1 recurrings yet verified.', array(1 => count($unverified)));
      }
      $msg = CRM_Utils_Array::implode('<br/>', $msgs);
       return CRM_Core_Error::statusBounce($msg);
    }
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addDate('transact_date', ts('Process Date'), TRUE, array('formatType' => 'searchDate'));
  }

  function setDefaultValues() {
    $defaults = array(
      'transact_date' => date('Y-m-d', strtotime('+1 day')),
    );
    return $defaults;
  }

  public static function formRule($fields, $files, $self) {
    return parent::formRule($fields, $files, $self);
  }
  public function postProcess() {
    // $this->_contactIds  <== contact id
    // $this->_additionalIds <== recurring id
    parent::postProcess();
    $values = $this->exportValues();
    $date = date('Ymd', strtotime($values['transact_date']));
    $this->_exportParams['file_name'] = str_replace(" ", "_", $values['payment_type']).'_Transaction_'.$date;
    $this->_exportParams['transact_date'] = $date;
    CRM_Contribute_BAO_TaiwanACH::doExportTransaction($this->_additionalIds, $this->_exportParams, $values['payment_type'], $values['export_format']);
  }
}