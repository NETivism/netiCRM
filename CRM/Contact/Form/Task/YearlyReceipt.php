<?php
/**
 * This
 * contacts.
 */
class CRM_Contact_Form_Task_YearlyReceipt extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */
  public $_single = NULL;

  public $_cid = NULL;

  protected $_tmpreceipt = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    // retrieve contact ID if this is 'single' mode
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);

    $this->_activityId = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if ($cid) {
      CRM_Contact_Form_Task_PDFLetterCommon::preProcessSingle($this, $cid);
      $this->_single = TRUE;
      $this->_cid = $cid;
    }
    else {
      parent::preProcess();
    }
    $this->assign('single', $this->_single);
    
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Print Annual Receipt'));
  }

  static function preProcessSingle(&$form, $cid) {
    $form->_contactIds = array($cid);
    CRM_Contact_Page_View::setTitle($cid);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    $years = array();
    $session = CRM_Core_Session::singleton();
    if(!empty($session->get('year', 'YearlyReceipt'))){
      $year = $session->get('year', 'YearlyReceipt');
      $years[$year] = $year;
      $ele = $this->addElement('select', 'year', ts('Receipt Date'), $years);
      $ele->freeze();
    }
    else{
      for($year = date('Y'); $year < date('Y') + 4; $year++) {
        $years[$year - 3] = $year - 3;
      }
      $this->addElement('select', 'year', ts('Receipt Date'), $years);
    }

    $this->addButtons(array(
        array('type' => 'next',
          'name' => ts('Download Receipt(s)'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    if(!empty($params['year'])){
      self::makeReceipt($this->_contactIds, $params['year']);
      self::makePDF();
    }
    CRM_Utils_System::civiExit();
  }

  public function pushFile($html) {
    // tmp directory
    file_put_contents($this->_tmpreceipt, $html, FILE_APPEND);
  }
  public function popFile() {
    $return = file_get_contents($this->_tmpreceipt);
    unlink($this->_tmpreceipt);
    return $return;
  }

  public function makePDF($output = FALSE) {
    $template = &CRM_Core_Smarty::singleton();
    $pages = self::popFile();
    $template->assign('pages', $pages);
    $pages = $template->fetch('CRM/common/YearlyReceipt.tpl');
    $pdf = CRM_Utils_PDF_Utils::domlib($pages, 'ReceiptYear.pdf', $output, 'portrait', 'a4');
    if ($output) {
      print $pdf;
    }
  }

  public function makeReceipt($contactIds, $year) {
    $this->_tmpreceipt = tempnam('/tmp', 'receiptyear');
    $count = 0;

    foreach ($contactIds as $contact_id){
      $template = &CRM_Core_Smarty::singleton();
      if ($count) {
        $html = '<div class="page-break" style="page-break-after: always;"></div>';
      }
      $html .= CRM_Contribute_BAO_Contribution::getReceiptYearly($contact_id, $year, $template);
      self::pushFile($html);

      // reset template values before processing next transactions
      $template->clearTemplateVars();
      $count++;
      unset($html);
    }
  }
}

