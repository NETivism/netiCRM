<?php

class CRM_Contact_Form_Task_TaiwanACHExportTransaction extends CRM_Contact_Form_Task_TaiwanACHExport {
  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts("Export ACH Transaction File"));
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
  }

  public function formRule($fields, $files, $self) {
    return parent::formRule($fields, $files, $self);
  }
  public function postProcess() {
    // $this->_contactIds  <== contact id
    // $this->_additionalIds <== recurring id
    parent::postProcess();
    $values = $this->exportValues();
    if ($this->_exportParams) {
      $this->_exportParams['file_name'] = 'ACHVerification'.$values['datetime'].'_'.$values['datetime_time'];
    }
    CRM_Contribute_BAO_TaiwanACH::doExportTransaction($this->_additionalIds, $this->_exportParams, $values['payment_type'], $values['export_format']);
  }
}