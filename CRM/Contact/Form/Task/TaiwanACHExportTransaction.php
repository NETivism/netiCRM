<?php

class CRM_Contact_Form_Task_TaiwanACHExportTransaction extends CRM_Contact_Form_Task_TaiwanACHExport {
  public $_exportParams;
  public $_additionalIds;
  /**
   * Build all the data structures needed to build the form.
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
    $this->_exportParams = [];
    CRM_Utils_System::setTitle(ts("Export ACH Transaction File"));
    $isError = FALSE;
    $unverified = [];
    $notInProcess = [];
    $msgs = [];
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
        $msgs[] = ts('All selected recurrings must be in process. There are %1 recurrings not in process.', [1 => count($notInProcess)]);
      }
      if (!empty($unverified)) {
        $msgs[] = ts('All selected recurrings must be verified. There are %1 recurrings yet verified.', [1 => count($unverified)]);
      }
      $msg = CRM_Utils_Array::implode('<br/>', $msgs);
      return CRM_Core_Error::statusBounce($msg);
    }
  }

  /**
   * Build the form object.
   *
   * @return void
   */
  public function buildQuickForm() {
    parent::buildQuickForm();
    $this->addDate('transact_date', ts('Process Date'), TRUE, ['formatType' => 'searchDate']);
  }

  /**
   * Set the default values for the form.
   *
   * @return array<string, string>
   */
  public function setDefaultValues() {
    $defaults = [
      'transact_date' => date('Y-m-d', strtotime('+1 day')),
    ];
    return $defaults;
  }

  /**
   * Form rule.
   *
   * @param array $fields
   * @param array $files
   * @param CRM_Core_Form $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    return parent::formRule($fields, $files, $self);
  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   */
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
