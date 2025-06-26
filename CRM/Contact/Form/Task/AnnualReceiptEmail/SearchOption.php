<?php
/**
 * This
 * contacts.
 */
class CRM_Contact_Form_Task_AnnualReceiptEmail_SearchOption extends CRM_Contact_Form_Task {

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
    return ts('Search Settings');
  }

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    CRM_Utils_System::setTitle(ts('Send Annual Receipt Email'));
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);
    if ($cid) {
      $this->_contactIds = [$cid];
    }
    else {
      parent::preProcess();
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    if (count($this->_contactIds) > self::BATCH_THRESHOLD) {
      $msg = ts('You have selected more than %1 contacts.', [1 => self::BATCH_THRESHOLD]).' ';
      $msg .= ts('Because of the large amount of data you are about to perform, we will schedule this job for the batch process after you submit. You will receive an email notification when the work is completed.');
      CRM_Core_Session::setStatus($msg);
    }

    $years = [];
    if(!empty($this->_year)){
      $years[$this->_year] = $this->_year;
      $ele = $this->addElement('select', 'year', ts('Receipt Year'), $years);
    }
    else{
      for($year = date('Y'); $year < date('Y') + 10; $year++) {
        $years[$year - 9] = $year - 9;
      }
      $this->addElement('select', 'year', ts('Receipt Year'), $years);
    }

    $contributionTypes = CRM_Contribute_PseudoConstant::contributionType(NULL, 'is_deductible', TRUE);
    $deductible = [ 0 => '- '.ts('All').' '.ts('Deductible').' -'];
    $contributionTypes = $deductible + $contributionTypes;
    $attrs = ['multiple' => 'multiple'];
    $this->addElement('select', 'contribution_type_id', ts('Contribution Types'), $contributionTypes, $attrs);

    $isRecur = [
      '' => '- '.ts('All').' -' ,
      -1 => ts('Non-Recurring Contribution'),
      1 => ts('Recurring Contribution'),
    ];
    $this->addElement('select', 'is_recur', ts('Find Recurring Contributions?'), $isRecur);

    $this->addButtons([
        [
          'type' => 'back',
          'name' => ts('<< Go Back'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'next',
          'name' => ts('Continue >>'),
          'isDefault' => TRUE,
        ],
      ]
    );
  }

  function setDefaultValues() {
    $defaults = [];
    $defaults['year'] = date('m') == '12' ? date('Y') : date('Y') - 1;
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
    $searchOption = $this->controller->exportValues($this->_name);
    $this->set('searchOption', $searchOption);
  }

}

