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

require_once 'Mail/mime.php';
require_once 'CRM/Core/DAO/MessageTemplates.php';
class CRM_Core_BAO_MessageTemplates extends CRM_Core_DAO_MessageTemplates {

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Core_BAO_MessageTemplates object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    $messageTemplates->copyValues($params);
    if ($messageTemplates->find(TRUE)) {
      CRM_Core_DAO::storeValues($messageTemplates, $defaults);
      return $messageTemplates;
    }
    return NULL;
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
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_MessageTemplates', $id, 'is_active', $is_active);
  }

  /**
   * function to add the Message Templates
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);

    $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    $messageTemplates->copyValues($params);

    $messageTemplates->save();
    return $messageTemplates;
  }

  /**
   * function to delete the Message Templates
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function del($messageTemplatesID) {
    // make sure messageTemplatesID is an integer
    if (!CRM_Utils_Rule::positiveInteger($messageTemplatesID)) {
       return CRM_Core_Error::statusBounce(ts('Invalid Message template'));
    }

    // set membership_type to null
    $query = "UPDATE civicrm_membership_type
                  SET renewal_msg_id = NULL
                  WHERE renewal_msg_id = %1";
    $params = array(1 => array($messageTemplatesID, 'Integer'));
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "UPDATE civicrm_mailing
                  SET msg_template_id = NULL
                  WHERE msg_template_id = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    $messageTemplates->id = $messageTemplatesID;
    $messageTemplates->delete();
    CRM_Core_Session::setStatus(ts('Selected message templates has been deleted.'));
  }

  /**
   * function to get the Message Templates
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function getMessageTemplates($all = TRUE, $isSMS = FALSE) {
    $msgTpls = array();

    $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    $messageTemplates->is_active = 1;
    if($isSMS){
      $messageTemplates->is_sms = $isSMS;
    }else{
      unset($messageTemplates->is_sms);
    }

    if (!$all) {
      $messageTemplates->workflow_id = 'NULL';
    }
    $messageTemplates->find();
    while ($messageTemplates->fetch()) {
      $msgTpls[$messageTemplates->id] = $messageTemplates->msg_title;
    }
    asort($msgTpls);
    return $msgTpls;
  }

  /**
   * Get message template by specify workflow
   *
   * @param string $groupName workflow group name from option group
   * @param string $valueName workflow value name option value
   * @return array
   */
  static function getMessageTemplateByWorkflow($groupName, $valueName) {
    static $cache;
    if (!empty($cache[$groupName.'__'.$valueName])) {
      return $cache[$groupName.'__'.$valueName];
    }
    $return = array();
    $query = 'SELECT mt.msg_title, mt.msg_subject, mt.msg_text, mt.msg_html
                  FROM civicrm_msg_template mt
                  JOIN civicrm_option_value ov ON workflow_id = ov.id
                  JOIN civicrm_option_group og ON ov.option_group_id = og.id
                  WHERE og.name = %1 AND ov.name = %2 AND mt.is_default = 1';
    $sqlParams = array(1 => array($groupName, 'String'), 2 => array($valueName, 'String'));
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    $dao->fetch();
    if ($dao->N) {
      $return = array(
        'msg_title' => $dao->msg_title,
        'msg_subject' => $dao->msg_subject,
        'msg_text' => $dao->msg_text,
        'msg_html' => $dao->msg_html,
      );
    }
    $dao->free();
    $cache[$groupName.'__'.$valueName] = $return;
    return $cache[$groupName.'__'.$valueName];
  }

  static function sendReminder($contactId, $email, $messageTemplateID, $from) {
    $messageTemplates = new CRM_Core_DAO_MessageTemplates();
    $messageTemplates->id = $messageTemplateID;

    $domain = CRM_Core_BAO_Domain::getDomain();
    $result = NULL;
    $hookTokens = array();

    if ($messageTemplates->find(TRUE)) {
      $body_text = $messageTemplates->msg_text;
      $body_html = $messageTemplates->msg_html;
      $body_subject = $messageTemplates->msg_subject;
      if (!$body_text) {
        $body_text = CRM_Utils_String::htmlToText($body_html);
      }
      $mailing = new CRM_Mailing_BAO_Mailing;
      $mailing->subject = $body_subject;
      $mailing->body_text = $body_text;
      $mailing->body_html = $body_html;
      $tokens = $mailing->getTokens();
      CRM_Utils_Hook::tokens($hookTokens);
      $categories = array_keys($hookTokens);

      $contactParams = array('contact_id' => $contactId);
      $returnProperties = array();

      if (isset($tokens['text']['contact'])) {
        foreach ($tokens['text']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }

      if (isset($tokens['html']['contact'])) {
        foreach ($tokens['html']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }

      list($contact) = $mailing->getDetails($contactParams, $returnProperties, FALSE);
      $contact = $contact[$contactId];

      $types = array('subject', 'body_html', 'body_text');

      foreach ($types as $key) {
        $value = str_replace('body_', '', $key);
        if (!empty($mailing->$key)) {
          $mailing->$key = CRM_Utils_Token::replaceDomainTokens($mailing->$key, $domain, TRUE, $tokens[$value], TRUE);
          $mailing->$key = CRM_Utils_Token::replaceContactTokens($mailing->$key, $contact, FALSE, $tokens[$value], FALSE, TRUE);
          $mailing->$key = CRM_Utils_Token::replaceComponentTokens($mailing->$key, $contact, $tokens[$value], TRUE);
          $mailing->$key = CRM_Utils_Token::replaceHookTokens($mailing->$key, $contactId, $categories, TRUE);
        }
      }

      $subject = "{strip}{$mailing->subject}{/strip}";
      $html = $mailing->body_html;
      $text = $mailing->body_text;

      require_once 'CRM/Core/Smarty/resources/String.php';
      civicrm_smarty_register_string_resource();
      $smarty = &CRM_Core_Smarty::singleton();
      foreach (array('subject', 'text', 'html') as $elem) {
        $$elem = $smarty->fetch("string:{*msg_tpl-$messageTemplateID-$elem*}{$$elem}");
      }

      $sent = FALSE;
      $params = array();
      $params['subject'] = $subject;
      $params['text'] = $text;
      $params['html'] = $html;
      $params['mailerType'] = array_search('Transaction Notification', CRM_Core_BAO_MailSettings::$_mailerTypes);
      $params['from'] = $from;
      $params['toName'] = $contact['display_name'];
      $params['toEmail'] = $email;
      $sent = CRM_Utils_Mail::send($params);
    }

    // free memory
    $messageTemplates->free();
    unset($contact);
    unset($domain);
    unset($mailing);

    return $sent;
  }

  /**
   * Revert a message template to its default subject+text+HTML state
   *
   * @param integer id  id of the template
   *
   * @return void
   */
  static function revert($id) {
    $diverted = new self;
    $diverted->id = (int) $id;
    $diverted->find(1);

    if ($diverted->N != 1) {
       return CRM_Core_Error::statusBounce(ts('Did not find a message template with id of %1.', array(1 => $id)));
    }

    $orig = new self;
    $orig->workflow_id = $diverted->workflow_id;
    $orig->is_reserved = 1;
    $orig->find(1);

    if ($orig->N != 1) {
      CRM_Core_Error::fatal(ts('Message template with id of %1 does not have a default to revert to.', array(1 => $id)));
    }

    $diverted->msg_subject = $orig->msg_subject;
    $diverted->msg_text = $orig->msg_text;
    $diverted->msg_html = $orig->msg_html;
    $diverted->save();
  }

  /**
   * Send an email from the specified template based on an array of params
   *
   * @param array $params  a string-keyed array of function params, see function body for details
   * @param object &$smarty passed by reference smarty object. Will be used when multiple call of sendTemplate in a loop
   * @param array $callback array first element is for success callback, second is for error callback
   *   ```
   *   $callback = [
   *     0 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' => [ // this is for success
   *       $activityId,
   *       TRUE,
   *     ]],
   *     1 => ['CRM_Activity_BAO_Activity::updateTransactionalStatus' => [ // this is for error
   *       $activityId,
   *       FALSE,
   *     ]],
   *   ];
   *   ```
   *
   * @return array  of four parameters: a boolean whether the email was sent, and the subject, text and HTML templates
   */
  static function sendTemplate($params, &$smarty = NULL, $callback = NULL) {
    $defaults = array(
      // activity id for use transactional email
      'activityId' => NULL,
      // option group name of the template
      'groupName' => NULL,
      // option value name of the template
      'valueName' => NULL,
      // contact id if contact tokens are to be replaced
      'contactId' => NULL,
      // additional template params (other than the ones already set in the template singleton)
      'tplParams' => array(),
      // the From: header
      'from' => NULL,
      // the recipient’s name
      'toName' => NULL,
      // the recipient’s email - mail is sent only if set
      'toEmail' => NULL,
      // the Cc: header
      'cc' => NULL,
      // the Bcc: header
      'bcc' => NULL,
      // the Reply-To: header
      'replyTo' => NULL,
      // email attachments
      'attachments' => NULL,
      // whether this is a test email (and hence should include the test banner)
      'isTest' => FALSE,
      // filename of optional PDF version to add as attachment (do not include path)
      'PDFFilename' => NULL,
    );
    $params = array_merge($defaults, $params);

    if (!$params['groupName'] or !$params['valueName']) {
      CRM_Core_Error::fatal(ts("Message template's option group and/or option value missing."));
    }

    $msg = self::getMessageTemplateByWorkflow($params['groupName'], $params['valueName']);
    $subject = $msg['msg_subject'];
    $text = $msg['msg_text'];
    $html = $msg['msg_html'];

    // add the test banner (if requested)
    if ($params['isTest']) {
      $meta = self::getMessageTemplateByWorkflow('msg_tpl_workflow_meta', 'test_preview');
      $subject = $meta['msg_subject'] . $subject;
      $text = $meta['msg_text'] . $text;
      $html = preg_replace('/<body(.*)$/im', "<body\\1\n{$meta['msg_html']}", $html);
    }

    // add receipt email encryption block
    $config = CRM_Core_Config::singleton();
    if ($config->receiptEmailEncryption) {
      $defaultMsg = "請輸入身分證字號或Email地址開啟您的收據。";
      if (empty($config->receiptEmailEncryptionText)) {
        $msg = $defaultMsg;
      } else {
        $msg = $config->receiptEmailEncryptionText;
      }
      $target_text = "{if \$formValues.receipt_text}";
      $html  = str_replace($target_text, $msg.$target_text, $html);
    }

    // replace tokens in the three elements (in subject as if it was the text body)

    $domain = CRM_Core_BAO_Domain::getDomain();
    $hookTokens = array();
    $mailing = new CRM_Mailing_BAO_Mailing;
    $mailing->body_text = $text;
    $mailing->body_html = $html;
    $tokens = $mailing->getTokens();
    CRM_Utils_Hook::tokens($hookTokens);
    $categories = array_keys($hookTokens);

    $contactID = CRM_Utils_Array::value('contactId', $params);

    if ($contactID) {
      $contactParams = array('contact_id' => $params['contactId']);
      $returnProperties = array();

      if (isset($tokens['text']['contact'])) {
        foreach ($tokens['text']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }

      if (isset($tokens['html']['contact'])) {
        foreach ($tokens['html']['contact'] as $name) {
          $returnProperties[$name] = 1;
        }
      }
      list($contact) = $mailing->getDetails($contactParams, $returnProperties, FALSE);
      $contact = $contact[$params['contactId']];
    }

    $subject = CRM_Utils_Token::replaceDomainTokens($subject, $domain, TRUE, $tokens['text'], TRUE);
    $text = CRM_Utils_Token::replaceDomainTokens($text, $domain, TRUE, $tokens['text'], TRUE);
    $html = CRM_Utils_Token::replaceDomainTokens($html, $domain, TRUE, $tokens['html'], TRUE);
    if ($contactID) {
      $subject = CRM_Utils_Token::replaceContactTokens($subject, $contact, FALSE, $tokens['text'], FALSE, TRUE);
      $text = CRM_Utils_Token::replaceContactTokens($text, $contact, FALSE, $tokens['text'], FALSE, TRUE);
      $html = CRM_Utils_Token::replaceContactTokens($html, $contact, FALSE, $tokens['html'], FALSE, TRUE);

      $contactArray = array($contactID => $contact);
      $contactIDArray = array($contactID);
      CRM_Utils_Hook::tokenValues($contactArray,
        $contactIDArray,
        NULL,
        CRM_Utils_Token::flattenTokens($tokens),
        'CRM_Core_BAO_MessageTemplate'
      );
      $contact = $contactArray[$contactID];

      $subject = CRM_Utils_Token::replaceHookTokens($subject, $contact[$params['contactId']], $categories, TRUE);
      $text = CRM_Utils_Token::replaceHookTokens($text, $contact[$params['contactId']], $categories, TRUE);
      $html = CRM_Utils_Token::replaceHookTokens($html, $contact[$params['contactId']], $categories, TRUE);
    }

    // free memory
    unset($contact);
    unset($domain);
    unset($mailing);

    // strip whitespace from ends and turn into a single line
    $subject = "{strip}$subject{/strip}";

    // parse the three elements with Smarty
    require_once 'CRM/Core/Smarty/resources/String.php';
    if (empty($smarty)) {
      $smarty = &CRM_Core_Smarty::singleton();
    }
    civicrm_smarty_register_string_resource($smarty);
    if (is_array($params['tplParams'])) {
      foreach ($params['tplParams'] as $name => $value) {
        $smarty->assign($name, $value);
      }
    }
    foreach (array('subject', 'text', 'html') as $elem) {
      $$elem = $smarty->fetch("string:{*".$params['groupName']."-".$params['valueName'].'-'.$elem."*}{$$elem}");
    }

    // send the template, honouring the target user’s preferences (if any)
    $sent = FALSE;

    // create the params array
    $params['subject'] = $subject;
    $params['text'] = $text;
    $params['html'] = $html;

    if ($params['toEmail']) {
      $contactParams = array( 
        'email' => $params['toEmail'],
        'version' => 3,
      );
      $result = civicrm_api('contact', 'get', $contactParams);
      $prefs = !empty($result['values']) ? array_pop($result['values']) : array();

      if (isset($prefs['preferred_mail_format']) and $prefs['preferred_mail_format'] == 'HTML') {
        $params['text'] = NULL;
      }

      if (isset($prefs['preferred_mail_format']) and $prefs['preferred_mail_format'] == 'Text') {
        $params['html'] = NULL;
      }

      $config = CRM_Core_Config::singleton();
      $pdf_filename = '';
      if (!$config->doNotAttachPDFReceipt && $params['PDFFilename'] && $params['html']) {
        $pdf_filename =  CRM_Utils_PDF_Utils::html2pdf($params['html'], 'pdf_'.microtime().'.pdf', NULL, NULL, FALSE);

        if (empty($params['attachments'])) {
          $params['attachments'] = array();
        }
        $params['attachments'][] = array(
          'fullPath' => $pdf_filename,
          'mime_type' => 'application/pdf',
          'cleanName' => $params['PDFFilename'],
        );
      }

      $params['mailerType'] = array_search('Transaction Notification', CRM_Core_BAO_MailSettings::$_mailerTypes);
      if (!empty($params['activityId']) && $config->enableTransactionalEmail) {
        $activityTypeId = CRM_Core_DAO::getFieldValue('CRM_Activity_DAO_Activity', $params['activityId'], 'activity_type_id');
        if(in_array(CRM_Core_OptionGroup::getName('activity_type', $activityTypeId), explode(',', CRM_Mailing_BAO_Transactional::ALLOWED_ACTIVITY_TYPES))) {
          $sent = CRM_Mailing_BAO_Transactional::send($params, $callback);
        }
      }
      else {
        $sent = CRM_Utils_Mail::send($params, $callback);
      }

      if ($pdf_filename) {
        unlink($pdf_filename);
      }
    }

    // CRM_Core_Error::debug(CRM_Utils_System::memory('end')); // memory leak detection
    return array($sent, $subject, $text, $html);
  }

  /**
   * Get workflow group name / value name by workflow id
   *
   * @param int $workflow_id workflow id of message template
   * @return array
   */
  static function getMessageTemplateNames($workflowId) {
    $query = 'SELECT ov.name as groupName, og.name as valueName
                  FROM civicrm_msg_template mt
                  INNER JOIN civicrm_option_value ov ON workflow_id = ov.id
                  INNER JOIN civicrm_option_group og ON ov.option_group_id = og.id
                  WHERE ov.id = %1 AND mt.is_default = 1';
    $dao = CRM_Core_DAO::executeQuery($query, array(1 => array($workflowId, 'Integer')));
    $dao->fetch();
    if ($dao->N) {
      return array(
        'groupName' => $dao->groupName,
        'valueName' => $dao->valueName,
      );
    }
    return array();
  }
}

