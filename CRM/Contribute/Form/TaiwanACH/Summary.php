<?php

/**
 * Form for displaying the summary of Taiwan ACH import results.
 */
class CRM_Contribute_Form_TaiwanACH_Summary extends CRM_Core_Form {
  public $_parseResult;
  public $_processResult;
  protected $_contactId = NULL;
  protected $_id = NULL;
  protected $_contributionRecurId = NULL;
  protected $_action = NULL;

  /**
   * Set up variables before the form is built.
   *
   * Retrieves the final parse and process results from the session and
   * assigns them to the template for display.
   *
   * @return void
   */
  public function preProcess() {
    $this->_parseResult = $this->get('parseResult');
    $this->_processResult = $this->get('processResult');
    $this->assign('processResult', $this->_processResult);
    $this->assign('parseResult', $this->_parseResult);
    $this->assign('importType', $this->_parseResult['import_type']);
  }

  /**
   * Actually build the form components.
   *
   * Adds a 'Done' button to complete the import workflow.
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons(
      [
        ['type' => 'cancel',
          'name' => ts('Done'),
        ],
      ]
    );
  }

  /**
   * Process the form submission.
   *
   * This method is a placeholder as no additional logic is required after
   * the summary display.
   *
   * @return void
   */
  public function postProcess() {
  }

  /**
   * Return a descriptive name for the page, used in wizard header.
   *
   * @return string the descriptive page title
   */
  public function getTitle() {
    return ts('Summary');
  }
}
