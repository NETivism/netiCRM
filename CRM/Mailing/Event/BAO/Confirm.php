<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

class CRM_Mailing_Event_BAO_Confirm extends CRM_Mailing_Event_DAO_Confirm {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Confirm a pending subscription
   *
   * @param int $contact_id       The id of the contact
   * @param int $subscribe_id     The id of the subscription event
   * @param string $hash          The hash
   *
   * @return boolean              True on success
   * @access public
   * @static
   */
  public static function confirm($contact_id, $subscribe_id, $hash) {
    $se = CRM_Mailing_Event_BAO_Subscribe::verify($contact_id, $subscribe_id, $hash);

    if (!$se) {
      return FALSE;
    }

    // check if subscribtion exists (status is added)
    $config = CRM_Core_Config::singleton();
    $domain = CRM_Core_BAO_Domain::getDomain();
    list($domainEmailName, $_) = CRM_Core_BAO_Domain::getNameAndEmail();
    $allEmail = CRM_Core_BAO_Email::allEmails($contact_id);
    $defaultEmail = '';
    foreach($allEmail as $m) {
      if ($m['is_primary']) {
        $defaultEmail = $m['is_primary'];
      }
      if ($m['is_bulkmail']) {
        $email = $m['email'];
        break;
      }
    }
    if (empty($email)) {
      $email = $defaultEmail;
    }

    $group = new CRM_Contact_DAO_Group();
    $group->id = $se->group_id;
    $group->find(TRUE);
    $contactGroups = CRM_Mailing_Event_BAO_Subscribe::getContactGroups($email, $contact_id);
    if ($contactGroups[$group->id]['status'] == 'Added') {
      return $group->title;
    }

    $transaction = new CRM_Core_Transaction();

    $ce = new CRM_Mailing_Event_BAO_Confirm();
    $ce->event_subscribe_id = $se->id;
    $ce->time_stamp = date('YmdHis');
    $ce->save();

    CRM_Contact_BAO_GroupContact::updateGroupMembershipStatus($contact_id, $se->group_id, 'Email', $ce->id);

    $transaction->commit();

    // remove opt-out and freezed email 
    $params = ['id' => $contact_id];
    $contact = [];
    CRM_Contact_BAO_Contact::retrieve($params, $contact);
    if ($contact['is_opt_out']) {
      $params = [
        'contact_id' => $contact_id,
        'contact_type' => $contact['contact_type'],
        'is_opt_out' => 0,
        'log_data' => ts('Opt-in').' ('.ts('Re-subscribe Confirmation').')',
      ];
      CRM_Contact_BAO_Contact::create($params);
    }
    $emailOnHold = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_email WHERE contact_id = %1 AND is_bulkmail = 1 AND on_hold = 1", [
      1 => [$contact_id, 'Positive'],      
    ]);
    if ($emailOnHold) {
      CRM_Core_DAO::executeQuery("UPDATE civicrm_email SET on_hold = 0 WHERE id = %1", [
        1 => [$emailOnHold, 'Positive'],      
      ]);
    }


    $component = new CRM_Mailing_BAO_Component();
    $component->is_default = 1;
    $component->is_active = 1;
    $component->component_type = 'Welcome';

    $component->find(TRUE);

    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    $headers = [
      'Subject' => $component->subject,
      'From' => "\"$domainEmailName\" <do-not-reply@$emailDomain>",
      'To' => $email,
      'Reply-To' => "do-not-reply@$emailDomain",
      'Return-Path' => "do-not-reply@$emailDomain",
    ];

    $html = $component->body_html;

    if ($component->body_text) {
      $text = $component->body_text;
    }
    else {
      $text = CRM_Utils_String::htmlToText($component->body_html);
    }

    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->body_text = $text;
    $bao->body_html = $html;
    $tokens = $bao->getTokens();

    $html = CRM_Utils_Token::replaceDomainTokens($html, $domain, TRUE, $tokens['html']);
    $html = CRM_Utils_Token::replaceWelcomeTokens($html, $group->title, TRUE);

    $text = CRM_Utils_Token::replaceDomainTokens($text, $domain, FALSE, $tokens['text']);
    $text = CRM_Utils_Token::replaceWelcomeTokens($text, $group->title, FALSE);

    $message = new Mail_mime("\n");

    $message->setHTMLBody($html);
    $message->setTxtBody($text);
    $b = CRM_Utils_Mail::setMimeParams($message);
    $h = &$message->headers($headers);
    $mailer = &$config->getMailer();
    if (is_object($mailer)) {
      $mailer->send($email, $h, $b);
      CRM_Core_Error::setCallback();
    }
    return $group->title;
  }
}

