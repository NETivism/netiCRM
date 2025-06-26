<?php

class CRM_Contribute_Form_NewebpayImport_Upload extends CRM_Core_Form {

  function preProcess() {
    if (strstr(CRM_Utils_System::currentPath(), '/newebpay/')) {
      CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contribute/fee/import', 'reset=1'));
    }
    $this->addFormRule(['CRM_Contribute_Form_NewebpayImport_Upload', 'formRule'], $this);
  }

  function buildQuickForm() {
    $this->add('file', 'uploadFile', ts('Import Data File'), 'size=30 maxlength=60', TRUE);

    $this->addRule('uploadFile', ts('Input file must be in CSV format'), 'utf8File');

    $customFields = [];
    $customFields[0] = ts('-- Select --');
    $sql = "SELECT cf.id, cf.label FROM civicrm_custom_field cf LEFT JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id WHERE data_type = 'Date' AND cg.extends = 'Contribution'";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $customFields[$dao->id] = $dao->label;
    }
    if (count($customFields) > 1) {

      $this->addSelect('disbursementDate', ts('Disbursement Date'), $customFields, []);
      // $this->add('Select', 'accounting_date', ts('Accounting date'), array(), )
    }

    $this->addButtons([
        ['type' => 'upload',
          'name' => ts('Continue'),
          'isDefault' => TRUE,
        ],
        ['type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]
    );
  }

  public static function formRule($fields, $files, $self) {
    $errors = [];
    if (empty($files)) {
      $errors['uploadFile'] = ts('Missing required field: %1', [1 => ts('Import Data File')]);
    }

    $typePass = FALSE;
    switch ($files['uploadFile']['type']) {
      case 'text/csv':
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
        $typePass = TRUE;
        break;
      default:
        break;
    }
    if (!$typePass) {
      $errors['uploadFile'] = ts('File format must be one of these: %1', [1 => 'csv, xlsx']);
    }

    return $errors;
  }

  function setDefaultValues() {
    $defaults = [];
    return $defaults;
  }


  function postProcess() {
    $this->set('parseResult', NULL);
    $submittedValues = $this->controller->exportValues($this->_name);
    if ($submittedValues['uploadFile']['name']) {
      $result['content'] = self::parseUpload($submittedValues['uploadFile']);
      $result['original_file'] = $submittedValues['uploadFile']['name'];
      $this->set('parseResult', $result);
    }
    if ($submittedValues['disbursementDate']) {
      $this->set('disbursementDate', $submittedValues['disbursementDate']);
    }
  }

  public static function parseUpload($file) {
    if (file_exists($file['name'])) {
      if ($file['type'] == 'text/csv') {
        $filePath = $file['name'];
        $fd = fopen($filePath, 'r');
        $config = CRM_Core_Config::singleton();
        $rowsFromSheet = [];
        $i = 1;
        while($row = fgetcsv($fd, 0, $config->fieldSeparator)) {
          $rowsFromSheet[$i] = $row;
          $i++;
        }
      }
      if ($file['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
        $rowsFromSheet = CRM_Core_Report_Excel::readExcelFile($file['name']);
      }
      $rows = [];
      foreach ($rowsFromSheet as $rowNum => $row) {
        if ($rowNum == 1) {
          $header = $row;
          $rows[] = $header;
        }
        else {
          $oneRow = [];
          foreach ($row as $columnNum => $value) {
            $oneRow[$header[$columnNum]] = $value;
          }
          $rows[] = $oneRow;
        }
      }
      return $rows;
    }
    else {
      return [];
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
