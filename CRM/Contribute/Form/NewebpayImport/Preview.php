<?php

class CRM_Contribute_Form_NewebpayImport_Preview extends CRM_Core_Form {
  protected $_result = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_NewebpayImport_Preview', 'formRule'), $this);
  }

  function buildQuickForm() {
    $this->_result = $this->get('parseResult');
    $successedContribution = $errorContribution = array();
    foreach ($this->_result['content'] as $rowNum => &$row) {
      if ($rowNum == 0) {
        $header = $row;
      }
      else {
        if (!empty($row['商店訂單編號'])) {
          $trxn_id = $row['trxn_id'] = $row['商店訂單編號'];
          $contribution = new CRM_Contribute_DAO_Contribution();
          $contribution->trxn_id = $trxn_id;
          if ($contribution->find(TRUE)) {
            $row['contact_id'] = $contribution->contact_id;
            if ($contribution->contribution_status_id == 1) {
              $successedContribution[] = $row;
            }
            else {
              $row['error_message'] = 'Contribution status is not "successed".';
              $errorContribution[] = $row;
            }
            if ($contribution->total_amount != $row['金額']) {
              $row['error_message'] = 'Amount is not correct.';
              $errorContribution[] = $row;
            }
          }
          else {
            $row['error_message'] = 'Can\'t find the contribution in CRM.';
            $errorContribution[] = $row;
          }
          $tableContent[] = $row;
        }
      }
    }

    $this->assign('tableHeader', $header);
    $this->assign('tableContent', $tableContent);
    $this->assign('successedContribution', $successedContribution);
    $this->set('successedContribution', $successedContribution);
    $this->assign('errorContribution', $errorContribution);
    $this->set('errorContribution', $errorContribution);

    $this->addButtons(array(
        array('type' => 'upload',
          'name' => ts('Import'),
          'isDefault' => TRUE,
        ),
        array('type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
  }

  public static function formRule($fields, $files, $self) {
    return $errors;
  }

  function setDefaultValues() {
    $defaults = array(
    );
    return $defaults;
  }


  function postProcess() {
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
