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
 * form to configure thank-you messages and receipting features for an online contribution page
 */
class CRM_Contribute_Form_ContributionPage_ThankYou extends CRM_Contribute_Form_ContributionPage {

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    $title = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'title');
    CRM_Utils_System::setTitle(ts('Thank-you and Receipting (%1)', [1 => $title]));
    return parent::setDefaultValues();
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->registerRule('emailList', 'callback', 'emailList', 'CRM_Utils_Rule');

    // thank you title and text (html allowed in text)
    $this->add('text', 'thankyou_title', ts('Thank-you Page Title'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'thankyou_title'), TRUE);
    $this->addWysiwyg('thankyou_text', ts('Thank-you Message'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'thankyou_text'));
    $this->addWysiwyg('thankyou_footer', ts('Thank-you Page Footer'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'thankyou_footer'));

    $this->addElement('checkbox', 'is_email_receipt', ts('Email Payment Notification to User?'), NULL, ['onclick' => "showReceipt()"]);
    $this->add('text', 'receipt_from_name', ts('Payment Notification From Name'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'receipt_from_name'));

    $availableFrom = CRM_Core_PseudoConstant::fromEmailAddress(TRUE, TRUE);
    $verifiedFrom = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail();
    $verifiedDomains = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail(
      CRM_Admin_Form_FromEmailAddress::VALID_EMAIL | CRM_Admin_Form_FromEmailAddress::VALID_DKIM | CRM_Admin_Form_FromEmailAddress::VALID_SPF,
      'domain'
    );
    $selectableEmail = [];
    $hasVerified = FALSE;
    foreach($availableFrom as $fromAddr) {
      $email = htmlspecialchars($fromAddr['email']);
      if (array_search($fromAddr['email'], $verifiedFrom) !== FALSE) {
        $email = ts('%1 Verified', [1 => '🛡️ '.htmlspecialchars($fromAddr['email'])]);
        $hasVerified = TRUE;
        $selectableEmail[$fromAddr['email']] = $email;
      }
      elseif (CRM_Utils_Mail::checkMailInDomains($fromAddr['email'], $verifiedDomains)) {
        $selectableEmail[$fromAddr['email']] = $email;
      }
    }
    arsort($selectableEmail);
    if (!$hasVerified) {
      $this->assign('show_spf_dkim_notice', TRUE);
    }
    $this->addSelect('receipt_from_email', ts('Payment Notification From Email'), $selectableEmail);

    $this->assign('mail_providers', str_replace('|', ', ', CRM_Utils_Mail::DMARC_MAIL_PROVIDERS));
    $defaultFromMail = CRM_Mailing_BAO_Mailing::defaultFromMail();
    $this->assign('default_from_target', 'receipt_from_email');
    $this->assign('default_from_value', $defaultFromMail);

    $this->addWysiwyg('receipt_text', ts('Payment Notification Message'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'receipt_text'));

    $this->add('text', 'cc_receipt', ts('CC Payment Notification To'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'cc_receipt'));
    $this->addRule('cc_receipt', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');

    $this->add('text', 'bcc_receipt', ts('BCC Payment Notification To'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'bcc_receipt'));
    $this->addRule('bcc_receipt', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');
    $this->add('text', 'recur_fail_notify', ts('Recurring Failed Notification to'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'recur_fail_notify'));
    $this->addRule('recur_fail_notify', ts('Please enter a valid list of comma delimited email addresses'), 'emailList');

    $this->addFormRule(['CRM_Contribute_Form_ContributionPage_ThankYou', 'formRule']);

    if (CRM_SMS_BAO_Provider::activeProviderCount()) {
      $this->addElement('checkbox', 'is_send_sms', ts('Send SMS when success?'), NULL, ['onclick' => "showSMS()"]);
      $this->add('textarea', 'sms_text', ts('SMS Text'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionPage', 'sms_text'));
    }

    // tokens
    $tokens = [];
    $tokens = CRM_Core_SelectValues::contactTokens();
    $tokens = array_merge(CRM_Core_SelectValues::contributionTokens(), $tokens);
    $this->assign('tokens', CRM_Utils_Token::formatTokensForDisplay($tokens));

    $this->add('select', 'token1', ts('Insert Tokens'),
      $tokens, FALSE,
      [
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplText(this);",
      ]
    );

    $this->add('select', 'token2', ts('Insert Tokens'),
      $tokens, FALSE,
      [
        'size' => "5",
        'multiple' => TRUE,
        'onclick' => "return tokenReplHtml(this);",
      ]
    );

    parent::buildQuickForm();
  }

  /**
   * global form rule
   *
   * @param array $fields  the input form values
   * @param array $files   the uploaded files if any
   * @param array $options additional user data
   *
   * @return true if no errors, else array of errors
   * @access public
   * @static
   */
  static function formRule($fields, $files, $options) {
    $errors = [];

    // if is_email_receipt is set, the receipt message must be non-empty
    if (CRM_Utils_Array::value('is_email_receipt', $fields)) {
      //added for CRM-1348
      $email = trim(CRM_Utils_Array::value('receipt_from_email', $fields));
      if (empty($email)) {
        $errors['receipt_from_email'] = ts('A valid Receipt From Email address must be specified if Email Payment Notification to User is enabled');
      }
      else {
        if (!CRM_Utils_Rule::email($email)) {
          $errors['receipt_from_email'] = ts('Please enter the valid email address.'); 
        }
        if (!CRM_Utils_Mail::checkMailProviders($email)) {
          $errors['receipt_from_email'] = ts('Do not use free mail address as mail sender. (eg. %1)', [1 => str_replace('|', ', ', CRM_Utils_Mail::DMARC_MAIL_PROVIDERS)]);
        }
      }
    }
    return $errors;
  }

  /**
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);

    $params['id'] = $this->_id;
    $params['is_email_receipt'] = CRM_Utils_Array::value('is_email_receipt', $params, FALSE);
    if (!$params['is_email_receipt']) {
      $params['receipt_from_name'] = NULL;
      $params['receipt_from_email'] = NULL;
      $params['receipt_text'] = NULL;
      $params['cc_receipt'] = NULL;
      $params['bcc_receipt'] = NULL;
    }

    $params['is_send_sms'] = CRM_Utils_Array::value('is_send_sms', $params, FALSE);
    if (!$params['is_send_sms']) {
      $params['sms_text'] = NULL;
    }


    $dao = CRM_Contribute_BAO_ContributionPage::create($params);
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   * @access public
   */
  public function getTitle() {
    return ts('Thanks and Receipt');
  }
}

