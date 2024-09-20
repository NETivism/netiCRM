<?php
/**
 * This
 * contacts.
 */
class CRM_Contact_Form_Task_AnnualReceipt extends CRM_Contact_Form_Task {

  public $option;
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
    if (count($this->_contactIds) > self::BATCH_THRESHOLD) {
      $msg = ts('You have selected more than %1 contacts.', array(1 => self::BATCH_THRESHOLD)).' ';
      $msg .= ts('Because of the large amount of data you are about to perform, we will schedule this job for the batch process after you submit. You will receive an email notification when the work is completed.');
      CRM_Core_Session::setStatus($msg);
    }
    else {
      // make receipt target popup new tab
      $this->updateAttributes(array('target' => '_blank'));
    }

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
      
      $totalNumRows = count($this->_contactIds);
      $batchThreshold = self::BATCH_THRESHOLD;
      if ($totalNumRows > $batchThreshold) {
        $exportFileName = self::getExportFileName().'.zip';
        $config = CRM_Core_Config::singleton();
        $file = $config->uploadDir.$exportFileName;
        $batch = new CRM_Batch_BAO_Batch();
        $batchParams = array(
          'label' => ts('Print Annual Receipt').': '.$exportFileName,
          'startCallback' => NULL,
          'startCallbackArgs' => NULL,
          'processCallback' => array($this, 'makeReceipt'),
          'processCallbackArgs' => array($this->_contactIds, $this->option),
          'finishCallback' => array(__CLASS__, 'batchFinish'),
          'finishCallbackArgs' => NULL,
          'exportFile' => $file,
          'download' => array(
            'header' => array(
              'Content-Type: application/zip',
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
    }
    $this->makeReceipt($this->_contactIds, $this->option);
    CRM_Utils_System::civiExit();
  }

  public function pushFile($html) {
    // tmp directory
    file_put_contents(self::$_tmpreceipt, $html, FILE_APPEND);
  }
  public function popFile() {
    $return = file_get_contents(self::$_tmpreceipt);
    unlink(self::$_tmpreceipt);
    return $return;
  }

  public function makePDF($download = TRUE) {
    $template = &CRM_Core_Smarty::singleton();
    $pages = $this->popFile();
    $template->assign('pages', $pages);
    $pages = $template->fetch('CRM/common/AnnualReceipt.tpl');
    $filename = 'AnnualReceipt'.$this->_year.'.pdf';
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

    $config = CRM_Core_Config::singleton();
    $tmpDir = empty($config->uploadDir) ? CRM_Utils_System::cmsDir('temp') .'/' : $config->uploadDir;
    self::$_tmpreceipt = tempnam($tmpDir, 'receipt');
    $offset = 0;
    $download = TRUE;

    if ($civicrm_batch) {
      $download = FALSE;
      $eachCount = self::GENERATE_COUNT_EACH_TIME;
      if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
        $offset = $civicrm_batch->data['processed'] ;
      }

    }
    else {
      $eachCount = count($contactIds);
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
      $this->pushFile($html);

      // reset template values before processing next transactions
      $template->clearTemplateVars();
      unset($html);
      unset($template);
    }
    $filePath = $this->makePDF($download);
    if ($civicrm_batch) {
      $filenameNum = sprintf("%'.07d", $civicrm_batch->data['processed']+1); 
      $dest = str_replace('.zip', '', $civicrm_batch->data['download']['file']);
      $dest .= '_'.$filenameNum.'.pdf';
      rename($filePath, $dest);

      CRM_Core_Error::debug_log_message("expect $i contacts");
      $civicrm_batch->data['processed'] = $i;
      if ($civicrm_batch->data['processed'] >= $civicrm_batch->data['total']) {
        $civicrm_batch->data['isCompleted'] = TRUE;
      }
      return;
    }
  }

  public static function batchFinish() {
    global $civicrm_batch;
    if (!empty($civicrm_batch)) {
      $prefix = str_replace('.zip', '', $civicrm_batch->data['download']['file']);
      $zipFile = $civicrm_batch->data['download']['file'];
      $zip = new ZipArchive();
      $files = array();
      if ($zip->open($zipFile, ZipArchive::CREATE) == TRUE) {
        foreach(glob($prefix."*.pdf") as $fileName) {
          if (is_file($fileName)) {
            $files[] = $fileName;
            $fname = end(explode('-', basename($fileName)));
            $zip->addFile($fileName, $fname);
          }
        }
        $zip->close();
        foreach($files as $fileName) {
          unlink($fileName);
        }
      }
    }

  }


}

