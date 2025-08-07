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
 * This class provides the common functionality for sending email to
 * one or a group of contact ids. This class is reused by all the search
 * components in CiviCRM (since they all have send email as a task)
 */
class CRM_Contact_Form_Task_EmailCommon {
  CONST MAX_EMAILS_KILL_SWITCH = 50;

  public $_contactDetails = [];
  public $_allContactDetails = [];
  public $_toContactEmails = [];

  static function preProcessFromAddress(&$form) {
    $form->_single = FALSE;
    $className = CRM_Utils_System::getClassName($form);
    if ($form->_context != 'search' &&
      $className == 'CRM_Contact_Form_Task_Email'
    ) {
      $form->_single = TRUE;
    }

    $form->_emails = $emails = [];
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    $form->_contactIds = [$contactID];

    $fromEmails = CRM_Contact_BAO_Contact_Utils::fromEmailAddress($contactID);

    foreach($fromEmails as $emailType => $emails) {
      if (!empty($emails)) {
        array_keys($emails);
        foreach($emails as $header => $display) {
          $form->_emails[$header] = $header;
        }
      }
    }
    if (empty($form->_emails)) {
      $form->assign('noEmails', TRUE);
      return CRM_Core_Error::statusBounce(ts('Your user record does not have a valid email address'));
    }
    $selectableEmails = [];
    if (!empty($fromEmails['system'])) {
      $selectableEmails[ts('Default').' '.ts('(built-in)')] = $fromEmails['system'];
    }
    $selectableEmails[ts('Default')] = $fromEmails['default'];
    if (!empty($fromEmails['contact'])) {
      $selectableEmails[ts('Your Email')] = $fromEmails['contact'];
    }
    else {
      $form->assign('show_spf_dkim_notice', TRUE);
    }
    if (!empty($fromEmails['domain'])) {
      $selectableEmails[ts('Other')] = $fromEmails['domain'];
    }
    $form->_fromEmails = $selectableEmails;
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  static function buildQuickForm(&$form) {
    $toArray = $ccArray = $bccArray = [];
    $suppressedEmails = 0;
    //here we are getting logged in user id as array but we need target contact id. CRM-5988
    $cid = $form->get('cid');
    if ($cid) {
      $form->_contactIds = [$cid];
    }

    $to = $form->add('text', 'to', ts('To'), '', TRUE);
    $cc = $form->add('text', 'cc_id', ts('CC'));
    $bcc = $form->add('text', 'bcc_id', ts('BCC'));

    $elements = ['cc', 'bcc'];
    foreach ($elements as $element) {
      if ($$element->getValue()) {
        preg_match_all('!"(.*?)"\s+<\s*(.*?)\s*>!', $$element->getValue(), $matches);
        $elementValues = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
          $name = '"' . $matches[1][$i] . '" &lt;' . $matches[2][$i] . '&gt;';
          $elementValues[] = [
            'name' => $name,
            'id' => $matches[0][$i],
          ];
        }

        $var = "{$element}Contact";
        $form->assign($var, json_encode($elementValues));
      }
    }

    $toSetDefault = TRUE;
    if ($form->_context == 'standalone') {
      $toSetDefault = FALSE;
    }
    // when form is submitted recompute contactIds
    $allToEmails = [];
    if ($to->getValue()) {
      $allToEmails = explode(',', $to->getValue());
      $form->_contactIds = [];
      foreach ($allToEmails as $value) {
        list($contactId, $email) = explode('::', $value);
        if ($contactId) {
          $form->_contactIds[$contactId] = $contactId;
          $form->_toContactEmails[$contactId] = $email;
        }
      }
      $toSetDefault = TRUE;
    }

    //get the group of contacts as per selected by user in case of Find Activities
    if (!empty($form->_activityHolderIds)) {
      $contact = $form->get('contacts');
      $form->_contactIds = $contact;
    }

    if (is_array($form->_contactIds) && $toSetDefault) {
      $returnProperties = ['sort_name' => 1,
        'email' => 1,
        'do_not_email' => 1,
        'is_deceased' => 1,
        'on_hold' => 1,
        'display_name' => 1,
        'preferred_mail_format' => 1,
      ];



      list($form->_contactDetails) = CRM_Mailing_BAO_Mailing::getDetails($form->_contactIds, $returnProperties, FALSE, FALSE);

      // make a copy of all contact details
      $form->_allContactDetails = $form->_contactDetails;

      foreach ($form->_contactIds as $contactId) {
        $value = $form->_contactDetails[$contactId];
        if ($value['do_not_email'] || empty($value['email']) || CRM_Utils_Array::value('is_deceased', $value) || $value['on_hold']) {
          $suppressedEmails++;

          // unset contact details for contacts that we won't be sending email. This is prevent extra computation
          // during token evaluation etc.
          unset($form->_contactDetails[$contactId]);
          unset($form->_toContactEmails[$contactId]);
        }
        else {
          if (empty($form->_toContactEmails)) {
            $email = $value['email'];
          }
          else {
            $email = $form->_toContactEmails[$contactId];
          }
          $toArray[] = ['name' => '"' . $value['sort_name'] . '" &lt;' . $email . '&gt;',
            'id' => "$contactId::{$email}",
          ];
        }
      }

      if (empty($toArray)) {
         return CRM_Core_Error::statusBounce(ts('Selected contact(s) do not have a valid email address, or communication preferences specify DO NOT EMAIL, or they are deceased or Primary email address is On Hold.'));
      }
    }

    $form->assign('toContact', json_encode($toArray));
    $form->assign('suppressedEmails', $suppressedEmails);

    $form->assign('totalSelectedContacts', count($form->_contactIds));

    $form->add('text', 'subject', ts('Subject'), 'size=50 maxlength=254', TRUE);

    $form->add('select', 'fromEmailAddress', ts('From'), $form->_fromEmails, TRUE);


    CRM_Mailing_BAO_Mailing::commonCompose($form);

    // add attachments

    CRM_Core_BAO_File::buildAttachment($form, NULL);

    if ($form->_single) {
      // also fix the user context stack
      if ($form->_caseId) {
        $ccid = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_CaseContact', $form->_caseId,
          'contact_id', 'case_id'
        );
        $url = CRM_Utils_System::url('civicrm/contact/view/case',
          "&reset=1&action=view&cid={$ccid}&id={$form->_caseId}"
        );
      }
      elseif ($form->_context) {
        $url = CRM_Utils_System::url('civicrm/dashboard', 'reset=1');
      }
      else {
        $contactId = reset($form->_contactIds);
        $url = CRM_Utils_System::url('civicrm/contact/view', "cid={$contactId}&selectedChild=activity");
      }

      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext($url);
      $form->addDefaultButtons(ts('Send Email'), 'upload', 'cancel');
    }
    else {
      $form->addDefaultButtons(ts('Send Email'), 'upload');
    }

