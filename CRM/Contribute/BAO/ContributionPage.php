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
 * $Id$
 *
 */



/**
 * This class contains Contribution Page related functions.
 */
class CRM_Contribute_BAO_ContributionPage extends CRM_Contribute_DAO_ContributionPage {

  CONST IS_ACTIVE = 1;
  CONST IS_SPECIAL = 2;

  /**
   * takes an associative array and creates a contribution page object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Contribute_DAO_ContributionPage object
   * @access public
   * @static
   */
  public static function &create(&$params) {
    $transaction = new CRM_Core_Transaction();
    $dao = new CRM_Contribute_DAO_ContributionPage();
    $dao->copyValues($params);
    $dao->save();

    if (CRM_Utils_Array::value('custom', $params) && is_array($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution_page', $dao->id);
    }
    $transaction->commit();
    return $dao;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionPage', $id, 'is_active', $is_active);
  }

  static function setValues($id, &$values) {
    $params = ['id' => $id];

    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $values);

    // get the amounts and the label

    $values['amount'] = [];
    CRM_Core_OptionGroup::getAssoc("civicrm_contribution_page.amount.{$id}", $values['amount'], TRUE);

    // get the profile ids

    $ufJoinParams = ['entity_table' => 'civicrm_contribution_page',
      'entity_id' => $id,
    ];
    list($values['custom_pre_id'],
      $values['custom_post_id']
    ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

    // add an accounting code also
    if ($values['contribution_type_id']) {
      $values['accountingCode'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType',
        $values['contribution_type_id'],
        'accounting_code'
      );
    }
  }

  /**
   * Function to send the emails
   *
   * @param int     $contactID         contact id
   * @param array   $values            associated array of fields
   * @param boolean $isTest            if in test mode
   * @param boolean $returnMessageText return the message text instead of sending the mail
   *
   * @return void
   * @access public
   * @static
   */
  static function sendMail($contactID, &$values, $isTest = FALSE, $returnMessageText = FALSE) {
    $config = CRM_Core_Config::singleton();
    $gIds = [];
    $params = [];
    if (isset($values['custom_pre_id'])) {
      $preProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_pre_id']);
      if ($preProfileType == 'Membership' && CRM_Utils_Array::value('membership_id', $values)) {
        $params['custom_pre_id'] = [['membership_id', '=', $values['membership_id'], 0, 0]];
      }
      elseif ($preProfileType == 'Contribution' && CRM_Utils_Array::value('contribution_id', $values)) {
        $params['custom_pre_id'] = [['contribution_id', '=', $values['contribution_id'], 0, 0]];
      }

      $gIds['custom_pre_id'] = $values['custom_pre_id'];
    }

    if (isset($values['custom_post_id'])) {
      $postProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_post_id']);
      if ($postProfileType == 'Membership' && CRM_Utils_Array::value('membership_id', $values)) {
        $params['custom_post_id'] = [['membership_id', '=', $values['membership_id'], 0, 0]];
      }
      elseif ($postProfileType == 'Contribution' && CRM_Utils_Array::value('contribution_id', $values)) {
        $params['custom_post_id'] = [['contribution_id', '=', $values['contribution_id'], 0, 0]];
      }

      $gIds['custom_post_id'] = $values['custom_post_id'];
    }

    //check whether it is a test drive
    if ($isTest && !empty($params['custom_pre_id'])) {
      $params['custom_pre_id'][] = ['contribution_test', '=', 1, 0, 0];
    }

    if ($isTest && !empty($params['custom_post_id'])) {
      $params['custom_post_id'][] = ['contribution_test', '=', 1, 0, 0];
    }
    if (!$returnMessageText) {
      //send notification email if field values are set (CRM-1941)

      foreach ($gIds as $key => $gId) {
        $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify');
        if ($email) {
          $val = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $contactID, $params[$key]);
          $fields = CRM_Core_BAO_UFGroup::getFields($gId, FALSE, CRM_Core_Action::VIEW);
          CRM_Core_BAO_UFGroup::verifySubmittedValue($fields, $val, $values['submitted']);
          CRM_Core_BAO_UFGroup::commonSendMail($contactID, $val);
        }
      }
    }

    if (CRM_Utils_Array::value('is_email_receipt', $values) ||
      CRM_Utils_Array::value('onbehalf_dupe_alert', $values) ||
      $returnMessageText
    ) {
      $template = CRM_Core_Smarty::singleton();
      $is_pay_later = $template->get_template_vars('is_pay_later');

      // refs #28471, auto send receipt after contribution
      $haveAttachReceiptOption = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
      $contributionTypeId = CRM_Utils_Array::value('contribution_type_id', $values);
      $deductible = CRM_Contribute_BAO_ContributionType::deductible($contributionTypeId, TRUE);


      if (!CRM_Utils_Array::arrayKeyExists('related_contact', $values)) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID, FALSE, $billingLocationTypeId);
      }
      // get primary location email if no email exist( for billing location).
      if (!$email) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }

      if ($config->receiptEmailAuto && $haveAttachReceiptOption && !$is_pay_later && $deductible) {
        $receiptEmailType = !empty($config->receiptEmailType) ? $config->receiptEmailType : 'copy_only';
        $receiptTask = new CRM_Contribute_Form_Task_PDF();
        $receiptTask->makeReceipt($values['contribution_id'], $receiptEmailType, TRUE);
        //set encrypt password
        if (!empty($config->receiptEmailEncryption) && $config->receiptEmailEncryption) {
          $receiptPwd = $email;
          if (!empty($receiptTask->_lastSerialId) && preg_match('/^[A-Za-z]{1,2}\d{8,9}$|^\d{8}$/', $receiptTask->_lastSerialId)) {
            $receiptPwd = $receiptTask->_lastSerialId;
          }
          $pdfFilePath = $receiptTask->makePDF(FALSE, TRUE, $receiptPwd);
        }
        else {
          $pdfFilePath = $receiptTask->makePDF(FALSE);
        }
        $pdfFileName = strstr($pdfFilePath, 'Receipt');
        $pdfParams =  [
          'fullPath' => $pdfFilePath,
          'mime_type' => 'application/pdf',
          'cleanName' => $pdfFileName,
        ];
      }

      // get the billing location type
      if (!CRM_Utils_Array::arrayKeyExists('related_contact', $values)) {
        $locationTypes = &CRM_Core_PseudoConstant::locationType();
        $billingLocationTypeId = array_search(ts('Billing'), $locationTypes);
      }
      else {
        // presence of related contact implies onbehalf of org case,
        // where location type is set to default.

        $locType = CRM_Core_BAO_LocationType::getDefault();
        $billingLocationTypeId = $locType->id;
      }

      //for display profile need to get individual contact id,
      //hence get it from related_contact if on behalf of org true CRM-3767.

      //CRM-5001 Contribution/Membership:: On Behalf of Organization,
      //If profile GROUP contain the Individual type then consider the
      //profile is of Individual ( including the custom data of membership/contribution )
      //IF Individual type not present in profile then it is consider as Organization data.

      $userID = $contactID;
      $template->clear_assign(['customPre', 'customPost']);
      if ($preID = CRM_Utils_Array::value('custom_pre_id', $values)) {
        if (CRM_Utils_Array::value('related_contact', $values)) {
          $preProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($preID);
          if (in_array('Individual', $preProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        self::buildCustomDisplay($preID, 'customPre', $userID, $template, $params['custom_pre_id']);
      }
      $userID = $contactID;
      if ($postID = CRM_Utils_Array::value('custom_post_id', $values)) {
        if (CRM_Utils_Array::value('related_contact', $values)) {
          $postProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($postID);
          if (in_array('Individual', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        self::buildCustomDisplay($postID, 'customPost', $userID, $template, $params['custom_post_id']);
      }

      // set email in the template here
      global $civicrm_conf;
      $fromEmail = '';
      if (!empty($values['receipt_from_email'])) {
        if (!empty($civicrm_conf['mailing_noreply_domain']) && preg_match($civicrm_conf['mailing_noreply_domain'], $values['receipt_from_email'])) {
          $fromEmail = '';
        }
        else {
          $fromEmail = $values['receipt_from_email'];
        }
      }
      $tplParams = [
        'createdDate' => CRM_Utils_Array::value('created_date', $values),
        'email' => $email,
        'receiptFromEmail' => $fromEmail,
        'contactID' => $contactID,
        'contributionID' => $values['contribution_id'],
        'membershipID' => CRM_Utils_Array::value('membership_id', $values),
        // CRM-5095
        'lineItem' => CRM_Utils_Array::value('lineItem', $values),
        // CRM-5095
        'priceSetID' => CRM_Utils_Array::value('priceSetID', $values),
      ];

      // #18853, tokenize thank you top text
      $receiptText = $template->get_template_vars('receipt_text');
      if($receiptText) {
        $receiptText = self::tokenize($contactID, $receiptText, $values['contribution_id']);
        $template->assign('receipt_text', $receiptText);
      }

      if ($contributionTypeId = CRM_Utils_Array::value('contribution_type_id', $values)) {
        $tplParams['contributionTypeId'] = $contributionTypeId;
        $tplParams['contributionTypeName'] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionType',
          $contributionTypeId
        );
      }

      // address required during receipt processing (pdf and email receipt)
      if ($displayAddress = CRM_Utils_Array::value('address', $values)) {
        $tplParams['address'] = $displayAddress;
        $tplParams['contributeMode'] = NULL;
      }

      // CRM-6976
      $originalCCReceipt = CRM_Utils_Array::value('cc_receipt', $values);

      // cc to related contacts of contributor OR the one who
      // signs up. Is used for cases like - on behalf of
      // contribution / signup ..etc
      if (CRM_Utils_Array::arrayKeyExists('related_contact', $values)) {
        list($ccDisplayName, $ccEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($values['related_contact']);
        $ccMailId = "{$ccDisplayName} <{$ccEmail}>";

        $values['cc_receipt'] = CRM_Utils_Array::value('cc_receipt', $values) ? ($values['cc_receipt'] . ',' . $ccMailId) : $ccMailId;

        // reset primary-email in the template
        $tplParams['email'] = $ccEmail;

        $tplParams['onBehalfName'] = $displayName;
        $tplParams['onBehalfEmail'] = $email;
      }

      // use either the contribution or membership receipt, based on whether it’s a membership-related contrib or not
      $sendTemplateParams = [
        'groupName' => $tplParams['membershipID'] ? 'msg_tpl_workflow_membership' : 'msg_tpl_workflow_contribution',
        'valueName' => $tplParams['membershipID'] ? 'membership_online_receipt' : 'contribution_online_receipt',
        'contactId' => $contactID,
        'tplParams' => $tplParams,
        'isTest' => $isTest,
        'PDFFilename' => 'receipt.pdf',
      ];

      $activityTypeId = CRM_Core_OptionGroup::getValue('activity_type', 'Email Receipt', 'name');
      $contribParams = ['id' => $values['contribution_id']];
      $contribution = CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_Contribution', $contribParams, CRM_Core_DAO::$_nullArray);
      $workflow = CRM_Core_BAO_MessageTemplates::getMessageTemplateByWorkflow($sendTemplateParams['groupName'], $sendTemplateParams['valueName']);
      if (!empty($pdfParams) && !empty($activityTypeId)) {
        $sendTemplateParams['attachments'][] = $pdfParams;
        $sendTemplateParams['tplParams']['pdf_receipt'] = 1;
        if (!empty($config->receiptEmailEncryption)) {
          $pdfReceiptDecryptInfo = $config->receiptEmailEncryptionText;
          if (empty(trim($pdfReceiptDecryptInfo))) {
            $pdfReceiptDecryptInfo = ts('Your PDF receipt is encrypted.').' '.ts('The password is either your tax certificate number or, if not provided, your email address.');
          }
          $sendTemplateParams['tplParams']['pdf_receipt_decrypt_info'] = $pdfReceiptDecryptInfo;
        }
        unset($sendTemplateParams['PDFFilename']);

        $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($contribution, 'Email Receipt', $workflow['msg_title']);
      }
      else {
        if ($tplParams['membershipID']) {
          $memberParams = ['id' => $tplParams['membershipID']];
          $membership = CRM_Core_DAO::commonRetrieve('CRM_Member_DAO_Membership', $memberParams, CRM_Core_DAO::$_nullArray);
          $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($membership, 'Membership Notification Email', $workflow['msg_title']);
        }
        else {
          $activityId = CRM_Activity_BAO_Activity::addTransactionalActivity($contribution, 'Contribution Notification Email', $workflow['msg_title']);
        }
      }
      $sendTemplateParams['activityId'] = $activityId;

      if ($returnMessageText) {
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams, CRM_Core_DAO::$_nullObject, [
          0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, TRUE]],
          1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, FALSE]],
        ]);
        return ['subject' => $subject,
          'body' => $message,
          'to' => $displayName,
          'html' => $html,
        ];
      }

      // do_not_notify check
      $contactDetail = CRM_Contact_BAO_Contact::getContactDetails($contactID);
      if (isset($contactDetail[5]) && !empty($contactDetail[5])) {
        CRM_Core_Error::debug_log_message("Skipped email notify {$sendTemplateParams['valueName']} for contact $contactID due to do_not_notify marked");
        $message = ts('Email has NOT been sent to %1 contact(s) - communication preferences specify DO NOT NOTIFY OR valid Email is NOT present.', [1 => '1']);
        CRM_Core_Session::singleton()->setStatus($message);
        return;
      }

      if ($values['is_email_receipt']) {
        $sendTemplateParams['from'] = CRM_Utils_Array::value('receipt_from_name', $values) . ' <' . $values['receipt_from_email'] . '>';
        $sendTemplateParams['toName'] = $displayName;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_receipt', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $values);
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams, CRM_Core_DAO::$_nullObject, [
          0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, TRUE]],
          1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' =>  [$activityId, FALSE]],
        ]);
      }

      // send duplicate alert, if dupe match found during on-behalf-of processing.
      if (CRM_Utils_Array::value('onbehalf_dupe_alert', $values)) {
        $sendTemplateParams['groupName'] = 'msg_tpl_workflow_contribution';
        $sendTemplateParams['valueName'] = 'contribution_dupalert';
        $sendTemplateParams['from'] = ts('Automatically Generated') . " <{$values['receipt_from_email']}>";
        $sendTemplateParams['toName'] = CRM_Utils_Array::value('receipt_from_name', $values);
        $sendTemplateParams['toEmail'] = $values['receipt_from_email'];
        $sendTemplateParams['tplParams']['onBehalfID'] = $contactID;
        $sendTemplateParams['tplParams']['receiptMessage'] = $message;

        // fix cc and reset back to original, CRM-6976
        $sendTemplateParams['cc'] = $originalCCReceipt;

        CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);
      }
    }
  }


  /**
   * Function to send the emails
   *
   * @param int     $contactID         contact id
   * @param array   $values            associated array of fields
   * @param boolean $isTest            if in test mode
   * @param boolean $returnMessageText return the message text instead of sending the mail
   *
   * @return void
   * @access public
   * @static
   */
  static function sendFailedNotifyMail($contactID, &$values, $isTest = FALSE, $returnMessageText = FALSE) {
    $recur_id = CRM_Utils_Array::value('contribution_recur_id', $values);
    $contribution_id = CRM_Utils_Array::value('contribution_id', $values);
    $tplParams = [
      'display_name'    => CRM_Utils_Array::value('display_name', $values),
      'amount'          =>
        CRM_Utils_Money::format(
          CRM_Utils_Array::value('total_amount', $values),
          CRM_Utils_Array::value('currency', $values)
        ),
      'cancel_date'     => date('Y-m-d H:i:s',strtotime(CRM_Utils_Array::value('cancel_date', $values))),
      'url'             =>
        CRM_Utils_System::url(
          'civicrm/contact/view/contributionrecur',
          "reset=1&id={$recur_id}&cid={$contactID}",
          TRUE
        ),
      'trxn_id'         => CRM_Utils_Array::value('trxn_id', $values),
      'detail'          => CRM_Utils_Array::value('message', $values),
    ];

    $recur_fail_notify = CRM_Utils_Array::value('recur_fail_notify', $values);
    $emailList = explode(',', $recur_fail_notify);

    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    foreach ($emailList as $emailTo) {
      // FIXME: take the below out of the foreach loop
      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
        [
          'groupName' => 'msg_tpl_workflow_contribution',
          'valueName' => 'contribution_recur_fail_notify',
          'contactId' => $contactID,
          'tplParams' => $tplParams,
          'from' => "$domainEmailName <$domainEmailAddress>",
          'toEmail' => str_replace(' ', '', $emailTo),
          'isTest' => $isTest,
        ]
      );
      $returnArray[] = [
        'success' => $sent,
        'subject' => $subject,
        'body' => $message,
        'to' => $emailTo,
        'html' => $html,
      ];
    }
    if ($returnMessageText){
      return $returnArray;
    }
  }

  /**
   * Function to send the emails for Recurring Contribution Notication
   *
   * @param string  $type         txnType
   * @param int     $contactID    contact id for contributor
   * @param int     $pageID       contribution page id
   * @param object  $recur        object of recurring contribution table
   *
   * @return void
   * @access public
   * @static
   */
  static function recurringNofify($type, $contactID, $pageID, $recur) {
    $value = [];
    CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_ContributionPage', 'id',
      $pageID, $value,
      ['title', 'is_email_receipt', 'receipt_from_name',
        'receipt_from_email', 'cc_receipt', 'bcc_receipt',
      ]
    );
    if ($value[$pageID]['is_email_receipt']) {
      $receiptFrom = '"' . CRM_Utils_Array::value('receipt_from_name', $value[$pageID]) . '" <' . $value[$pageID]['receipt_from_email'] . '>';

      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID, FALSE);


      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
        [
          'groupName' => 'msg_tpl_workflow_contribution',
          'valueName' => 'contribution_recurring_notify',
          'contactId' => $contactID,
          'tplParams' => [
            'recur_frequency_interval' => $recur->frequency_interval,
            'recur_frequency_unit' => ts($recur->frequency_unit),
            'recur_installments' => $recur->installments,
            'recur_start_date' => $recur->start_date,
            'recur_end_date' => $recur->end_date,
            'recur_amount' => $recur->amount,
            'recur_txnType' => $type,
            'displayName' => $displayName,
            'receipt_from_name' => $value[$pageID]['receipt_from_name'],
            'receipt_from_email' => $value[$pageID]['receipt_from_email'],
          ],
          'from' => $receiptFrom,
          'toName' => $displayName,
          'toEmail' => $email,
        ]
      );

      if ($sent) {
        CRM_Core_Error::debug_log_message('Success: mail sent for recurring notification.');
      }
      else {
        CRM_Core_Error::debug_log_message('Failure: mail not sent for recurring notification.');
      }
    }
  }

  /**
   * Function to add the custom fields for contribution page (ie profile)
   *
   * @param int    $gid            uf group id
   * @param string $name
   * @param int    $cid            contact id
   * @param array  $params         params to build component whereclause
   *
   * @return void
   * @access public
   * @static
   */
  static function buildCustomDisplay($gid, $name, $cid, &$template, &$params) {
    if ($gid) {

      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $values = [];
        $groupTitle = NULL;
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW);

        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v["groupTitle"];
          }
          // unset all view only profile field
          if ($v['is_view']){
            unset($fields[$k]);
          }
        }

        if ($groupTitle) {
          $template->assign($name . "_grouptitle", $groupTitle);
        }

        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params, CRM_Core_BAO_UFGroup::MASK_ALL);

        foreach ($fields as $k => $v) {
          // suppress all file fields from display
          if ((CRM_Utils_Array::value('data_type', $v, '') == 'File' || CRM_Utils_Array::value('name', $v, '') == 'image_URL') && !empty($values[$v['title']] )){
            $values[$v['title']] = ts("Uploaded files received");
          }
        }

        if (count($values)) {
          $template->assign($name, $values);
        }
      }
    }
  }

  /**
   * This function is to make a copy of a contribution page, including
   * all the blocks in the page
   *
   * @param int $id the contribution page id to copy
   *
   * @return the copy object
   * @access public
   * @static
   */
  static function copy($id) {
    $fieldsFix = [
      'prefix' => [
        'title' => ts('Copy of') . ' ',
        ],
      'replace' => [
        'is_active' => 0,
        ],
      ];
    $copy = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_ContributionPage',
      ['id' => $id],
      NULL,
      $fieldsFix
    );

    //copying all the blocks pertaining to the contribution page
    $copyPledgeBlock = &CRM_Core_DAO::copyGeneric('CRM_Pledge_DAO_PledgeBlock',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ],
      ['entity_id' => $copy->id]
    );

    $copyMembershipBlock = &CRM_Core_DAO::copyGeneric('CRM_Member_DAO_MembershipBlock',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ],
      ['entity_id' => $copy->id]
    );

    $copyUFJoin = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFJoin',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ],
      ['entity_id' => $copy->id]
    );

    $copyWidget = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_Widget',
      ['contribution_page_id' => $id],
      ['contribution_page_id' => $copy->id]
    );


    //copy option group and values

    $copy->default_amount_id = CRM_Core_BAO_OptionGroup::copyValue('contribution',
      $id,
      $copy->id,
      CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $id,
        'default_amount_id'
      )
    );
    $copyTellFriend = &CRM_Core_DAO::copyGeneric('CRM_Friend_DAO_Friend',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ],
      ['entity_id' => $copy->id]
    );

    $copyPersonalCampaignPages = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_PCPBlock',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ],
      ['entity_id' => $copy->id]
    );

    $copyPremium = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_Premium',
      ['entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ],
      ['entity_id' => $copy->id]
    );
    $premiumQuery = "        
