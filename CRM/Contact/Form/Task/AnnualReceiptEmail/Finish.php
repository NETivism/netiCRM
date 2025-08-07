<?php
/**
 * This
 * contacts.
 */
class CRM_Contact_Form_Task_AnnualReceiptEmail_Finish extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */

  CONST GENERATE_COUNT_EACH_TIME = 100;
  CONST BATCH_THRESHOLD = 100;

  static protected $_tmpreceipt = NULL;
  static protected $_exportFileName = NULL;

  protected $_year = NULL;

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle() {
    return ts('Done');
  }

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('Send Annual Receipt Email'));
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    $this->addButtons([
        [
          'type' => 'done',
          'name' => ts('Done'),
          'isDefault' => TRUE,
        ],
      ]
    );
  }

  function setDefaultValues() {
    $defaults = [];
    return $defaults;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
  }
}

