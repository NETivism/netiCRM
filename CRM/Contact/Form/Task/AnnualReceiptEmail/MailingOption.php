<?php

/**
 * This
 * contacts.
 */
class CRM_Contact_Form_Task_AnnualReceiptEmail_MailingOption extends CRM_Contact_Form_Task
{

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */

  const BATCH_LIMIT = 100;
  const BATCH_THRESHOLD = 1;

  static protected $_tmpreceipt = NULL;
  static protected $_exportFileName = NULL;

  /**
   * Display Name of the form
   *
   * @access public
   *
   * @return string
   */
  public function getTitle()
  {
    return ts('Email Delivery Settings');
  }

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess()
  {
    CRM_Utils_System::setTitle(ts('Send Annual Receipt Email'));
    parent::preProcess();

    $queryParams = array();
    $returnProperties = array(
      'sort_name' => 1,
      'do_not_email' => 1,
      'is_deceased' => 1,
      'email' => 1,
    );
    foreach ($this->_contactIds as $contactId) {
      $queryParams[] = array(
        CRM_Core_Form::CB_PREFIX . $contactId, '=', 1, 0, 0,
      );
    }
    $query = new CRM_Contact_BAO_Query($queryParams, $returnProperties);
    $numberofContacts = count($this->_contactIds);
    $suppressedContactIds = array();
    $details = $query->apiQuery($queryParams, $returnProperties, NULL, NULL, 0, $numberofContacts, TRUE, TRUE);
    if (!empty($details[0])) {
      foreach ($details[0] as $contactDetail) {
        if (!empty($contactDetail['is_deceased']) || !empty($contactDetail['do_not_email']) || empty($contactDetail['email'])) {
          $suppressedContactIds[] = $contactDetail['contact_id'];
        }
      }
      $allowedContactIds = array_diff($this->_contactIds, $suppressedContactIds);
      $this->_contactIds = $allowedContactIds;
    }
    $this->assign('total_selected', $numberofContacts);
    $this->assign('suppressed', count($suppressedContactIds));
    $this->assign('total_recipient', count($this->_contactIds));
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm()
  {
    $fromEmails = CRM_Contact_BAO_Contact_Utils::fromEmailAddress();
    $emails = array(
      ts('Default') => $fromEmails['default'],
      ts('Your Email') => $fromEmails['contact'],
    );
    $this->addSelect('receipt_from_email', ts('From Email'), array('' => ts('- select -')) + $emails, NULL, TRUE);
    $this->addWysiwyg(
      'receipt_text',
      ts('Body Html'),
      array(
        'cols' => '80',
        'rows' => '4',
      )
    );
    $this->addTextfield('bcc', ts('BCC'));


    $this->addButtons(
      array(
        array(
          'type' => 'back',
          'name' => ts('<< Go Back'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'next',
          'name' => ts('Send Email'),
          'isDefault' => TRUE,
        ),
      )
    );
  }

  function setDefaultValues()
  {
    $defaults = array();
    return $defaults;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess()
  {
    return;
    $mailingOption = $this->controller->exportValues($this->_name);
    $this->set('mailingOption', $mailingOption);
    $searchOption = $this->get('searchOption');
    if (!empty($searchOption['year'])) {
      CRM_Utils_Hook::postProcess(get_class($this), $this);

      $totalNumContacts = count($this->_contactIds);
      // if only 1 contact, send email immediately
      if ($totalNumContacts <= self::BATCH_THRESHOLD) {
        self::sendAnnualReceiptEmails($this->_contactIds, $searchOption, $mailingOption);
        CRM_Utils_System::civiExit();
      } else {
        $batch = new CRM_Batch_BAO_Batch();
        $batchParams = array(
          'label' => ts('Send Annual Receipt Email') . ' - ' . date('YmdHi'),
          'startCallback' => NULL,
          'startCallbackArgs' => NULL,
          'processCallback' => array($this, 'sendAnnualReceiptEmails'),
          'processCallbackArgs' => array($this->_contactIds, $searchOption, $mailingOption),
          'finishCallback' => NULL,
          'finishCallbackArgs' => NULL,
          'actionPermission' => '',
          'total' => $totalNumContacts,
          'processed' => 0,
        );
        $batch->start($batchParams);
        // redirect to notice page
        CRM_Core_Session::setStatus(ts("Because of the large amount of data you are about to perform, we have scheduled this job for the batch process. You will receive an email notification when the work is completed."));
        CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/batch', "reset=1&id={$batch->_id}"));
      }
    }
  }

  /**
   * PDF receipt temporary file
   *
   * @param int $contactId
   * @param array $searchOption
   * @return string file path of pdf receipt
   */
  public static function makePDFReceipt($contactId, $searchOption) {
    $config = CRM_Core_Config::singleton();
    $pageTemplate = new CRM_Core_Smarty();
    if (!empty($config->imageBigStampName)) {
      $pageTemplate->assign('imageBigStampUrl', $config->imageUploadDir . $config->imageBigStampName);
    }
    if (!empty($config->imageSmallStampName)) {
      $pageTemplate->assign('imageSmallStampUrl', $config->imageUploadDir . $config->imageSmallStampName);
    }

    $option = array_merge($searchOption, array(
      'workflow_group' => 'msg_tpl_workflow_receipt',
      'workflow_value' => 'email_receipt_letter_annual',
    ));
    $pages = CRM_Contribute_BAO_Contribution::getAnnualReceipt($contactId, $searchOption, $pageTemplate);

    $htmlTemplate = new CRM_Core_Smarty();
    $htmlTemplate->assign('pages', $pages);
    $html = $htmlTemplate->fetch('CRM/common/AnnualReceipt.tpl');
    $filename = 'AnnualReceipt-' . $contactId . '-' . $searchOption['year'] . '.pdf';
    $pdfFilename = CRM_Utils_PDF_Utils::html2pdf($html, $filename, 'portrait', 'a4', FALSE);

    // tidy template
    $htmlTemplate->clearTemplateVars();
    $pageTemplate->clearTemplateVars();
    unset($htmlTemplate);
    unset($pageTemplate);

    return $pdfFilename;
  }

  public static function sendAnnualReceiptEmail($contactId, $searchOption, $mailingOption) {
    // get primary location email if no email exist( for billing location).
    $locationTypes = &CRM_Core_PseudoConstant::locationType();
    $billingLocationTypeId = array_search(ts('Billing'), $locationTypes);
    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId, FALSE, $billingLocationTypeId);

    if (!$email) {
      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);
    }
    if (empty($email)) {
      CRM_Core_Error::debug_log_message("Cannot find email of contact id $contactId when sending annual receipt.");
      return FALSE;
    }

    // making PDF
    $pdfFilePath = self::makePDFReceipt($contactId, $searchOption);
    $pdfFileName = strstr($pdfFilePath, 'Receipt');

    // making email template
    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_receipt',
      'valueName' => 'email_annual_receipt',
    );
    $sendTemplateParams = array();
    $sendTemplateParams['attachments'][] = array(
      'fullPath' => $pdfFilePath,
      'mime_type' => 'application/pdf',
      'cleanName' => $pdfFileName,
    );
    $sendTemplateParams['tplParams']['pdf_receipt'] = 1;

    // special case to add Email Receipt without connect contribution(annual receipt connect contact only)
    $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
    $activityStatusId = CRM_Core_OptionGroup::getValue('activity_status', 'Scheduled', 'name');
    $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateByWorkflow($sendTemplateParams['groupName'], $sendTemplateParams['valueName']);
    $subject = array();
    $subject[] = $workflow['msg_title'].'-'.$searchOption['year'];
    if (is_array($searchOption['contribution_type_id']) && array_search(0, $searchOption['contribution_type_id']) !== FALSE) {
      $subject[] = ts('Contribution Types').':'.implode(',', $searchOption['contribution_type_id']);
    }
    if (!empty($searchOption['is_recur'])) {
      $subject[] = $searchOption['is_recur'] > 0 ? ts('Recurring Contribution') : ts('Non-Recurring Contribution');
    }
    $activityParams = array(
      'assignee_contact_id' => $contactId,
      'activity_type_id' => $activityTypeId,
      'subject' => implode(' ', $subject),
      'activity_date_time' => date('YmdHis'),
      'is_test' => 0,
      'status_id' => $activityStatusId,
      'skipRecentView' => TRUE,
    );
    $session = CRM_Core_Session::singleton();
    $loggedUserId = $session->get('userID');
    if ($loggedUserId) {
      $activityParams['source_contact_id'] = $loggedUserId;
    }
    else {
      $activityParams['source_contact_id'] = $contactId;
    }
    $activity = CRM_Activity_BAO_Activity::create($activityParams);

    if (!is_a($activity, 'CRM_Core_Error') && isset($activity->id)) {
      $activityId = $activity->id;
      $sendTemplateParams['from'] = $mailingOption['receipt_from_email'];
      $sendTemplateParams['toName'] = $displayName;
      $sendTemplateParams['toEmail'] = $email;
      $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $mailingOption['bcc']);
      $sendTemplateParams['activityId'] = $activity->id;
      CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams, array(
        0 => array('CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  array($activityId, TRUE)),
        1 => array('CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  array($activityId, FALSE)),
      ));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Send pdf receipt mail to each contact id
   *
   * @param array $contactIds
   * @param array $searchOption
   * @param array $mailingOption
   * @return void
   */
  public static function sendAnnualReceiptEmails($contactIds, $searchOption, $mailingOption)
  {
    global $civicrm_batch;

    if ($civicrm_batch) {
      $limit = self::BATCH_LIMIT;
      if (isset($civicrm_batch->data['processed']) && !empty($civicrm_batch->data['processed'])) {
        $offset = $civicrm_batch->data['processed'];
      } else {
        $offset = 0;
      }
      for ($i = $offset; $i < $offset + $limit; $i++) {
        if (!isset($contactIds[$i])) {
          break;
        }
        self::sendAnnualReceiptEmail($contactIds[$i], $searchOption, $mailingOption);
      }
      if ($civicrm_batch->data['processed'] >= $civicrm_batch->data['total']) {
        $civicrm_batch->data['isCompleted'] = TRUE;
      }
    }
  }
}