SELECT id
FROM civicrm_premiums
WHERE entity_table = 'civicrm_contribution_page'
      AND entity_id ={$id}";

    $premiumDao = CRM_Core_DAO::executeQuery($premiumQuery, CRM_Core_DAO::$_nullArray);
    while ($premiumDao->fetch()) {
      if ($premiumDao->id) {
        $copyPremiumProduct = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_PremiumsProduct',
          ['premiums_id' => $premiumDao->id],
          ['premiums_id' => $copyPremium->id]
        );
      }
    }

    //copy custom data

    $extends = ['contributionPage'];
    $groupTree = CRM_Core_BAO_CustomGroup::getGroupDetail(NULL, NULL, $extends);
    if ($groupTree) {
      foreach ($groupTree as $groupID => $group) {
        $table[$groupTree[$groupID]['table_name']] = ['entity_id'];
        foreach ($group['fields'] as $fieldID => $field) {
          if ($field['data_type'] == 'File') {
            continue;
          }
          $table[$groupTree[$groupID]['table_name']][] = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
        }
      }

      foreach ($table as $tableName => $tableColumns) {
        $insert = 'INSERT INTO ' . $tableName . ' (' . CRM_Utils_Array::implode(', ', $tableColumns) . ') ';
        $tableColumns[0] = $copy->id;
        $select = 'SELECT ' . CRM_Utils_Array::implode(', ', $tableColumns);
        $from = ' FROM ' . $tableName;
        $where = " WHERE {$tableName}.entity_id = {$id}";
        $query = $insert . $select . $from . $where;
        $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      }
    }

    $copy->save();
    $copy->originId = $id;


    CRM_Utils_Hook::copy('ContributionPage', $copy);

    return $copy;
  }

  /**
   * Function to check if contribution page contains payment
   * processor that supports recurring payment
   *
   * @param int $contributionPageId Contribution Page Id
   *
   * @return boolean true if payment processor supports recurring
   *                 else false
   *
   * @access public
   * @static
   */
  static function checkRecurPaymentProcessor($contributionPageId) {
    //FIXME
    $sql = "
    SELECT pp.is_recur
    FROM   civicrm_contribution_page  cp,
           civicrm_payment_processor  pp
    WHERE  cp.payment_processor = pp.id
      AND  cp.id = {$contributionPageId}
  ";

    if ($recurring = &CRM_Core_DAO::singleValueQuery($sql, CRM_Core_DAO::$_nullArray)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to get info for all sections enable/disable.
   *
   * @return array $info info regarding all sections.
   * @access public
   */
  static function getSectionInfo($contribPageIds = []) {
    $info = [];
    $whereClause = NULL;
    if (is_array($contribPageIds) && !empty($contribPageIds)) {
      $whereClause = 'WHERE civicrm_contribution_page.id IN ( ' . CRM_Utils_Array::implode(', ', $contribPageIds) . ' )';
    }

    $sections = ['settings',
      'amount',
      'membership',
      'custom',
      'thankYou',
      'friend',
      'pcp',
      'widget',
      'premium',
    ];
    $query = "
   SELECT  civicrm_contribution_page.id as id,
           civicrm_contribution_page.contribution_type_id as settings, 
           amount_block_is_active as amount, 
           civicrm_membership_block.id as membership,
           civicrm_uf_join.id as custom,
           civicrm_contribution_page.thankyou_title as thankYou,
           civicrm_tell_friend.id as friend,
           civicrm_pcp_block.id as pcp,
           civicrm_contribution_widget.id as widget,
           civicrm_premiums.id as premium
     FROM  civicrm_contribution_page
LEFT JOIN  civicrm_membership_block    ON ( civicrm_membership_block.entity_id = civicrm_contribution_page.id
                                            AND civicrm_membership_block.entity_table = 'civicrm_contribution_page'
                                            AND civicrm_membership_block.is_active = 1 )
LEFT JOIN  civicrm_uf_join             ON ( civicrm_uf_join.entity_id = civicrm_contribution_page.id 
                                            AND civicrm_uf_join.entity_table = 'civicrm_contribution_page' 
                                            AND civicrm_uf_join.is_active = 1 )
LEFT JOIN  civicrm_tell_friend         ON ( civicrm_tell_friend.entity_id = civicrm_contribution_page.id 
                                            AND civicrm_tell_friend.entity_table = 'civicrm_contribution_page'
                                            AND civicrm_tell_friend.is_active = 1)
LEFT JOIN  civicrm_pcp_block           ON ( civicrm_pcp_block.entity_id = civicrm_contribution_page.id 
                                            AND civicrm_pcp_block.entity_table = 'civicrm_contribution_page' 
                                            AND civicrm_pcp_block.is_active = 1 ) 
LEFT JOIN  civicrm_contribution_widget ON ( civicrm_contribution_widget.contribution_page_id = civicrm_contribution_page.id 
                                            AND civicrm_contribution_widget.is_active = 1 )
LEFT JOIN  civicrm_premiums            ON ( civicrm_premiums.entity_id = civicrm_contribution_page.id 
                                            AND civicrm_premiums.entity_table = 'civicrm_contribution_page' 
                                            AND civicrm_premiums.premiums_active = 1 )
           $whereClause";

    $contributionPage = CRM_Core_DAO::executeQuery($query);
    while ($contributionPage->fetch()) {
      if (!empty($info[$contributionPage->id]) && !is_array($info[$contributionPage->id])) {
        $info[$contributionPage->id] = array_fill_keys(array_values($sections), FALSE);
      }
      foreach ($sections as $section) {
        if ($contributionPage->$section) {
          $info[$contributionPage->id][$section] = TRUE;
        }
      }
    }

    return $info;
  }

  
  /**
   * Function to get goal of contribution page
   *
   * @param int $contributionPageId Contribution Page Id
   *
   * @return array $achieved
   *   $type     amount or recurring
   *   $goal     goal of this contribution page
   *   $current  current amount or people
   *   $percent  current/goal percentage, 100 means achieved
   *   $achieved TRUE / FALSE to indicate if page is achieved goal
   *
   * @access public
   * @static
   */
  static function goalAchieved($contributionPageId) {
    $page = $params = $whereClause = [];
    CRM_Contribute_BAO_ContributionPage::setValues($contributionPageId, $page);
    $whereClause = [
      'c.contribution_page_id = %1',
      'c.contribution_status_id = 1',
      'c.is_test = 0',
    ];
    $params = [
      1 => [$contributionPageId, 'Integer'],
    ];
    // goal - recurring amount
    if ($page['goal_recurring'] === '0' && $page['goal_amount'] > 0) {
      $type = 'recuramount';
      $label = '';
      if ($page['recur_frequency_unit'] === 'month') {
        $label = ucfirst(ts('monthly'));
      }
      if ($page['recur_frequency_unit'] === 'year') {
        $label = ucfirst(ts('yearly'));
      }
      if (preg_match('/^[a-z]+$/i', $label)) {
        $label .= ' ';
      }
      $label .= ts('Goal Recurring Amount');
      $whereClause[] = "r.contribution_status_id = 5"; // In Progress 
      $where = CRM_Utils_Array::implode(" AND ", $whereClause);
      $sql = "SELECT SUM(amount) as `sum`, COUNT(id) as `count` FROM (SELECT r.id, r.amount FROM civicrm_contribution_recur r INNER JOIN civicrm_contribution c ON c.contribution_recur_id = r.id WHERE $where GROUP BY c.contribution_recur_id) rr";
      $goal = $page['goal_amount'];
    }
    // goal - amount
    elseif (!empty($page['goal_amount']) && $page['goal_amount'] > 0) {
      $type = 'amount';
      $label = ts('Goal Amount');
      $where = CRM_Utils_Array::implode(" AND ", $whereClause);
      $sql = "SELECT SUM(c.total_amount) as `sum`, COUNT(id) as `count` FROM civicrm_contribution c WHERE $where GROUP BY c.contribution_page_id";
      $goal = $page['goal_amount'];
    }
    // goal - recurring people
    elseif (!empty($page['goal_recurring']) && $page['goal_recurring'] > 0) {
      $type = 'recurring';
      $label = ts('Goal Subscription');
      $whereClause[] = "r.contribution_status_id not in (3,7)";
      $where = CRM_Utils_Array::implode(" AND ", $whereClause);
      $sql = "SELECT SUM(subscription.total_amount) as `sum`, COUNT(subscription.id) as `count` FROM (SELECT c.total_amount, c.id FROM civicrm_contribution c INNER JOIN civicrm_contribution_recur r ON c.contribution_recur_id = r.id WHERE $where GROUP BY r.id) as subscription";
      $goal = $page['goal_recurring'];
    }

    if ($type) {
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      $dao->fetch();
      $current = strstr($type, 'amount') ? $dao->sum : $dao->count;
      $percent = round(ceil(($current/$goal)*100));
      if ($current > 0 && $percent < 1) {
        $percent = 1; // when there is value, we have at least 1 percent
      }
      return [
        'type' => $type,
        'label' => $label,
        'goal' => $goal,
        'current' => !empty($current) ? $current : 0,
        'percent' => $percent,
        'achieved' => $percent >= 100 ? TRUE : FALSE,
        'count' => !empty($dao->count) ? $dao->count : 0,
        'sum' => !empty($dao->sum) ? $dao->sum : 0,
      ];
    }
    return [];
  }

  static function tokenize($contactId, $input, $contributionId = NULL) {
    $output = $input;
    $tokens = CRM_Utils_Token::getTokens($input);
    $contactParams = ['contact_id' => $contactId];
    // Prepare $detail for contact, contribution properties array
    foreach ($tokens as $component => $usedToken) {
      if (isset($usedToken)) {
        if ($component == 'contact') {
          $returnProperties = [];
          foreach ($tokens['contact'] as $name) {
            $returnProperties[$name] = 1;
          }
          list($contact) = CRM_Mailing_BAO_Mailing::getDetails($contactParams, $returnProperties, FALSE);
          $contact = $contact[$contactId];
          $detail['contact'] = $contact;
        }
        if ($component == 'contribution' && !empty($contributionId)) {
          $contactParams['contribution_id'] = $contributionId;
          $returnProperties = [];
          foreach ($tokens['contribution'] as $name) {
            $returnProperties[$name] = 1;
          }
          $ids = $params = ['contribution_id' => $contributionId];
          CRM_Contribute_BAO_Contribution::getValues($params, $contribution, $ids);
          $detail['contribution'] = $contribution;
        }
      }
    }

    // Hook for contact, contribution properties of 'tokenValues' function
    CRM_Utils_Hook::tokenValues($detail, $contactParams, NULL, $tokens, 'CRM_Contribution_BAO_ContributionPage');

    // Applied properties to tokens
    foreach ($tokens as $component => $usedToken) {
      if (isset($usedToken)) {
        if ($component == 'contact') {
          $output = CRM_Utils_Token::replaceContactTokens($output, $detail['contact'], FALSE, $tokens, FALSE, TRUE);
        }
        if ($component == 'domain') {
          $domain = CRM_Core_BAO_Domain::getDomain();
          $output = CRM_Utils_Token::replaceDomainTokens($output, $domain, TRUE, $tokens, FALSE);
        }
        if ($component == 'contribution' && !empty($contributionId)) {
          $output = CRM_Utils_Token::replaceContributionTokens($output, $detail['contribution'], FALSE, $tokens, FALSE, TRUE);
        }
      }
    }

    return $output;
  }

  public static function feeBlock($pageId) {
    $feeBlock = [];
    if ($priceSetId = CRM_Price_BAO_Set::getFor('civicrm_contribution_page', $pageId)) {
      $feeBlock['price_set_id'] = $priceSetId;
      $setDetails = CRM_Price_BAO_Set::getSetDetail($priceSetId);
      $priceSetFields = $setDetails[$priceSetId]['fields'];
      if (is_array($priceSetFields)) {
        $fieldCnt = 1;
        $visibility = CRM_Core_PseudoConstant::visibility('name');

        foreach ($priceSetFields as $fid => $fieldValues) {
          if (!is_array($fieldValues['options']) ||
            empty($fieldValues['options']) ||
            CRM_Utils_Array::value('visibility_id', $fieldValues) != array_search('public', $visibility)
          ) {
            continue;
          }

          if (count($fieldValues['options']) > 1) {
            $feeBlock['value'][$fieldCnt] = '';
            $feeBlock['label'][$fieldCnt] = $fieldValues['label'];
            $fieldCnt++;
          }

          foreach ($fieldValues['options'] as $optionId => $optionVal) {
            $feeBlock['value'][$fieldCnt] = $optionVal['amount'];
            $feeBlock['label'][$fieldCnt] = $optionVal['label'];
            $fieldCnt++;
          }
        }
      }
    }
    else {
      CRM_Core_OptionGroup::getAssoc("civicrm_contribution_page.amount.{$pageId}", $feeBlock);
    }
    return $feeBlock;
  }
}

