<?php
class CRM_Contact_Form_Task_TaiwanACHExportVerification extends CRM_Contact_Form_Task_TaiwanACHExport {

  function preProcess() {
    parent::preProcess();
    $this->_exportParams = array();
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
        $msgs[] = ts('All selected recurrings must be pending. There are %1 recurrings not pending.', array(1 => count($notPending)));
      }
      if (!empty($notUnverified)) {
        $msgs[] = ts('All selected recurrings must be unverified. There are %1 recurrings not unverified.', array(1 => count($notUnverified)));
      }
      $msg = CRM_Utils_Array::implode('<br/>', $msgs);
       return CRM_Core_Error::statusBounce($msg);
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
    $this->addDate('datetime', ts('Verification Date'), TRUE, array('formatType' => 'searchDate'));
    $this->addYesNo('is_overwrite', ts('overwrite').'?');
  }

  function setDefaultValues() {
    $defaults = array(
      'datetime' => date('Y-m-d'),
    );
    return $defaults;
  }

  public static function formRule($fields, $files, $self) {
    return parent::formRule($fields, $files, $self);
  }

  public function validate() {
    $pass = TRUE;
    $values = $this->exportValues();
    if (!$values['is_overwrite'] && $values['export_format'] == 'txt') {
      $dates = date("Ymd", strtotime($values['datetime']));
      if ($values['payment_type'] == CRM_Contribute_BAO_TaiwanACH::BANK) {
        $entity_table = CRM_Contribute_BAO_TaiwanACH::BANK_VERIFY_ENTITY;
      }
      else {
        $entity_table = CRM_Contribute_BAO_TaiwanACH::POST_VERIFY_ENTITY;
      }
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
    $date = date('Ymd', strtotime($values['datetime']));
    if ($values['is_overwrite']) {
      $sql = "UPDATE civicrm_contribution_recur SET invoice_id = NULL WHERE invoice_id LIKE %1";
      $params = array( 1 => array("{$date}_%", 'String'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
    }
    $this->_exportParams['file_name'] = str_replace(" ", "_", $values['payment_type']).'_Verification_'.$date;
    $this->_exportParams['date'] = $date;
    CRM_Contribute_BAO_TaiwanACH::doExportVerification($this->_additionalIds, $this->_exportParams, $values['payment_type'], $values['export_format']);
  }
}