    $form->addFormRule(['CRM_Contact_Form_Task_EmailCommon', 'formRule'], $form);
  }

  /**
   * form rule
   *
   * @param array $fields    the input form values
   * @param array $dontCare
   * @param array $self      additional values form 'this'
   *
   * @return true if no errors, else array of errors
   * @access public
   *
   */
  static function formRule($fields, $dontCare, $self) {
    $errors = [];
    $template = CRM_Core_Smarty::singleton();

    if (isset($fields['html_message'])) {
      $htmlMessage = str_replace(["\n", "\r"], ' ', $fields['html_message']);
      $htmlMessage = str_replace('"', '\"', $htmlMessage);
      $template->assign('htmlContent', $htmlMessage);
    }

    //Added for CRM-1393
    if (CRM_Utils_Array::value('saveTemplate', $fields) && empty($fields['saveTemplateName'])) {
      $errors['saveTemplateName'] = ts("Enter name to save message template");
    }
    if (CRM_Utils_Array::value('updateTemplate', $fields) && empty($fields['template'])) {
      $errors['template'] = ts("You need to specify a template to update.");
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  static function postProcess(&$form) {
    if (count($form->_contactIds) > self::MAX_EMAILS_KILL_SWITCH) {
      return CRM_Core_Error::statusBounce(ts('Please do not use this task to send a lot of emails (greater than %1). We recommend using CiviMail instead.',
          [1 => self::MAX_EMAILS_KILL_SWITCH]
        ));
    }

    // check and ensure that
    $formValues = $form->controller->exportValues($form->getName());

    $fromEmail = $formValues['fromEmailAddress'];
    $from = CRM_Utils_Array::value($fromEmail, $form->_emails);
    $cc = CRM_Utils_Array::value('cc_id', $formValues);
    $bcc = CRM_Utils_Array::value('bcc_id', $formValues);
    $subject = $formValues['subject'];

    // CRM-5916: prepend case id hash to CiviCase-originating emails’ subjects
    if ($form->_caseId) {
      $hash = substr(sha1(CIVICRM_SITE_KEY . $form->_caseId), 0, 7);
      $subject = "[case #$hash] $subject";
    }

    // process message template

    if (CRM_Utils_Array::value('saveTemplate', $formValues) || CRM_Utils_Array::value('updateTemplate', $formValues)) {
      $messageTemplate = ['msg_text' => $formValues['text_message'],
        'msg_html' => $formValues['html_message'],
        'msg_subject' => $formValues['subject'],
        'is_active' => TRUE,
      ];

      if ($formValues['saveTemplate']) {
        $messageTemplate['msg_title'] = $formValues['saveTemplateName'];
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }

      if ($formValues['template'] && $formValues['updateTemplate']) {
        $messageTemplate['id'] = $formValues['template'];
        unset($messageTemplate['msg_title']);
        CRM_Core_BAO_MessageTemplates::add($messageTemplate);
      }
    }

    $attachments = [];
    CRM_Core_BAO_File::formatAttachment($formValues,
      $attachments,
      NULL, NULL
    );

    // format contact details array to handle multiple emails from same contact
    $formattedContactDetails = [];
    $tempEmails = [];

    foreach ($form->_contactIds as $contactId) {
      $email = $form->_toContactEmails[$contactId];
      if (is_numeric($contactId) && !empty($contactId) && !empty($email)) {
        // prevent duplicate emails if same email address is selected CRM-4067
        // we should allow same emails for different contacts
        $emailKey = "{$contactId}::{$email}";
        if (!in_array($emailKey, $tempEmails)) {
          $tempEmails[] = $emailKey;
          $details = $form->_contactDetails[$contactId];
          $details['email'] = $email;
          unset($details['email_id']);
          $formattedContactDetails[] = $details;
        }
      }
    }

    // send the mail
    if (CRM_Core_Config::singleton()->enableTransactionalEmail) {
      foreach($formattedContactDetails as $details) {
        $detailsParam = [$details];
        list($sent, $activityId) = CRM_Activity_BAO_Activity::sendEmail(
          $detailsParam,
          $subject,
          $formValues['text_message'],
          $formValues['html_message'],
          NULL,
          NULL,
          $from,
          $attachments,
          $cc,
          $bcc,
          array_keys($form->_contactDetails),
          $form
        );
      }
      $status = ['', ts('Your message has been scheduled to send.')];
    }
    else {
      list($sent, $activityId) = CRM_Activity_BAO_Activity::sendEmail(
        $formattedContactDetails,
        $subject,
        $formValues['text_message'],
        $formValues['html_message'],
        NULL,
        NULL,
        $from,
        $attachments,
        $cc,
        $bcc,
        array_keys($form->_contactDetails),
        $form
      );

      if ($sent) {
        $status = ['', ts('Your message has been sent.')];
      }
    }

    //Display the name and number of contacts for those email is not sent.
    $emailsNotSent = array_diff_assoc($form->_allContactDetails, $form->_contactDetails);

    if (!empty($emailsNotSent)) {
      $statusOnHold = '';
      $statusDisplay = ts('Email not sent to contact(s) (no email address on file or communication preferences specify DO NOT EMAIL or Contact is deceased or Primary email address is On Hold): %1', [1 => count($emailsNotSent)]) . '<br />' . ts('Details') . ': ';
      foreach ($emailsNotSent as $contactId => $values) {
        $displayName = $values['display_name'];
        $email = $values['email'];
        $contactViewUrl = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$contactId}");
        $statusDisplay .= "<a href='{$contactViewUrl}'>{$displayName}</a>, ";

        // build separate status for on hold messages
        if ($values['on_hold']) {
          $statusOnHold .= ts('Email was not sent to %1 because primary email address (%2) is On Hold.',
            [1 => "<a href='{$contactViewUrl}'>{$displayName}</a>", 2 => "<strong>{$email}</strong>"]
          ) . '<br />';
        }
      }
      $status[] = $statusDisplay;
    }

    if ($form->_caseId) {
      // if case-id is found in the url, create case activity record
      $caseParams = ['activity_id' => $activityId,
        'case_id' => $form->_caseId,
      ];

      CRM_Case_BAO_Case::processCaseActivity($caseParams);
    }

    if (strlen($statusOnHold)) {
      $status[] = $statusOnHold;
    }

    CRM_Core_Session::setStatus($status);
  }
  //end of function
}

