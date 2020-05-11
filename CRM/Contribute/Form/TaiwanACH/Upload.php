<?php

class CRM_Contribute_Form_TaiwanACH_Upload extends CRM_Core_Form {
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_TaiwanACH_Upload', 'formRule'), $this);
  }

  function buildQuickForm() {
    $this->add('file', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=60', TRUE);

    $this->addButtons(array(
        array('type' => 'upload',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public static function formRule($fields, $files, $self) {
    $errors = array();
    if (empty($files)) {
      $errors['uploadFile'] = ts('Missing required field: %1', array(1 => ts('Import Data File')));
    }

    if ($files['uploadFile']['type'] !== 'text/plain') {
      $errors['uploadFile'] = ts('File format must be one of these: %1', array(1 => 'txt'));
    }

    return $errors;
  }

  function setDefaultValues() {
    $defaults = array();
    return $defaults;
  }


  function postProcess() {
    $this->set('parseResult', NULL);
    $submittedValues = $this->controller->exportValues($this->_name);
    if ($submittedValues['uploadFile']['name']) {
      $content = file_get_contents($submittedValues['uploadFile']['name']);
      $result = CRM_Contribute_BAO_TaiwanACH::parseUpload($content);
      $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $contributionType = CRM_Contribute_PseudoConstant::contributionType();
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $stampStatus = CRM_Contribute_PseudoConstant::taiwanACHStampVerification();
      $counter = array();
      foreach($result['processed_data'] as &$line) {
        if (!empty($line['payment_instrument_id'])) {
          $line['payment_instrument'] = $paymentInstrument[$line['payment_instrument_id']];
        }
        if (!empty($line['contribution_type_id'])) {
          $line['contribution_type'] = $contributionType[$line['contribution_type_id']];
        }
        if (!empty($line['contribution_status_id'])) {
          $line['contribution_status'] = $contributionStatus[$line['contribution_status_id']];
        }
        if (isset($line['stamp_verification'])) {
          $line['stamp_verification_status'] = $stampStatus[$line['stamp_verification']];
        }
        switch($line['contribution_status_id']) {
          case 1:
            $counter[ts('Complete Donation')]++;
            break;
          case 2:
          default:
            $counter[ts('Failure Count')]++;
            break;
        }
      }
      $result['counter'] = $counter;
      if ($result['import_type'] == 'transaction') {
        $result['columns'] = array(
          'id' => ts('Contribution ID'),
          'trxn_id' => ts('Transaction ID'),
          'invoice_id' => ts('Invoice ID'),
          'payment_instrument' => ts('Payment Instrument'),
          'total_amount' => ts('Amount'),
          'contribution_type' => ts('Type'),
          'source' => ts('Source'),
          'created_date' => ts('Created Date'),
          'receive_date' => ts('Received'),
          'contribution_status' => ts('Status'),
        );
      }
      else {
        $result['columns'] = array(
          'id' => ts('Recurring Contribution ID'),
          'invoice_id' => ts('Invoice ID'),
          'payment_type' => ts('Payment Instrument'),
          'create_date' => ts('Created Date'),
          'start_date' => ts('Start Date'),
          'contribution_status' => ts('Recurring Status'),
          'amount' => ts('Amount'),
          'stamp_verification_status' => ts('Stamp Verification Status'),
          'verification_failed_date' => ts('Stamp Verification').' - '.ts('Failed Date'),
          'verification_failed_reason' => ts('Stamp Verification').' - '.ts('Failed Reason'),
        );
      }
      $this->set('parseResult', $result);
    }
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Upload Data');
  }
}
