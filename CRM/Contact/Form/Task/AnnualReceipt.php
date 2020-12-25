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

  CONST GENERATE_COUNT_EACH_TIME = 100;
  CONST BATCH_THRESHOLD = 100;

  static protected $_tmpreceipt = NULL;
  static protected $_exportFileName = NULL;

  protected $_year = NULL;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);
    if ($cid) {
      $this->_contactIds = array($cid);
    }
    else {
      parent::preProcess();
      $session = CRM_Core_Session::singleton();
      $year = $session->get('year', 'AnnualReceipt');
      if(!empty($year)){
        $this->_year = $year;
      }
    }

    // this session comes from custom search
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
    // make receipt target popup new tab
    $this->updateAttributes(array('target' => '_blank'));

    $years = array();
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

    $contribution_type = CRM_Contribute_PseudoConstant::contributionType(NULL, 'is_deductible', TRUE);
    $deductible = array( 0 => '- '.ts('All').' '.ts('Deductible').' -');
    $contribution_type = $deductible + $contribution_type;
    $attrs = array('multiple' => 'multiple');
    $this->addElement('select', 'contribution_type_id', ts('Contribution Type'), $contribution_type, $attrs);

    $contribution_type = CRM_Contribute_PseudoConstant::contributionType();
    $is_recur = array(
      '' => '- '.ts('All').' -' ,
      -1 => ts('Non-Recurring Contribution'),
      1 => ts('Recurring Contribution'),
    );
    $this->addElement('select', 'is_recur', ts('Find Recurring Contributions?'), $is_recur);

    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Download Receipt(s)'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  function setDefaultValues() {
    $defaults = array();
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
    $params = $this->controller->exportValues($this->_name);
    set_time_limit(1800);
    if(!empty($params['year'])){
      $session = CRM_Core_Session::singleton();
      $session->resetScope('AnnualReceipt');
      $this->_year = $params['year'];

      $this->option = array();
      foreach($params as $k => $p){
        if($k != 'qfKey' && !empty($p)){
          $this->option[$k] = $p;
        }
      }
      CRM_Utils_Hook::postProcess(get_class($this), $this);
      $this->makeReceipt($this->_contactIds, $this->option);
      $this->makePDF();
    }
    CRM_Utils_System::civiExit();
  }

  public static function pushFile($html) {
    // tmp directory
    file_put_contents(self::$_tmpreceipt, $html, FILE_APPEND);
  }
  public static function popFile() {
    $return = file_get_contents(self::$_tmpreceipt);
    unlink(self::$_tmpreceipt);
    return $return;
  }

  public static function makePDF($download = TRUE) {
    $template = &CRM_Core_Smarty::singleton();
    $pages = self::popFile();
    $template->assign('pages', $pages);
    $pages = $template->fetch('CRM/common/AnnualReceipt.tpl');
    $filename = self::$_exportFileName;
    $pdf_real_filename = CRM_Utils_PDF_Utils::html2pdf($pages, $filename, 'portrait', 'a4', $download);
    if(!$download){
      return $pdf_real_filename;
    }
  }

  static function getExportFileName() {
    $rand = substr(md5(microtime(TRUE)), 0, 4);
    return 'Annual-Receipt-Batch-'.$rand.'-'.date('YmdHi');
  }

  public function makeReceipt($contactIds, &$option) {
    global $civicrm_batch;
    $totalNumRows = count($contactIds);
    $batchThreshold = self::BATCH_THRESHOLD;
    if (empty($option['tempFileName'])) {
      $tempFileName = self::getExportFileName();
      self::$_tmpreceipt = $option['tempFileName'] = $tempFileName;
    }
    else {
      self::$_tmpreceipt = '/tmp/'.$option['tempFileName'];
    }
    $offset = 0;
    $config = CRM_Core_Config::singleton();
    if (empty($civicrm_batch)) {
      // First execute, not batch session.
      if ($totalNumRows > $batchThreshold) {
        $option['tempFileName'] = $tempFileName;
        $exportFileName = self::getExportFileName().'.pdf';
        $file = $config->uploadDir.$exportFileName;
        $batch = new CRM_Batch_BAO_Batch();
        $batchParams = array(
          'label' => ts('Export').': '.$exportFileName,
          'startCallback' => NULL,
          'startCallback_args' => NULL,
          'processCallback' => array(__CLASS__, __FUNCTION__),
          'processCallbackArgs' => array($contactIds, $option),
          'finishCallback' => array(__CLASS__, 'batchFinish'),
          'finishCallbackArgs' => array($tempFileName, $exportFileName),
          'exportFile' => $file,
          'download' => array(
            'header' => array(
              'Content-Type: application/pdf',
              'Content-Disposition: attachment;filename="'.$exportFileName.'"',
            ),
            'file' => $file,
          ),
          'actionPermission' => '',
          'total' => $totalNumRows,
          'processed' => 0,
        );
        $batch->start($batchParams);
        // redirect to notice page
        CRM_Core_Session::setStatus(ts("Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed."));
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$batch->_id}"));
      }
      else {
        $eachCount = count($contactIds);
      }
    }
    else {
      $eachCount = self::GENERATE_COUNT_EACH_TIME;
      if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
        $offset = $civicrm_batch->data['processed'] ;
      }
    }

    // If on batch, eachCount use const GENERATE_COUNT_EACH_TIME,
    // If not batch, eachCount use length of $contactIds.
    for ($i=$offset; $i < $offset + $eachCount; $i++) {
      if (!isset($contactIds[$i])) {
        break;
      }
      $contact_id = $contactIds[$i];
      $template = new CRM_Core_Smarty($config->templateDir, $config->templateCompileDir);
      if ($i) {
        $html = '<div class="page-break" style="page-break-after: always;"></div>';
      }
      if (!empty($config->imageBigStampName)){
        $template->assign('imageBigStampUrl', $config->imageUploadDir . $config->imageBigStampName);
      }
      if (!empty($config->imageSmallStampName)) {
        $template->assign('imageSmallStampUrl', $config->imageUploadDir . $config->imageSmallStampName);
      }
      $html .= CRM_Contribute_BAO_Contribution::getAnnualReceipt($contact_id, $option, $template);
      self::pushFile($html);

      // reset template values before processing next transactions
      $template->clearTemplateVars();
      unset($html);
      unset($template);
    }

    if ($civicrm_batch) {
      CRM_Core_Error::debug_log_message("expect $i contacts");
      $civicrm_batch->data['processed'] = $i;
      if ($civicrm_batch->data['processed'] >= $civicrm_batch->data['total']) {
        $civicrm_batch->data['isCompleted'] = TRUE;
      }
      return;
    }
  }

  public static function batchFinish($tempFileName, $exportFileName) {
    global $civicrm_batch;
    self::$_tmpreceipt = '/tmp/'.$tempFileName;
    self::$_exportFileName = $exportFileName;
    $filePath = self::makePDF(FALSE);
    if (!empty($civicrm_batch)) {
      // Update correct file path to Batch data.
      $civicrm_batch->data['exportFile'] = $filePath;
      $civicrm_batch->data['download']['file'] = $filePath;
      $params = (array) $civicrm_batch;
      CRM_Batch_BAO_Batch::create($params);
    }

  }


}

