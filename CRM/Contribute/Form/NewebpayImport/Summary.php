<?php

class CRM_Contribute_Form_NewebpayImport_Summary extends CRM_Core_Form {

  protected $_successedContribution;

  function preProcess() {
    $successedTableHeader = $this->get('successedTableHeader');
    $this->_successedContribution = $this->get('successedContribution');
    $this->assign('successedTableHeader', $successedTableHeader);
    $this->assign('successedContribution', $this->_successedContribution);
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
