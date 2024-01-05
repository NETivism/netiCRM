<?php

class CRM_Contribute_Form_NewebpayImport_Summary extends CRM_Core_Form {

  protected $_successedContribution;

  function preProcess() {
    $successedTableHeader = $this->get('successedTableHeader');
    $this->_successedContribution = $this->get('successedContribution');
    $this->assign('successedTableHeader', $successedTableHeader);
    $this->assign('successedContribution', $this->_successedContribution);
    $this->assign('successedHeaderText', ts("Contribution data that matches"));

    $statusContent = $this->get('modifyStatusContribution');
    if (!empty($statusContent)) {
      $statusHeader = $this->get('modifyStatusHeader');
      foreach ($statusContent as &$row) {
        $row[ts('Contribution Status')] = ts('Completed');
      }
      $this->assign('modifyStatusHeader', $statusHeader);
      $this->assign('modifyStatusContribution', $statusContent);
      $this->assign('modifyStatusBlockHeaderText', ts("Contribution data with inconsistent 'status' (import after modifying status)"));
    }

    $errorContent = $this->get('errorContribution');
    if (!empty($errorContent)) {
      $errorHeader = $this->get('errorHeader');
      $this->assign('errorTableHeader', $errorHeader);
      $this->assign('errorContribution', $errorContent);
      $this->assign('errorBlockHeaderText', ts("Erroneous contribution data (cannot be imported)"));
    }

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
        array('type' => 'cancel',
          'name' => ts('Done'),
        ),
      )
    );
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
    return ts('Summary');
  }
}
