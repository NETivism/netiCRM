<?php

class CRM_Contribute_Form_NewebpayImport_Preview extends CRM_Core_Form {
  protected $_result = NULL;

  protected $_successedContribution = [];

  protected $_statusHeader = [];

  protected $_statusContent = [];

  protected $_errorHeader = [];

  protected $_errorContent = [];

  protected $_statusFileName = 'NewebpayImportPreviewStatus.xlsx';

  protected $_errorFileName = 'NewebpayImportPreviewError.xlsx';

  function preProcess() {
    $downloadErrorType = CRM_Utils_Request::retrieve( 'downloadType', 'String', CRM_Core_DAO::$_nullObject);

    $this->_result = $this->get('parseResult');
    $this->_successedContribution = array();
    $importDateCustomFieldId = $this->get('disbursementDate');
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    // Add all row to the tables
    foreach ($this->_result['content'] as $rowNum => &$row) {
      if ($rowNum == 0) {
        $header = $row;
      }
      else {
        if (!empty($row['商店訂單編號'])) {
          $trxn_id = $row['trxn_id'] = $row['商店訂單編號'];
          $contribution = new CRM_Contribute_DAO_Contribution();
          $contribution->trxn_id = $trxn_id;
          $rowProcessType = 0;
          // 1 => successed, 2 => contribution_status is wrong, 3 => error.
          if ($contribution->find(TRUE)) {
            $row['contact_id'] = $contribution->contact_id;
            $row['id'] = $contribution->id;
            if ($contribution->contribution_status_id == 1) {
              $rowProcessType = 1;
            }
            else {
              $row['error_message'] = 'Contribution status is not "successed".';
              $rowProcessType = 2;
            }
            if ($contribution->total_amount != $row['金額']) {
              $row['error_message'] = 'Amount is not correct.';
              $rowProcessType = 3;
            }
            if ($importDateCustomFieldId) {
              if (!CRM_Utils_Type::validate($row['撥款日期'], 'Date', FALSE)) {
                $row['error_message'] = '撥款日期欄位不正確';
                $rowProcessType = 3;
              }
            }
          }
          else {
            $row['error_message'] = 'Can\'t find the contribution in CRM.';
            $rowProcessType = 3;
          }
          if ($rowProcessType == 1) {
            $this->_successedContribution[] = $row;
          }
          elseif ($rowProcessType == 2) {
            $row[ts('Contribution Status')] = $contributionStatus[$contribution->contribution_status_id];
            $this->_statusContent[] = $row;
          }
          else {
            $this->_errorContent[] = $row;
          }
          $tableContent[] = $row;
        }
      }
    }

    if (!empty($this->_statusContent)) {
      $this->_statusHeader = $header;
      $this->_statusHeader[] = ts('Contribution Status');
      $this->assign('modifyStatusHeader', $this->_statusHeader);
      $this->assign('modifyStatusContribution', $this->_statusContent);
    }

    $this->_errorHeader = $header;
    $this->_errorHeader[] = 'error_message';

    if ($downloadErrorType) {
      if ($downloadErrorType == 'error') {
        foreach ($this->_errorContent as &$row) {
          foreach ($row as $key => $value) {
            if (!in_array($key, $this->_errorHeader)) {
              unset($row[$key]);
            }
          }
        }
        CRM_Core_Report_Excel::writeExcelFile($this->_errorFileName, $this->_errorHeader, $this->_errorContent, $download = TRUE);
      }
      if ($downloadErrorType == 'status') {
        foreach ($this->_statusContent as &$row) {
          foreach ($row as $key => $value) {
            if (!in_array($key, $this->_statusHeader)) {
              unset($row[$key]);
            }
          }
        }
        CRM_Core_Report_Excel::writeExcelFile($this->_statusFileName, $this->_statusHeader, $this->_statusContent, $download = TRUE);
      }
    }

    $this->assign('tableContent', $tableContent);
    $this->set('tableHeader', $header);
    $this->assign('successedTableHeader', $header);
    $this->assign('successedContribution', $this->_successedContribution);
    $this->assign('errorTableHeader', $this->_errorHeader);
    $this->assign('errorContribution', $this->_errorContent);
    $this->set('errorContribution', $this->_errorContent);
    $this->addFormRule(array('CRM_Contribute_Form_NewebpayImport_Preview', 'formRule'), $this);

    $query = "_qf_Preview_display=true&qfKey={$this->controller->_key}&downloadType=";
    $queryError = $query.'error';
    $downloadErrorUrl = CRM_Utils_System::url('civicrm/contribute/newebpay/import', $queryError);
    $this->assign('downloadErrorUrl', $downloadErrorUrl);

    $queryStatus = $query.'status';
    $downloadStatusUrl = CRM_Utils_System::url('civicrm/contribute/newebpay/import', $queryStatus);
    $this->assign('downloadStatusUrl', $downloadStatusUrl);
  }

  function buildQuickForm() {
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
