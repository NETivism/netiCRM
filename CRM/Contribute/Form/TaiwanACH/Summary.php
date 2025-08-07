<?php

class CRM_Contribute_Form_TaiwanACH_Summary extends CRM_Core_Form {
  public $_parseResult;
  public $_processResult;
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;

  function preProcess() {
    $this->_parseResult = $this->get('parseResult');
    $this->_processResult = $this->get('processResult');
    $this->assign('processResult', $this->_processResult);
    $this->assign('parseResult', $this->_parseResult);
    $this->assign('importType', $this->_parseResult['import_type']);
  }

  function buildQuickForm() {
    $this->addButtons([
        ['type' => 'cancel',
          'name' => ts('Done'),
        ],
      ]
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
