<?php

class CRM_Contribute_Form_NewebpayImport_Summary extends CRM_Core_Form {

  protected $_successedContribution;

  function preProcess() {
    $successedTableHeader = $this->get('successedTableHeader');
    $this->_successedContribution = $this->get('successedContribution');
    $this->assign('successedTableHeader', $successedTableHeader);
    $this->assign('successedContribution', $this->_successedContribution);
    $this->assign('successedHeaderText', ts('Success Contribution'));

    $statusContent = $this->get('modifyStatusContribution');
    if (!empty($statusContent)) {
      $statusHeader = $this->get('modifyStatusHeader');
      $this->assign('modifyStatusHeader', $statusHeader);
      $this->assign('modifyStatusContribution', $statusContent);
      $this->assign('modifyStatusBlockHeaderText', ts('Pending Contribution'));
    }

    $errorContent = $this->get('errorContribution');
    if (!empty($errorContent)) {
      $errorHeader = $this->get('errorHeader');
      $this->assign('errorTableHeader', $errorHeader);
      $this->assign('errorContribution', $errorContent);
      $this->assign('errorBlockHeaderText', ts('Error Contribution'));
    }
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
