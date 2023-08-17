<?php

class CRM_Contribute_Form_NewebpayImport_Preview extends CRM_Core_Form {
  protected $_result = NULL;

  protected $_successedContribution = [];

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_NewebpayImport_Preview', 'formRule'), $this);
  }

  function buildQuickForm() {
    $this->_result = $this->get('parseResult');
    $this->_successedContribution = $errorContribution = array();
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
            $row['id'] = $contribution->id;
            if ($contribution->contribution_status_id == 1) {
              $this->_successedContribution[] = $row;
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
    $this->set('tableHeader', $header);
    $this->assign('successedContribution', $this->_successedContribution);
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
    $errors = [];
    return $errors;
  }

  function setDefaultValues() {
    $defaults = array(
    );
    return $defaults;
  }


  function postProcess() {
    $importDateCustomFieldId = $this->get('disbursementDate');
    if (!empty($this->_successedContribution)) {
      foreach ($this->_successedContribution as &$contributionRow) {
        $contributionRow[ts('Result')] = "";
        if (!empty($contributionRow['id']) && !empty($contributionRow['手續費'])) {
          $id = $contributionRow['id'];
          $feeAmount = $contributionRow['手續費'];
          CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $id, 'fee_amount', $feeAmount);
          $contributionRow[ts('Result')] .= 'Add fee to contribution.';
        }
        if (!empty($importDateCustomFieldId)) {
          $fieldName = 'custom_'.$importDateCustomFieldId;
          $importDate = array(
            $fieldName => $contributionRow['撥款日期'],
            'entityID' => $id,
          );
          CRM_Core_BAO_CustomValueTable::setValues($importDate);
          $contributionRow[ts('Result')] .= "Add date to `{$fieldName}`";
        }
      }
    }
    $successedHeader = $this->get('tableHeader');
    $successedHeader[] = ts('Result');
    $this->set('successedContribution', $this->_successedContribution);
    $this->set('successedTableHeader', $successedHeader);
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
