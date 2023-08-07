<?php

class CRM_Contribute_Form_NewebpayImport_Preview extends CRM_Core_Form {
  protected $_result = NULL;

  function preProcess() {
    $this->addFormRule(array('CRM_Contribute_Form_NewebpayImport_Preview', 'formRule'), $this);
  }

  function buildQuickForm() {
    $this->_result = $this->get('parseResult');
    
    // $tableHeader = reset($this->_parseResult['content']);
    foreach ($this->_result['content'] as $rowNum => &$row) {
      if ($rowNum == 0) {
        $header = $row;
      }
      else {
        if (!empty($row['商店訂單編號'])) {
          $row['id'] = $row['商店訂單編號'];
          $row['contact_id'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $row['商店訂單編號'], 'contact_id');
          $tableContent[] = $row;
        }
      }
    }

    $this->assign('tableHeader', $header);
    $this->assign('tableContent', $tableContent);

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
