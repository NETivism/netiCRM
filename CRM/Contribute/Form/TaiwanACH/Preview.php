<?php

class CRM_Contribute_Form_TaiwanACH_Preview extends CRM_Core_Form {
  public $_processResult;
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;
  protected $_parseResult = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH_Preview', 'formRule'), $this);
    $this->_parseResult = $this->get('parseResult');

    // refs #33861, check parse result process_id
    // we need process_id to know which batch we want to process
    if ($this->_parseResult['payment_type'] === CRM_Contribute_BAO_TaiwanACH::BANK && $this->_parseResult['import_type'] === 'transaction') {
      $log = new CRM_Core_DAO_Log();
      $log->entity_id = !empty($this->get('customProcessId')) ? (int) $this->get('customProcessId') : (int) $this->_parseResult['process_id'];
      $log->entity_table = CRM_Contribute_BAO_TaiwanACH::TRANS_ENTITY;
      if ($log->find()) {
        $this->_parseResult['process_id'] = $log->entity_id;
        $result = CRM_Contribute_Form_TaiwanACH_Upload::parseUpload($this->_parseResult['original_file'], $log->entity_id);
        $result['original_file'] = $this->_parseResult['original_file'];
        $this->_parseResult = $result;
        $this->set('parseResult', $result);
      }
      else {
        $this->_parseResult['process_id'] = NULL;
      }
    }
    $this->assign('parseResult', $this->_parseResult);
    $this->assign('importType', $this->_parseResult['import_type']);
  }

  function buildQuickForm() {
    $result = $this->_parseResult;
    if ($result['import_type'] == 'transaction') {
      if (is_null($result['process_id']) || !empty($this->get('customProcessId'))) {
        $tYear = date('Y') - 1911;
        $tYear = sprintf('%04d', $tYear);
        $this->add('text', 'custom_process_id', ts('ACH Transaction File ID'), array('class' => 'huge', 'placeholder' => 'BOFACHP01'.$tYear.date('md').'xxxxxx'), TRUE);
      }
      $dateLabel = ts('Receive Date');
    }
    else {
      $dateLabel = ts('Start Date');
    }

    $this->addDateTime('receive_date', $dateLabel, False, array('formatType' => 'activityDateTime'));

    if (!empty($this->_parseResult)) {
      if (is_null($result['process_id'])) {
        $this->addButtons(array(
            array('type' => 'refresh',
              'name' => ts('Refresh'),
              'isDefault' => TRUE,
            ),
            array('type' => 'cancel',
              'name' => ts('Cancel'),
            ),
          )
        );
      }
      else {
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

    // refs #33861, parse custom_process_id
    if (empty($self->_parseResult['process_id']) && !empty($fields['custom_process_id'])) {
      $processId = NULL;
      if (preg_match('/^[0-9]{6}$/', $fields['custom_process_id'])) {
        $processId = (int) $fields['custom_process_id'];
      }
      elseif (preg_match('/^BOF.{6}\d{8}(\d{6})/', $fields['custom_process_id'], $matches)) {
        $processId = (int) $matches[1];
      }
      if ($processId || $processId === 0) {
        $log = new CRM_Core_DAO_Log();
        $log->entity_id = $processId;
        $log->entity_table = CRM_Contribute_BAO_TaiwanACH::TRANS_ENTITY;
        if (!$log->find()) {
          $errors['custom_process_id'] = ts('Could not find your ACH transaction file ID.');
        }
        else {
          $self->set('customProcessId', $processId);
        }
      }
      else {
        $errors['custom_process_id'] = ts("Format is not correct. Input format is '%1'", array(1 => 'BOFACHP01'.$tYear.date('md').'xxxxxx123123'));
      }
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
    // do not submit when button state is refresh
    $buttonPressed = $this->controller->getButtonName();
    if ($buttonPressed == '_qf_Preview_refresh') {
      return;
    }

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
      $counter[ts('Completed')] = 0;
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
        if ($line['contribution_status_id'] == 1 && empty($line['cancel_reason']) && $line['executed'] == TRUE) {
          $counter[ts('Completed')]++;
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
