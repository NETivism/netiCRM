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
    $modifyFieldsArray = [ts('Transaction Fee Amount')];
    $importDateCustomFieldId = $this->get('disbursementDate');
    if ($importDateCustomFieldId) {
      $importDataCustomFieldName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $importDateCustomFieldId, 'label');
      $modifyFieldsArray[] = ts("`%1` to the field `%2`", array(1 => ts('Disbursement Date'), 2 => $importDataCustomFieldName));
    }
    $modifyFields = implode(', ', $modifyFieldsArray);
    $this->assign('modifyFields', $modifyFields);
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    // Add all row to the tables
    $this->_successedContribution = $this->get('successedContribution');
    if (empty($this->_successedContribution)) {
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
                $row[ts('Error Message')] = ts("Contribution status is not 'Completed'.");
                $rowProcessType = 2;
              }
              if ($contribution->total_amount != $row['金額']) {
                $row[ts('Error Message')] = ts('Amount is not correct.');
                $rowProcessType = 3;
              }
              if ($importDateCustomFieldId) {
                if (!CRM_Utils_Type::validate($row['撥款日期'], 'Date', FALSE)) {
                  $row[ts('Error Message')] = ts('Disbursement date is not correct.');
                  $rowProcessType = 3;
                }
              }
            }
            else {
              $row[ts('Error Message')] = ts("Can't find the contribution in database.");
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
    }
    else {
      $this->_statusContent = $this->get('modifyStatusContribution');
      $this->_errorContent = $this->get('errorContribution');
      $header = $this->get('tableHeader');
    }


    if (!empty($this->_statusContent)) {
      $this->_statusHeader = $header;
      $this->_statusHeader[] = ts('Contribution Status');
      $this->set('modifyStatusHeader', $this->_statusHeader);
      $this->set('modifyStatusContribution', $this->_statusContent);
      $this->assign('modifyStatusHeader', $this->_statusHeader);
      $this->assign('modifyStatusContribution', $this->_statusContent);
      $this->assign('modifyStatusBlockHeaderText', ts("Contribution data with inconsistent 'status' (import after modifying status)"));
      $modifyStatusFieldsArray = [ts('Transaction Fee Amount')];
      $modifyStatusFieldsArray[] = ts("`%1` to the field `%2`", array(1 => ts('Disbursement Date'), 2 => ts('Empty Receive Date')));
      $modifyStatusFields = implode(', ', $modifyStatusFieldsArray);
      $this->assign('modifyStatusFields', $modifyStatusFields);
    }

    $this->_errorHeader = $header;
    $this->_errorHeader[] = ts('Error Message');

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
      CRM_Core_Error::statusBounce(ts('Error type is wrong.'));
    }

    $this->assign('tableContent', $tableContent);
    $this->set('tableHeader', $header);
    $this->assign('successedTableHeader', $header);
    $this->assign('successedContribution', $this->_successedContribution);
    $this->assign('successedHeaderText', ts("Contribution data that matches"));
    if (!empty($this->_errorContent)) {
      $this->assign('errorTableHeader', $this->_errorHeader);
      $this->assign('errorContribution', $this->_errorContent);
      $this->set('errorHeader', $this->_errorHeader);
      $this->set('errorContribution', $this->_errorContent);
      $this->assign('errorBlockHeaderText', ts("Erroneous contribution data (cannot be imported)"));
    }
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
    if (!empty($this->_successedContribution)) {
      foreach ($this->_successedContribution as &$contributionRow) {
        $this->processImportData($contributionRow);
      }
    }
    if (!empty($this->_statusContent)) {
      foreach ($this->_statusContent as &$contributionRow) {
        $this->processImportData($contributionRow, $isChangeStatus = TRUE);
      }
    }
    $successedHeader = $this->get('tableHeader');
    $successedHeader[] = ts('Result');
    $this->set('successedContribution', $this->_successedContribution);
    $this->set('successedTableHeader', $successedHeader);
    CRM_Core_Session::setStatus(ts('Import completed, below is the summary of the import results.'));
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

  /**
   * Process the contribution, which is array type.
   * 
   * @param Array $contributionRow
   */
  private function processImportData(&$contributionRow, $isChangeStatus = FALSE) {
    $contributionRow[ts('Result')] = "";
    $id = $contributionRow['id'];
    if (!empty($contributionRow['id']) && !empty($contributionRow['手續費'])) {
    $feeAmount = $contributionRow['手續費'];
      CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_Contribution', $id, 'fee_amount', $feeAmount);
      $contributionRow[ts('Result')] .= ts('Add fee to contribution.');
    }
    $importDateCustomFieldId = $this->get('disbursementDate');
    if (!empty($importDateCustomFieldId)) {
      $sql = "SELECT table_name, cg.id as custom_group_id, cf.label as field_label, column_name FROM civicrm_custom_group cg INNER JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id WHERE cf.id = %1";
      $params = array( 1 => array($importDateCustomFieldId, 'Positive'));
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $sql2 = "SELECT id FROM {$dao->table_name} WHERE entity_id = %1";
        $params2 = array( 1 => array($id, 'Positive'));
        $customValueID = CRM_Core_DAO::singleValueQuery($sql2, $params2);
        $tableName = $dao->table_name;
        $importDate = array(
          $tableName => array(
            0 => array(
              0 => array(
                'entity_id' => $id,
                'column_name' => $dao->column_name,
                'custom_group_id' => $dao->custom_group_id,
                'is_multiple' => FALSE,
                'type' => 'Date',
                'value' => $contributionRow['撥款日期'],
              )
            )
          )
        );
        if ($customValueID) {
          $importDate[$tableName][0][0]['id'] = $customValueID;
        }
        CRM_Core_BAO_CustomValueTable::create($importDate);
        $contributionRow[ts('Result')] .= ts("Add disbursement date to field `%1`", array(1 => $dao->field_label));
      }
    }
    if ($isChangeStatus) {
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contributionRow['id'];
      $contribution->find(TRUE);
      $isChanged = FALSE;
      if ($contribution->contribution_status_id != 1) {
        $contribution->contribution_status_id = 1;
        $isChanged = TRUE;
        $contributionRow[ts('Result')] .= ts("Modify contribution status to 'finished'.");
      }
      if (empty($contribution->receive_date)) {
        $contribution->receive_date = $contributionRow['撥款日期'];
        $isChanged = TRUE;
        $contributionRow[ts('Result')] .= ts("Update disbursement date data to receive date.");
      }
      if ($isChanged) {
        $contribution->save();
      }
    }
    self::addNote($contributionRow[ts('Result')], $contributionRow);
  }

  static private function addNote($note, &$contributionRow){
    require_once 'CRM/Core/BAO/Note.php';
    $note = date("Y/m/d H:i:s"). ts("Transaction record").": \n".$note."\n===============================\n";
    $note_exists = CRM_Core_BAO_Note::getNote( $contributionRow['id'], 'civicrm_contribution' );
    if(count($note_exists)){
      $note_id = array( 'id' => reset(array_keys($note_exists)) );
      $note = $note . reset($note_exists);
    }
    else{
      $note_id = NULL;
    }
    $noteParams = array(
      'entity_table'  => 'civicrm_contribution',
      'note'          => $note,
      'entity_id'     => $contributionRow['id'],
      'contact_id'    => $contributionRow['contact_id'],
      'modified_date' => date('Ymd')
    );
    CRM_Core_BAO_Note::add( $noteParams, $note_id );
  }
}
