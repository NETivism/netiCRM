<?php
/**
 * This
 * contacts.
 */
class CRM_Contact_Form_Task_AnnualReceipt extends CRM_Contact_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */

  protected $_tmpreceipt = NULL;

  protected $_year = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    $session = CRM_Core_Session::singleton();

    // this session comes from custom search
    if(!empty($session->get('year', 'AnnualReceipt'))){
      $this->_year = $session->get('year', 'AnnualReceipt');
    }
    
    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Print Annual Receipt'));
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
    if(!empty($this->_year)){
      $years[$this->_year] = $this->_year;
      $ele = $this->addElement('select', 'year', ts('Receipt Date'), $years);
    }
    else{
      for($year = date('Y'); $year < date('Y') + 4; $year++) {
        $years[$year - 3] = $year - 3;
      }
      $this->addElement('select', 'year', ts('Receipt Date'), $years);
    }
    $this->addButtons(array(
        array(
          'type' => 'next',
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
      $session = CRM_Core_Session::singleton();
      $session->resetScope('AnnualReceipt');
      $this->_year = $params['year'];
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
    $pages = $template->fetch('CRM/common/AnnualReceipt.tpl');
    $filename = 'AnnualReceipt'.$this->_year.'.pdf';
    $pdf = CRM_Utils_PDF_Utils::domlib($pages, $filename, $output, 'portrait', 'a4');
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
      $html .= CRM_Contribute_BAO_Contribution::getAnnualReceipt($contact_id, $year, $template);
      self::pushFile($html);

      // reset template values before processing next transactions
      $template->clearTemplateVars();
      $count++;
      unset($html);
    }
  }
}

