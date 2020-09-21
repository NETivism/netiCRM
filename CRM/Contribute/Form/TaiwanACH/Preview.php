<?php

class CRM_Contribute_Form_TaiwanACH_Preview extends CRM_Core_Form {
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;
  protected $_parseResult = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH_Preview', 'formRule'), $this);
    $this->_parseResult = $this->get('parseResult');
    if (!empty($this->_parseResult) && !empty($this->_parseResult['process_id'])) {
      $this->assign('parseResult', $this->_parseResult);
    }
  }

  function buildQuickForm() {

    $result = $this->get('parseResult');
    if ($result['import_type'] == 'transaction') {
      $dateLabel = ts('Receive Date');
    }
    else {
      $dateLabel = ts('Start Date');
    }

    $this->addDateTime('receive_date', $dateLabel, False, array('formatType' => 'activityDateTime'));

    if (!empty($this->_parseResult)) {
      $this->addButtons(array(
          array('type' => 'back',
            'name' => ts('<< Previous'),
          ),
          array('type' => 'upload',
            'name' => ts('Import Now >>'),
            'isDefault' => TRUE,
          ),
          array('type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      CRM_Core_Session::setStatus(ts('Invalid file being import, abort.'), FALSE, 'error');
      $this->addButtons(array(
          array('type' => 'back',
            'name' => ts('<< Previous'),
          ),
          array('type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();
    if (empty($self->_parseResult)) {
      $errors['qfKey'] = ts('Invalid file being import, abort.');
    }
    return $errors;
  }

  function setDefaultValues() {
    $defaults = array(
      'receive_date' => date('Y-m-d'),
      'receive_date_time' => date('H:i:s'),
    );
    return $defaults;
  }


  function postProcess() {
    // send parseResult into BAO
    // Considering type is Bank or Post in process function
    $counter = array();
    $receiveDate = $this->exportValue('receive_date').' '.$this->exportValue('receive_date_time');
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $contributionType = CRM_Contribute_PseudoConstant::contributionType();
    $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
    $stampStatus = CRM_Contribute_PseudoConstant::taiwanACHStampVerification();
    if ($this->_parseResult['import_type'] == 'verification') {
      foreach ($this->_parseResult['processed_data'] as $id => $ignore) {
        $this->_parseResult['parsed_data'][$id]['process_date'] = $receiveDate;
        $line = CRM_Contribute_BAO_TaiwanACH::doProcessVerification($id, $this->_parseResult['parsed_data'][$id], FALSE);
        if (!empty($line['payment_instrument_id'])) {
          $line['payment_instrument'] = $paymentInstrument[$line['payment_instrument_id']];
        }
        if (isset($line['stamp_verification'])) {
          $line['stamp_verification_status'] = $stampStatus[$line['stamp_verification']];
        }
        if (!empty($line['contribution_status_id'])) {
          $line['contribution_status'] = $contributionStatus[$line['contribution_status_id']];
        }
        if ($line['contribution_status_id'] == 5) {
          $counter[ts('Completed Donation')]++;
        }
        $this->_processResult[$id] = $line;
      }
    }
    elseif ($this->_parseResult['import_type'] == 'transaction') {
      foreach ($this->_parseResult['processed_data'] as $id => $ignore) {
        $this->_parseResult['parsed_data'][$id]['process_date'] = $receiveDate;
        $line = CRM_Contribute_BAO_TaiwanACH::doProcessTransaction($id, $this->_parseResult['parsed_data'][$id], FALSE);
        if (!empty($line['payment_instrument_id'])) {
          $line['payment_instrument'] = $paymentInstrument[$line['payment_instrument_id']];
        }
        if (!empty($line['contribution_type_id'])) {
          $line['contribution_type'] = $contributionType[$line['contribution_type_id']];
        }
        if (!empty($line['contribution_status_id'])) {
          $line['contribution_status'] = $contributionStatus[$line['contribution_status_id']];
        }
        if ($line['contribution_status_id'] == 1 && empty($line['cancel_reason'])) {
          $counter[ts('Completed Donation')]++;
        }
        $this->_processResult[$id] = $line;
      }
    }
    $this->_parseResult['counter'] = $counter;
    $this->set('parseResult', $this->_parseResult);
    $this->set('processResult', $this->_processResult);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Preview');
  }
}
