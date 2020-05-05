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
      // $result = NULL;
      // $result = array(
      //   'process_id' => '123456789', // export batch id, should be unique every generate
      //   'import_type' => 'transaction', // transaction or validation
      //   'payment_type' => 'ACH Bank', // ACH Bank or ACH Post
      //   'lines' => array(           // each line is a recurring (when verification) or a contriubtion (when transaction)
      //     '123' => array(
      //       'id' => 123,
      //       'trxn_id' => '123',
      //       'receipt_id' => 'abc-123',
      //       'payment_instrument_id' => 1,
      //       'contribution_type_id' => 1,
      //       'source' => 'ach generate ...',
      //       'created_date' => '2020-01-22 10:01:55',
      //       'receive_date' => '2020-01-22 10:02:03',
      //       'contribution_status_id' => 2,
      //       'total_amount' => 101.00,
      //       'currency' => 'TWD',
      //     ),
      //   ),
      // );
      $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $contributionType = CRM_Contribute_PseudoConstant::contributionType();
      $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
      $counter = array();
      foreach($result['lines'] as &$line) {
        if (!empty($line['payment_instrument_id'])) {
          $line['payment_instrument'] = $paymentInstrument[$line['payment_instrument_id']];
        }
        if (!empty($line['contribution_type_id'])) {
          $line['contribution_type'] = $contributionType[$line['contribution_type_id']];
        }
        if (!empty($line['contribution_status_id'])) {
          $line['contribution_status'] = $contributionStatus[$line['contribution_status_id']];
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
      $this->set('parseResult', $result);
      // should return parsed result
      // eg. payment_type, validation file or transaction file
      // eg. lines of transaction and validation
      // eg. if it's transaction response, need to bring which contribution was successful payment from civicrm_log

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
