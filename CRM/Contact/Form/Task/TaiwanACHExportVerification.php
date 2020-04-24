<?php
class CRM_Contact_Form_Task_TaiwanACHExportVerification extends CRM_Contact_Form_Task_TaiwanACHExport {
  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts("Export ACH Verification File"));
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
  }

  function setDefaultValues($form) {
    return parent::setDefaultValues($form);
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
    CRM_Contribute_BAO_TaiwanACH::doExportVerification($this->_additionalIds, $this->_exportParams, $values['export_format']);
  }
}