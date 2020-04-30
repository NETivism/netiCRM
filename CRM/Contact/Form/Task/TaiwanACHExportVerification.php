<?php
class CRM_Contact_Form_Task_TaiwanACHExportVerification extends CRM_Contact_Form_Task_TaiwanACHExport {

  function preProcess() {
    parent::preProcess();
    CRM_Utils_System::setTitle(ts("Export ACH Verification File"));
    $isError = FALSE;
    $notUnverified = array();
    $notPending = array();
    $msgs = array();

    foreach ($this->_achDatas as $id => $achData) {
      if ($achData['contribution_status_id'] != 2) {
        $notPending[] = $achData['id'];
        $isError = TRUE;
      }
      if ($achData['stamp_verification'] != 0) {
        $notUnverified[] = $achData['id'];
        $isError = TRUE;
      }
    }
    if (!empty($isError)) {
      if (!empty($notPending)) {
        $msgs[] = ts('All selected recurrings need pending. There are %1 recurrings not pending.', array(1 => count($notPending)));
      }
      if (!empty($notUnverified)) {
        $msgs[] = ts('All selected recurrings need unverified. There are %1 recurrings not unverified.', array(1 => count($notUnverified)));
      }
      $msg = implode('<br/>', $msgs);
      CRM_Core_Error::statusBounce($msg);
    }

    if (!empty($this->_achDatas)) {
      $countHaveInvoiceId = '';
      foreach ($this->_achDatas as $recurId => $achData) {
        if (!empty($achData['invoice_id'])) {
          $countHaveInvoiceId++;
        }
      }
      if (!empty($countHaveInvoiceId)) {
        CRM_Core_Session::setStatus(ts("There are %1 recurrings have invoice ID, This behavior will overwrite them.", array(1 => $countHaveInvoiceId)));
      }
    }
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addYesNo('is_overwrite', ts('overwrite').'?');
  }

  function setDefaultValues() {
    return parent::setDefaultValues();
  }

  public function formRule($fields, $files, $self) {
    return parent::formRule($fields, $files, $self);
  }

  public function validate() {
    $pass = TRUE;
    $values = $this->exportValues();
    if (!$values['is_overwrite']) {
      $dates = date("Ymd", strtotime($values['datetime']));
      $entity_table = 'civicrm_contribution_taiwanach_verification';
      $lastInvoiceId = CRM_Core_DAO::singleValueQuery("SELECT entity_id FROM civicrm_log WHERE entity_table = '$entity_table' AND entity_id = %1", array(1 => array($dates, 'String')));
      if (!empty($lastInvoiceId)) {
        $session = CRM_Core_Session::singleton();
        $msg = ts("There already have file exported in the same day, if you want to rewrite it, please check 'rewrite' then submit.");
        $session->setStatus($msg, TRUE, 'error');
        $this->assign('is_need_confirm', TRUE);
        $pass = FALSE;
      }
    }
    return $pass;
  }

  public function postProcess() {
    // $this->_contactIds  <== contact id
    // $this->_additionalIds <== recurring id
    parent::postProcess();
    $values = $this->exportValues();
    if ($this->_exportParams) {
      $this->_exportParams['file_name'] = 'ACHVerification'.$values['datetime'].'_'.$values['datetime_time'];
    }
    CRM_Contribute_BAO_TaiwanACH::doExportVerification($this->_additionalIds, $this->_exportParams, $values['payment_type'], $values['export_format']);
  }
}