<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */



/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contribute_Form_Task_PDF extends CRM_Contribute_Form_Task {
  public $_enableEmailReceipt;
  CONST PDF_BATCH_THRESHOLD = 100;

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */
  public $_single = FALSE;

  /**
   * Save last serial id when generate receipt
   *
   * @var string
   */
  public $_lastSerialId = '';


  protected $_tmpreceipt;

  protected $_rows;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);
    if ($id) {
      $this->_contributionIds = array($id);
      $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContributions', 1);
    }
    else {
      parent::preProcess();
    }

    $deductible_type_id = array();
    $sql = "SELECT * FROM civicrm_contribution_type WHERE is_deductible = 0";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
      $deductible_type_id[] = $dao->id;
    }
    if(count($deductible_type_id) > 0){
      $deductible_type = CRM_Utils_Array::implode(',', $deductible_type_id);
      $deductible_type_clause = "OR contribution_type_id IN ($deductible_type)";
    }
    // check that all the contribution ids have pending status
    $query = " SELECT count(*) FROM civicrm_contribution WHERE (contribution_status_id != 1 $deductible_type_clause) AND {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query, CRM_Core_DAO::$_nullArray);
    if ($count != 0) {
      $msg = ts('Contribution need to match conditions below in order to generate receipt(and receipt serial id number)');
      $cond1 = ts('Contribution record must dedutible.(base on <a href="%1">Contribution type</a> settings)',
        array(1 => CRM_Utils_System::url('civicrm/admin/contribute/contributionType','reset=1'))
        );
      $cond2 = ts('Contribution record must completed.');
      $str = "<label>$msg</label>;
  <ul>
    <li>$cond1</li>
    <li>$cond2</li>
  </ul>";
       return CRM_Core_Error::statusBounce($str);
    }

    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();
    $this->assign('single', $this->_single);

    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }

    $url = CRM_Utils_System::url('civicrm/contribute/search', $urlParams);
    $breadCrumb = array(array('url' => $url, 'title' => ts('Search Results')));

    CRM_Utils_System::appendBreadCrumb($breadCrumb);
    CRM_Utils_System::setTitle(ts('Print Contribution Receipts'));

    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    if (!empty($activityTypeId)) {

      $this->_enableEmailReceipt = TRUE;
      CRM_Utils_System::setTitle(ts('Print or Email Contribution Receipts'));
    }
    // Check contact email
    $emailIsEmpty = FALSE;
    $contactId = intval($this->_contactIds);
    $emptyEmail = array();
    foreach ($this->_contactIds as $contactId) {
      $contributorDisplayName = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);
      $result = CRM_Core_BAO_Email::allEmails($contactId);
      $array = array_values($result);
      $email = $array[0]['email'];
      if (empty($email)) {
        $emailIsEmpty = TRUE;
        array_push($emptyEmail,$contributorDisplayName[0]);
      }
    }
    $emptyEmailList = CRM_Utils_Array::implode(",", $emptyEmail);
    $actionName = $this->controller->getActionName($this->_name);
    if ($emailIsEmpty && $actionName[1] == 'display') {
      $this->assign('emptyEmailList', $emptyEmailList);
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
    if (count($this->_contributionIds) > self::PDF_BATCH_THRESHOLD) {
      $msg = ts('You have selected more than %1 contributions.', array(1 => self::PDF_BATCH_THRESHOLD)).' ';
      $msg .= ts('To prevent large volumn email being sent and blocked by recipients, we got to turn off receipt function.').' ';
      $msg .= ts('To enable this, please search again and select under %1 contributions.', array(1 => self::PDF_BATCH_THRESHOLD));
      CRM_Core_Session::setStatus($msg);

      $this->assign("isBatch", TRUE);
    }

    // make receipt target popup new tab
    $options = self::getPrintingTypes();
    $config = CRM_Core_Config::singleton();
    if ($config->debug) {
      $this->addCheckBox('nopdf', '', array('Debug: print html' => 1));
    }
    $this->addRadio( 'window_envelope', ts('Apply to window envelope'), $options,null,'<br/>',true );

    if (count($this->_contributionIds) <= self::PDF_BATCH_THRESHOLD && $this->_enableEmailReceipt) {
      $this->addCheckBox('email_pdf_receipt', '', array(ts('Send an Email') => 1));
      $fromEmails = CRM_Contact_BAO_Contact_Utils::fromEmailAddress();
      $emails = array();
      if (!empty($fromEmails['system'])) {
        $emails[ts('Default').' '.ts('(built-in)')] = $fromEmails['system'];
      }
      $emails[ts('Default')] = $fromEmails['default'];
      if (!empty($fromEmails['contact'])) {
        $emails[ts('Your Email')] = $fromEmails['contact'];
      }
      if (CRM_Core_Permission::check('access CiviContribute') && !empty($fromEmails['domain'])) {
        $emails[ts('Other')] = $fromEmails['domain'];
      }
      $this->addSelect('from_email', ts('From Email'), array('' => ts('- select -')) + $emails);
      $this->addWysiwyg('receipt_text',
        ts('Body Html'),
        array(
          'cols' => '80',
          'rows' => '4',
        )
      );
    }

    $buttons = array();
    if (count($this->_contributionIds) <= self::PDF_BATCH_THRESHOLD && $this->_enableEmailReceipt) {
      $buttons[] = array(
        'type' => 'upload',
        'name' => ts('Email Receipt'),
      );
    }
    $buttons[] = array(
      'type' => 'next',
      'name' => ts('Download Receipt(s)'),
      'isDefault' => TRUE,
    );
    $buttons[] = array(
      'type' => 'back',
      'name' => ts('Cancel'),
    );
    $this->addButtons($buttons);
    $this->addFormRule(array('CRM_Contribute_Form_Task_PDF', 'formRule'), $this);
  }

  static public function formRule($fields, $files, $self) {
    $errors = array();
    if (!empty($fields['email_pdf_receipt'][1]) && empty($fields['from_email'])) {
      $errors['from_email'] = ts('%1 is a required field.', array(1 => ts('From Email')));
      // make receipt not popup when error detect
    }
    return $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    // get all the details needed to generate a receipt

    $actionName = $this->controller->getActionName($this->_name);
    list($page, $action) = $actionName;
    if ($action == 'next') {
      $totalNumPDF = count($this->_contributionIds);
      $params = $this->controller->exportValues($this->_name);
      $args = array(
        $this->_contributionIds,
        $params,
      );
      if (count($this->_contributionIds) > self::PDF_BATCH_THRESHOLD) {
        // start batch
        $config = CRM_Core_Config::singleton();
        $rand = substr(md5(microtime(TRUE)), 0, 4);
        $fileName = CRM_Utils_String::safeFilename('Receipt-Batch-'.$rand.'-'.date('YmdHi')).'.zip';
        $file = $config->uploadDir.$fileName;
        $batch = new CRM_Batch_BAO_Batch();
        $batchParams = array(
          'label' => ts('Receipt').': '.$fileName,
          'startCallback' => NULL,
          'startCallbackArgs' => NULL,
          'processCallback' => array($this, 'batchPDF'),
          'processCallbackArgs' => $args,
          'finishCallback' => array($this, 'batchFinishCallback'),
          'finishCallbackArgs' => NULL,
          'exportFile' => $file,
          'download' => array(
            'header' => array(
              'Content-Type: application/zip',
              'Content-Transfer-Encoding: Binary',
              'Content-Disposition: attachment;filename="'.$fileName.'"',
            ),
            'file' => $file,
          ),
          'actionPermission' => '',
          'total' => $totalNumPDF,
          'processed' => 0,
        );
        $batch->start($batchParams);

        // redirect to notice page
        CRM_Core_Session::setStatus(ts("Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed."));
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$batch->_id}"));
      }
      else {
        $this->batchPDF($this->_contributionIds, $params);
      }
    }
    else if($action == 'upload') {
      // #28472, batch sending email pdf receipt
      $params = $this->controller->exportValues($this->_name);
      foreach($this->_contributionIds as $contributionId) {
        CRM_Contribute_BAO_Contribution::sendPDFReceipt($contributionId, $params['from_email'], $params['window_envelope'], $params['receipt_text']);
      }
    }
  }

  public function batchPDF($contributionIds, $params) {
    global $civicrm_batch;

    if ($civicrm_batch) {
      $offset = 0;
      if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
        $offset = $civicrm_batch->data['processed'];
      }
      $contributionIds = array_slice($contributionIds, $offset, self::PDF_BATCH_THRESHOLD);
    }
    
    $contribIDs = CRM_Utils_Array::implode(',', $contributionIds);
    $details = &CRM_Contribute_Form_Task_Status::getDetails($contribIDs);
    $details = array_replace(array_flip($contributionIds), $details);
    $this->makeReceipt($details, $params['window_envelope']);
    $this->createActivity($details);

    if ($civicrm_batch) {
      $filenameNum = sprintf("%'.07d", $civicrm_batch->data['processed']+1); 
      $dest = str_replace('.zip', '', $civicrm_batch->data['download']['file']);
      $dest .= '_'.$filenameNum.'.pdf';
      $pdf = $this->makePDF(FALSE);
      rename($pdf, $dest);
      $civicrm_batch->data['processed'] += self::PDF_BATCH_THRESHOLD;
      if ($civicrm_batch->data['processed'] >= $civicrm_batch->data['total']) {
        $civicrm_batch->data['processed'] = $civicrm_batch->data['total'];
        $civicrm_batch->data['isCompleted'] = TRUE;
      }
    }
    else {
      $this->makePDF();
      CRM_Utils_System::civiExit();
    }
  }

  public function batchFinishCallback() {
    global $civicrm_batch;
    if (!empty($civicrm_batch)) {
      $prefix = str_replace('.zip', '', $civicrm_batch->data['download']['file']);
      $names = explode('-', basename($prefix));
      $prefixFile = end($names).'_';
      $prefix .= '_';
      $zipFile = $civicrm_batch->data['download']['file'];
      $zip = new ZipArchive();
      $files = array();
      if ($zip->open($zipFile, ZipArchive::CREATE) == TRUE) {
        foreach(glob($prefix."*.pdf") as $fileName) {
          if (is_file($fileName)) {
            $files[] = $fileName;
            $fname = str_replace($prefix, $prefixFile, $fileName);
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

  public function pushFile($html) {
    // tmp directory
    file_put_contents($this->_tmpreceipt, $html, FILE_APPEND);
  }
  public function popFile() {
    $return = file_get_contents($this->_tmpreceipt);
    unlink($this->_tmpreceipt);
    return $return;
  }

  public function makePDF($download = TRUE, $encryptWhenPossible = FALSE, $encryptPwd = NULL) {
    $template = &CRM_Core_Smarty::singleton();
    $pages = $this->popFile();
    $template->assign('pages', $pages);
    $pages = $template->fetch('CRM/common/Receipt.tpl');
    $pdf_real_filename = CRM_Utils_PDF_Utils::html2pdf($pages, 'Receipt.pdf', 'portrait', 'a4', $download);
    $encryptPwd = str_replace(array('\'', '"'), '', $encryptPwd);
    if ($encryptWhenPossible) {
      $pdf_real_filename = self::encryptPDF($pdf_real_filename, $encryptPwd);
    }
    if (!$download) {
      return $pdf_real_filename;
    }
  }

  /**
   * Generate pdf from static version of pdf
   *
   *
   * @ $dest string
   * destination of pdf out
   *
   * @ $encryptPwd string
   * destination of encrypt password
   *
   * @ $option string
   * see /usr/bin/qpdf --help
   */
  static function encryptPDF($dest, $encryptPwd, $option = ' --encrypt', $key_length = 256) {
    $config = CRM_Core_Config::singleton();
    $qpdf = $config->qpdfPath;
    if (empty($qpdf)) {
      CRM_Core_Error::debug_log_message("qpdf path is empty");
      return $dest;
    }

    if (!empty(exec("test -x $qpdf && echo 1"))) {
      $pdfName = basename($dest);
      $destInput = '-- '.$dest;
      $destOutput = str_replace($pdfName, "Encrypt_".$pdfName, $dest);
      $exec = $qpdf.escapeshellcmd("$option $encryptPwd $encryptPwd $key_length $destInput $destOutput");
      exec($exec);
      unlink($dest);
      rename($destOutput, $dest);
      return $dest;
    }
    else {
      CRM_Core_Error::debug_log_message("Could not find qpdf library");
      return FALSE;
    }
  }

  public function makeReceipt($details, $window_envelope = NULL, $isAttachment = FALSE) {
    $config = CRM_Core_Config::singleton();
    $tmpDir = empty($config->uploadDir) ? CIVICRM_TEMPLATE_COMPILEDIR : $config->uploadDir;
    $this->_tmpreceipt = tempnam($tmpDir, 'receipt');
    if (is_numeric($details)) {
      $details = &CRM_Contribute_Form_Task_Status::getDetails($details);
    }

    switch ($window_envelope) {
      case 'single_page_letter':
        $print_type = array(
          'copy' => ts('Copy Receipts'),
        );
        break;
      case 'single_page_letter_with_copied':
        $print_type = array(
          'copy' => ts('Copy Receipts'),
          'original' => ts('Original Receipts'),
        );
        break;
      // refs #28069, to respect default template
      // we need this workaround to print copy only receipt
      case 'copy_only':
        $print_type = array(
          'copy' => ts('Copy Receipts'),
        );
        $window_envelope = '';
        break;
      case 'none':
      default:
        $print_type = array(
          'original' => ts('Original Receipts'),
          'copy' => ts('Copy Receipts'),
        );
        $window_envelope = '';
        break;
    }
    // domain info
    $domain = CRM_Core_BAO_Domain::getDomain();
    $location = $domain->getLocationValues();

    $config = CRM_Core_Config::singleton();
    $count = 0;

    foreach ($details as $contribID => $detail) {
      $input = $ids = $objects = array();
      $input['component'] = $detail['component'];
      $ids['contact'] = $detail['contact'];
      $ids['contribution'] = $contribID;
      $ids['contributionRecur'] = NULL;
      $ids['contributionPage'] = NULL;
      $ids['membership'] = $detail['membership'];
      $ids['participant'] = $detail['participant'];
      $ids['event'] = $detail['event'];


      if (!self::validateData($input, $ids, $objects, FALSE)) {
        CRM_Core_Error::fatal("Specific contribution doesn't pass validation before printing receipt. ID: {$contribID}");
      }
      $contribution = &$objects['contribution'];

      $deductible = CRM_Contribute_BAO_ContributionType::deductible($contribution->contribution_type_id, TRUE);
      if(!$deductible) {
        continue;
      }

      $template = new CRM_Core_Smarty($config->templateDir, $config->templateCompileDir);
      $template->assign('print_type', $print_type);
      $template->assign('print_type_count', count($print_type));
      $template->assign('single_page_letter', $window_envelope);
      $template->assign('domain_name', $domain->name);
      $template->assign('domain_email', $location['email'][1]['email']);
      $template->assign('domain_phone', $location['phone'][1]['phone']);
      $template->assign('domain_address', $location['address'][1]['display_text']);
      $template->assign('receiptOrgInfo', htmlspecialchars_decode($config->receiptOrgInfo));
      $template->assign('receiptDescription', htmlspecialchars_decode($config->receiptDescription));
      if (!empty($config->imageBigStampName)) {
        $template->assign('imageBigStampUrl', $config->imageUploadDir . $config->imageBigStampName);
      }
      if (!empty($config->imageSmallStampName)) {
        $template->assign('imageSmallStampUrl', $config->imageUploadDir . $config->imageSmallStampName);
      }

      // set some fake input values so we can reuse IPN code
      $input['amount'] = $contribution->total_amount;
      $input['is_test'] = $contribution->is_test;
      $input['fee_amount'] = $contribution->fee_amount;
      $input['net_amount'] = $contribution->net_amount;
      $input['trxn_id'] = $contribution->trxn_id;
      $input['trxn_date'] = isset($contribution->trxn_date) ? $contribution->trxn_date : NULL;

      $values = array();
      if ($count) {
        $html = '<div style="page-break-after: always;"></div>';
      }

      if(empty($contribution->receipt_id)){
        if(empty($contribution->receipt_date)){
          $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
          $contribution->created_date = CRM_Utils_Date::isoToMysql($contribution->created_date);
          $contribution->receipt_date = date('YmdHis');
        }else{
          $contribution->receipt_date = date('YmdHis', strtotime($contribution->receipt_date));
        }
        $receipt_id = CRM_Contribute_BAO_Contribution::genReceiptID($contribution);
      }
      $html .= CRM_Contribute_BAO_Contribution::getReceipt($input, $ids, $objects, $values, $template);
      $this->_lastSerialId = $template->_tpl_vars['serial_id'];

      // do not use array to prevent memory exhusting
      $this->pushFile($html);
      // dump to file then retrive lately

      // reset template values before processing next transactions
      $template->clearTemplateVars();
      $count++;
      unset($html);
      unset($template);
    }
  }

  static private function createActivity($details) {
    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Print Contribution Receipts', 'name');
    if (!empty($activityTypeId)) {
      $contributeIds = array_keys($details);
      foreach ($contributeIds as $contributeId) {
        if (empty($userID)) {
          $session = CRM_Core_Session::singleton();
          $userID = $session->get('userID');
        }
        $statusId = CRM_Core_OptionGroup::getValue('activity_status', 'Completed', 'name');
        $receiptId = CRM_Core_DAO::singleValueQuery("SELECT receipt_id FROM civicrm_contribution WHERE id = %1", array(1 => array($contributeId, 'Positive')));
        $subject = $receiptId ? ts('Receipt ID') . " : ".$receiptId : ts('Print Contribution Receipts');
        $activityParams = array(
          'activity_type_id' => $activityTypeId,
          'activity_date_time' => date('Y-m-d H:i:s'),
          'source_record_id' => $contributeId,
          'status_id' => $statusId,
          'subject' => $subject,
          'assignee_contact_id' => $details[$contributeId]['contact'],
          'source_contact_id' => $userID,
        );
        CRM_Activity_BAO_Activity::create($activityParams);
      }
    }
  }

  static public function getPrintingTypes(){
    return array(
      'copy_only' => ts('Copied receipt only'),
      'none' => ts('Contain copied receipt without address'),
      'single_page_letter' => ts('Single page with address letter'),
      'single_page_letter_with_copied' => ts('Single page with address letter and copied receipt'),
    );
  }

  static public function validateData(&$input, &$ids, &$objects, $required = TRUE, $paymentProcessorID = NULL) {
    // make sure contribution exists and is valid
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->id = $ids['contribution'];
    if (!$contribution->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution record: {$ids['contribution']}");
      return FALSE;
    }
    if (!empty($contribution->receive_date)) {
      $contribution->receive_date = CRM_Utils_Date::isoToMysql($contribution->receive_date);
    }
    $contribution->created_date = CRM_Utils_Date::isoToMysql($contribution->created_date);

    // make sure contact exists and is valid
    $contact = new CRM_Contact_DAO_Contact();
    if (!empty($contribution->contact_id)) {
      $ids['contact'] = $contribution->contact_id;
    }
    $contact->id = $ids['contact'];
    if (!$contact->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contact record: {$ids['contact']}");
      return FALSE;
    }

    $objects['contact'] = &$contact;
    $objects['contribution'] = &$contribution;
    if (!self::loadObjects($input, $ids, $objects, $required, $paymentProcessorID)) {
      return FALSE;
    }

    return TRUE;
  }

  static public function loadObjects(&$input, &$ids, &$objects, $required, $paymentProcessorID) {
    $config = CRM_Core_Config::singleton();
    $contribution = &$objects['contribution'];

    $objects['membership'] = NULL;
    $objects['contributionRecur'] = NULL;
    $objects['contributionType'] = NULL;
    $objects['event'] = NULL;
    $objects['participant'] = NULL;
    $objects['pledge_payment'] = NULL;

    $contributionType = new CRM_Contribute_DAO_ContributionType();
    $contributionType->id = $contribution->contribution_type_id;
    if (!$contributionType->find(TRUE)) {
      CRM_Core_Error::debug_log_message("Could not find contribution type record: $contributionTypeID");
      return FALSE;
    }
    $objects['contributionType'] = $contributionType;
    $paymentProcessorID = $paymentProcessorID ? $paymentProcessorID : $contribution->payment_processor_id;
    if ($input['component'] == 'contribute') {
      if (!empty($contribution->contribution_recur_id) && empty($ids['contributionRecur'])) {
        $ids['contributionRecur'] = $contribution->contribution_recur_id;
      }

      // retrieve the other optional objects first so
      // stuff down the line can use this info and do things
      // CRM-6056
      if (isset($ids['membership'])) {
        $membership = new CRM_Member_DAO_Membership();
        $membership->id = $ids['membership'];
        if (!$membership->find(TRUE)) {
          CRM_Core_Error::debug_log_message("Could not find membership record: $membershipID");
          return FALSE;
        }
        $membership->join_date = CRM_Utils_Date::isoToMysql($membership->join_date);
        $membership->start_date = CRM_Utils_Date::isoToMysql($membership->start_date);
        $membership->end_date = CRM_Utils_Date::isoToMysql($membership->end_date);
        $membership->reminder_date = CRM_Utils_Date::isoToMysql($membership->reminder_date);

        $objects['membership'] = &$membership;
      }

      if (isset($ids['pledge_payment'])) {
        $objects['pledge_payment'] = array();
        foreach ($ids['pledge_payment'] as $key => $paymentID) {
          $payment = new CRM_Pledge_DAO_Payment();
          $payment->id = $paymentID;
          if (!$payment->find(TRUE)) {
            CRM_Core_Error::debug_log_message("Could not find pledge payment record: $pledge_paymentID");
            return FALSE;
          }
          $objects['pledge_payment'][] = $payment;
        }
      }

      if (isset($ids['contributionRecur'])) {
        $recur = new CRM_Contribute_DAO_ContributionRecur();
        $recur->id = $ids['contributionRecur'];
        if (!$recur->find(TRUE)) {
          CRM_Core_Error::debug_log_message("Could not find recur record: $contributionRecurID");
          return FALSE;
        }
        $objects['contributionRecur'] = &$recur;
      }

      // get the contribution page id from the contribution
      // and then initialize the payment processor from it
      if (!$objects['contribution']->contribution_page_id) {
        if (!CRM_Utils_Array::value('pledge_payment', $ids)) {
          // return if we are just doing an optional validation
          if (!$required) {
            return TRUE;
          }

          CRM_Core_Error::debug_log_message("Could not find contribution page for contribution record: {$objects['contribution']->id}");
          return FALSE;
        }
      }
    }
    else {
      // we are in event mode
      // make sure event exists and is valid
      $event = new CRM_Event_DAO_Event();
      $event->id = $ids['event'];
      if ($ids['event'] &&
        !$event->find(TRUE)
      ) {
        CRM_Core_Error::debug_log_message("Could not find event: $eventID");
        return FALSE;
      }

      $objects['event'] = &$event;

      $participant = new CRM_Event_DAO_Participant();
      $participant->id = $ids['participant'];
      if ($ids['participant'] &&
        !$participant->find(TRUE)
      ) {
        CRM_Core_Error::debug_log_message("Could not find participant: $participantID");
        return FALSE;
      }
      $participant->register_date = CRM_Utils_Date::isoToMysql($participant->register_date);

      $objects['participant'] = &$participant;
    }

    if (!$paymentProcessorID) {
      if ($required) {
        CRM_Core_Error::debug_log_message("Could not find payment processor for contribution record: {$objects['contribution']->id}");
        return FALSE;
      }
    }
    else {
      $paymentProcessor = CRM_Core_BAO_PaymentProcessor::getPayment($paymentProcessorID, $contribution->is_test ? 'test' : 'live');

      $ids['paymentProcessor'] = $paymentProcessorID;
      $objects['paymentProcessor'] = &$paymentProcessor;
    }

    return TRUE;
  }
}
