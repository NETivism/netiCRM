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
 * Utility methods for composing and sending email messages
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Utils_Mail {

  public const DMARC_MAIL_PROVIDERS = 'yahoo.com|gmail.com|msn.com|outlook.com|hotmail.com';
  public const DKIM_EXTERNAL_VERIFIED_FILE = 'verified_external_dkim.config';
  public const RFC_2822_SPECIAL_CHARS = '()<>[]:;@\,."';

  /**
   * Wrapper function to send mail in CiviCRM. Hooks are called from this function.
   *
   * @param array &$params Is an associative array which holds the values of field needed to send an email. These are:
   *   from    : complete from envelope
   *   toName  : name of person to send email
   *   toEmail : email address to send to
   *   cc      : email addresses to cc
   *   bcc     : email addresses to bcc
   *   subject : subject of the email
   *   text    : text of the message
   *   html    : html version of the message
   *   reply-to: reply-to header in the email
   *   attachments: an associative array of
   *   fullPath : complete pathname to the file
   *   mime_type: mime type of the attachment
   *   cleanName: the user friendly name of the attachment
   *
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
   * @return bool  TRUE if the mail was sent successfully, FALSE otherwise.
   */
  public static function send(&$params, $callback = NULL) {
    $config = CRM_Core_Config::singleton();
    $returnPath = CRM_Core_BAO_MailSettings::defaultReturnPath();
    $from = CRM_Utils_Array::value('from', $params);
    if (!$returnPath) {
      $returnPath = self::pluckEmailFromHeader($from);
    }
    $params['returnPath'] = $returnPath;

    // first call the mail alter hook

    $params['alterTag'] = 'mail';
    CRM_Utils_Hook::alterMailParams($params);
    unset($params['alterTag']);

    // check if any module has aborted mail sending
    if (CRM_Utils_Array::value('abortMailSend', $params) ||
      !CRM_Utils_Array::value('toEmail', $params)
    ) {
      return FALSE;
    }

    $textMessage = CRM_Utils_Array::value('text', $params);
    $htmlMessage = CRM_Utils_Array::value('html', $params);
    $attachments = CRM_Utils_Array::value('attachments', $params);
    $embedImages = CRM_Utils_Array::value('images', $params);

    // CRM-6224
    if (trim(CRM_Utils_String::htmlPurifier($htmlMessage, ['img'])) == '') {
      $htmlMessage = FALSE;
    }

    $headers = [];
    if (self::checkRFC822Email($params['from'])) {
      $headers['From'] = $params['from'];
    }
    else {
      $fromName = self::pluckNameFromHeader($params['from']);
      $fromEmail = self::pluckEmailFromHeader($params['from']);
      $headers['From'] = self::formatRFC822Email($fromName, $fromEmail);
    }
    $headers['To'] = self::formatRFC822Email($params['toName'], $params['toEmail']);
    // TODO: check cc / bcc have correct format
    $headers['Cc'] = !empty($params['cc']) ? self::checkEmails(CRM_Utils_Array::value('cc', $params)) : '';
    $headers['Bcc'] = !empty($params['bcc']) ? self::checkEmails(CRM_Utils_Array::value('bcc', $params)) : '';
    $headers['Subject'] = CRM_Utils_Array::value('subject', $params);
    $headers['Content-Type'] = $htmlMessage ? 'multipart/mixed; charset=utf-8' : 'text/plain; charset=utf-8';
    $headers['Content-Disposition'] = 'inline';
    $headers['Content-Transfer-Encoding'] = 'quoted-printable';
    $headers['Return-Path'] = CRM_Utils_Array::value('returnPath', $params);
    $headers['Reply-To'] = CRM_Utils_Array::value('replyTo', $params, $from);

    if (isset($config->enableDMARC) && !empty($config->enableDMARC)) {
      $validatedEmails = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail();
      $fromEmail = self::pluckEmailFromHeader($params['from']);
      if (in_array($fromEmail, $validatedEmails)) {
        $headers['Sender'] = $fromEmail;
      }
    }
    else {
      $headers['Sender'] = CRM_Utils_Array::value('returnPath', $params);
    }
    $headers['Date'] = date('r');
    if (CRM_Utils_Array::value('autoSubmitted', $params)) {
      $headers['Auto-Submitted'] = "Auto-Generated";
    }

    //make sure we has to have space, CRM-6977
    foreach (['From', 'To', 'Cc', 'Bcc', 'Reply-To', 'Return-Path', 'Sender'] as $fld) {
      $headers[$fld] = str_replace('"<', '" <', $headers[$fld]);
    }

    // quote FROM, if comma is detected AND is not already quoted. CRM-7053
    if (strpos($headers['From'], ',') !== FALSE) {
      $from = explode(' <', $headers['From']);
      if (substr($from[0], 0, 1) != '"' ||
        substr($from[0], -1, 1) != '"'
      ) {
        $from[0] = str_replace('"', '\"', $from[0]);
        $headers['From'] = "\"{$from[0]}\" <{$from[1]}";
      }
    }
    CRM_Mailing_BAO_Mailing::addMessageIdHeader($headers);

    $msg = new Mail_mime("\n");
    if ($textMessage) {
      $msg->setTxtBody($textMessage);
    }

    if ($htmlMessage) {
      $msg->setHTMLBody($htmlMessage);
    }

    if (!empty($attachments)) {
      foreach ($attachments as $fileID => $attach) {
        $msg->addAttachment(
          $attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName']
        );
      }
    }
    if (!empty($embedImages)) {
      foreach ($embedImages as $imageID => $attach) {
        $msg->addHTMLImage(
          $attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName'],
          TRUE,
          $imageID
        );
      }
    }

    $message = &self::setMimeParams($msg);
    $headers = &$msg->headers($headers);

    $to = [$params['toEmail']];

    //get emails from headers, since these are
    //combination of name and email addresses.
    if (CRM_Utils_Array::value('Cc', $headers)) {
      $to[] = CRM_Utils_Array::value('Cc', $headers);
    }
    if (CRM_Utils_Array::value('Bcc', $headers)) {
      $to[] = CRM_Utils_Array::value('Bcc', $headers);
      unset($headers['Bcc']);
    }

    $result = NULL;
    if (!empty($params['mailerType'])) {
      $mailer = &CRM_Core_Config::getMailer($params['mailerType']);
    }
    else {
      $mailer = &CRM_Core_Config::getMailer();
    }
    CRM_Core_Error::ignoreException();
    if (is_object($mailer)) {
      // refs #30289, for valid DKIM
      if (empty($headers['Sender']) && !empty($mailer->_mailSetting['return_path'])) {
        $headers['Sender'] = $mailer->_mailSetting['return_path'];
        $headers['Return-Path'] = $mailer->_mailSetting['return_path'];
      }

      // only send non-blocking when there is a callback
      if (isset($callback) && is_array($callback)) {
        $sendParams = [
          'headers' => $headers,
          'to' => $to,
          'body' => $message,
          'callback' => $callback,
        ];
        // Non-blocking only make sense when there is fastcgi_finish_request
        if (php_sapi_name() === 'fpm-fcgi') {
          CRM_Core_Config::addShutdownCallback('after', 'CRM_Utils_Mail::sendNonBlocking', [$mailer, $sendParams]);
        }
        else {
          CRM_Utils_Mail::sendNonBlocking($mailer, $sendParams);
        }
        return TRUE;
      }
      else {
        $result = $mailer->send($to, $headers, $message);
        CRM_Core_Error::setCallback();
        if (is_a($result, 'PEAR_Error')) {
          $message = self::errorMessage($mailer, $result);
          CRM_Core_Session::setStatus($message, FALSE);
          return FALSE;
        }
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Wrapper function which called by shutdown callback
   *
   * @param object $mailer From CRM_Core_Config::getMailer($params['mailerType'])
   * @param array $params An associative array for to send email.
   *   $params = array(
   *     'headers' => (array) associative array from Mail_mime->headers
   *     'to' => (string) recipient email address, required.
   *     'body' => (string) string from Mail_mime->body
   *     'callback' => (string) associative array for callbacks
   *   );
   * @return void
   */
  public static function sendNonBlocking($mailer, $params) {
    $result = $mailer->send($params['to'], $params['headers'], $params['body']);
    CRM_Core_Error::setCallback();
    $error = 0;
    if (is_a($result, 'PEAR_Error')) {
      $error = 1;
    }

    if (!empty($params['callback'][$error])) {
      $callback = $params['callback'];
      $call = key($callback[$error]);
      $args = reset($callback[$error]);
      if (is_callable($call)) {
        call_user_func_array($call, $args);
      }
    }
  }

  /**
   * Build a human-readable HTML error message for a failed mail send attempt.
   *
   * @param object     $mailer The mailer object (e.g. Mail_smtp or Mail_sendmail).
   * @param PEAR_Error $result The PEAR_Error returned by the mailer.
   *
   * @return string HTML-formatted error message.
   */
  public static function errorMessage($mailer, $result) {
    $message = '<p>' . ts('An error occurred when CiviCRM attempted to send an email (via %1). If you received this error after submitting on online contribution or event registration - the transaction was completed, but we were unable to send the email receipt.', [1 => 'SMTP']) . '</p>' . '<p>' . ts('The mail library returned the following error message:') . '<br /><span class="font-red"><strong>' . $result->getMessage() . '</strong></span></p>' . '<p>' . ts('This is probably related to a problem in your Outbound Email Settings (Administer CiviCRM &raquo; Global Settings &raquo; Outbound Email), OR the FROM email address specifically configured for your contribution page or event. Possible causes are:') . '</p>';

    if (is_a($mailer, 'Mail_smtp')) {
      $message .= '<ul>' . '<li>' . ts('Your SMTP Username or Password are incorrect.') . '</li>' . '<li>' . ts('Your SMTP Server (machine) name is incorrect.') . '</li>' . '<li>' . ts('You need to use a Port other than the default port 25 in your environment.') . '</li>' . '<li>' . ts('Your SMTP server is just not responding right now (it is down for some reason).') . '</li>';
    }
    else {
      $message .= '<ul>' . '<li>' . ts('Your Sendmail path is incorrect.') . '</li>' . '<li>' . ts('Your Sendmail argument is incorrect.') . '</li>';
    }

    $message .= '<li>' . ts('The FROM Email Address configured for this feature may not be a valid sender based on your email service provider rules.') . '</li>' . '</ul>' . '<p>' . ts('Check <a href="%1">this page</a> for more information.', [1 => CRM_Utils_System::docURL2('Outbound Email (SMTP)', TRUE)]) . '</p>';

    return $message;
  }

  /**
   * Log an outgoing email to a file for debugging purposes.
   *
   * If CIVICRM_MAIL_LOG is numeric, writes each email to a separate file
   * in the configAndLogDir/mail/ directory. Otherwise appends to the
   * file specified by CIVICRM_MAIL_LOG.
   *
   * @param string|array $to      Recipient email address(es) (passed by reference).
   * @param array        $headers Email headers as key-value pairs (passed by reference).
   * @param string       $message The email body (passed by reference).
   *
   * @return void
   */
  public static function logger(&$to, &$headers, &$message) {
    if (is_array($to)) {
      $toString = CRM_Utils_Array::implode(', ', $to);
      $fileName = $to[0];
    }
    else {
      $toString = $fileName = $to;
    }
    $content = "To: " . $toString . "\n";
    foreach ($headers as $key => $val) {
      $content .= "$key: $val\n";
    }
    $content .= "\n" . $message . "\n";

    if (is_numeric(CIVICRM_MAIL_LOG)) {
      $config = CRM_Core_Config::singleton();
      // create the directory if not there
      $dirName = $config->configAndLogDir . 'mail' . DIRECTORY_SEPARATOR;
      CRM_Utils_File::createDir($dirName);
      $fileName = md5(uniqid(CRM_Utils_String::munge($fileName))) . '.txt';
      file_put_contents(
        $dirName . $fileName,
        $content
      );
    }
    else {
      file_put_contents(CIVICRM_MAIL_LOG, $content, FILE_APPEND);
    }
  }

  /**
   * Get the email address itself from a formatted full name + address string
   *
   * Ugly but working.
   *
   * @param  string $header  the full name + email address string
   *
   * @return string          the plucked email address
   */
  public static function pluckEmailFromHeader($header) {
    preg_match('/<([^<]*)>$/', $header, $matches);
    return $matches[1];
  }

  /**
   * Extract the display name from a formatted "Name <email>" header string.
   *
   * @param string $header The full name + email address string.
   *
   * @return string The extracted display name.
   */
  public static function pluckNameFromHeader($header) {
    $email = self::pluckEmailFromHeader($header);
    $name = str_replace("<{$email}>", '', $header);
    $name = trim($name, '" ');
    return trim($name);
  }

  /**
   * Check whether a valid outbound email configuration exists.
   *
   * Inspects the mailing preferences to determine if SMTP or Sendmail
   * settings are properly configured.
   *
   * @return bool TRUE if valid outbound email configuration found, FALSE otherwise.
   */
  public static function validOutBoundMail() {

    $mailingInfo = &CRM_Core_BAO_Preferences::mailingPreferences();
    if ($mailingInfo['outBound_option'] == 3) {
      return TRUE;
    }
    elseif ($mailingInfo['outBound_option'] == 0) {
      if (!isset($mailingInfo['smtpServer']) || $mailingInfo['smtpServer'] == '' ||
        $mailingInfo['smtpServer'] == 'YOUR SMTP SERVER' ||
        ($mailingInfo['smtpAuth'] && ($mailingInfo['smtpUsername'] == '' || $mailingInfo['smtpPassword'] == ''))
      ) {
        return FALSE;
      }
      return TRUE;
    }
    elseif ($mailingInfo['outBound_option'] == 1) {
      if (!$mailingInfo['sendmail_path'] || !$mailingInfo['sendmail_args']) {
        return FALSE;
      }
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Set MIME encoding parameters on a Mail_mime message and return the encoded body.
   *
   * Uses UTF-8 charset and quoted-printable encoding for text/html,
   * base64 for headers.
   *
   * @param Mail_mime  $message The Mail_mime message object (passed by reference).
   * @param array|null $params  Optional custom MIME parameters. If NULL, defaults are used.
   *
   * @return string The encoded message body.
   */
  public static function &setMimeParams(&$message, $params = NULL) {
    static $mimeParams = NULL;
    if (!$params) {
      if (!$mimeParams) {
        $mimeParams = [
          'head_encoding' => 'base64',
          'text_encoding' => 'quoted-printable',
          'html_encoding' => 'quoted-printable',
          'head_charset' => 'utf-8',
          'text_charset' => 'utf-8',
          'html_charset' => 'utf-8',
        ];
      }
      $params = $mimeParams;
    }
    return $message->get($params);
  }

  /**
   * Check and remove incorrect emails
   *
   * @param string|array $mails
   * @return string
   */
  public static function checkEmails($mails) {
    $mailArray = [];
    if (is_string($mails)) {
      preg_match_all('/(?:"[^"]*"|[^,])+/u', $mails, $matches);
      if (!empty($matches[0])) {
        $mailArray = $matches[0];
      }
    }
    elseif (is_array($mails)) {
      $mailArray = $mails;
    }
    $checked = [];
    foreach ($mailArray as $address) {
      $address = trim($address);
      if (strstr($address, '<') && strstr($address, '>')) {
        $name = self::pluckNameFromHeader($address);
        $email = self::pluckEmailFromHeader($address);
        $formatted = self::formatRFC822Email($name, $email);
        if (!empty($formatted)) {
          $checked[] = $formatted;
        }
      }
      else {
        if (CRM_Utils_Rule::email($address)) {
          $checked[] = '<'.$address.'>';
        }
      }
    }
    if (!empty($checked)) {
      return implode(', ', $checked);
    }
    return '';
  }

  /**
   * Validate whether an email address string conforms to RFC 822/2822 format.
   *
   * Checks that special characters in the display name are properly quoted
   * and that the email address portion is valid.
   *
   * @param string $address The email address string to validate.
   *
   * @return bool TRUE if the address is valid RFC 822, FALSE otherwise.
   */
  public static function checkRFC822Email($address) {
    $address = trim($address);
    if (strstr($address, '<') && strstr($address, '>')) {
      $name = self::pluckNameFromHeader($address);
      $email = self::pluckEmailFromHeader($address);
    }
    else {
      $name = '';
      $email = trim($address);
    }
    if (!empty($name) && preg_match('/[ ' . preg_quote(self::RFC_2822_SPECIAL_CHARS) . ']/u', $name)) {
      // check the name already quoted
      if (!preg_match('/^"[^"]+"\s*<[^>]+@[^>]+>/i', $address)) {
        return FALSE;
      }
    }
    if (!CRM_Utils_Rule::email($email)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Format a display name and email address into an RFC 822 compliant string.
   *
   * @param string $name     The display name.
   * @param string $email    The email address.
   * @param bool   $useQuote Whether to quote the display name.
   *
   * @return string The formatted email string (e.g. '"Name" <email>'), or empty string if email is invalid.
   */
  public static function formatRFC822Email($name, $email, $useQuote = TRUE) {
    $result = '';
    $email = CRM_Utils_Type::escape($email, 'Email', FALSE);
    if (empty($email)) {
      return '';
    }

    // strip out double quotes if present at the beginning AND end
    if (substr($name, 0, 1) == '"' && substr($name, -1, 1) == '"') {
      $name = substr($name, 1, -1);
    }
    if (!empty($name)) {
      $name = self::sanitizeName($name);
      if (!empty($name)) {
        if (preg_match('/[' . preg_quote(self::RFC_2822_SPECIAL_CHARS) . ']/u', $name)) {
          $useQuote = TRUE;
        }
        if ($useQuote) {
          $name = '"' . $name . '"';
        }
        $result = $name.' ';
      }
    }
    $result .= "<{$email}>";
    return $result;
  }

  /**
   * Check whether an email address belongs to a known DMARC-enforcing mail provider.
   *
   * @param string $email The email address to check.
   *
   * @return bool TRUE if the email does NOT belong to a known provider, FALSE if it does.
   */
  public static function checkMailProviders($email) {
    $mailProviders = str_replace('.', '\.', self::DMARC_MAIL_PROVIDERS);
    if (preg_match('/'.$mailProviders.'/i', $email)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if SPF record valid
   *
   * @param string $email email or domain name to verify
   * @param object $mailer specific mailer which contain host info to help verify
   * @return bool|string return TRUE when success, return fail reason explain when failed.
   */
  public static function checkSPF($email, $mailer = NULL) {
    if (strstr($email, '@')) {
      list($user, $domain) = explode('@', trim($email));
    }
    else {
      $domain = $email;
    }

    if (empty($mailer)) {
      $config = CRM_Core_Config::singleton();
      $mailer = $config->getMailer();
      $host = $mailer->host;
    }
    if (!empty($host)) {
      $ip = CRM_Utils_System::getHostIPAddress($host);
      if (!$ip) {
        CRM_Core_Error::debug_log_message("Could not resolve IP address of $host.");
        return 'Cannot resolve IP address of provided host: '.$host.'. Abort SPF verification.';
      }
      if (CRM_Utils_System::checkPHPVersion(7.1)) {
        require_once 'SPFLib/autoload.php';
        $checker = new SPFLib\Checker();
        $checkResult = $checker->check(new SPFLib\Check\Environment($ip, $domain));
        $result = $checkResult->getCode();
        if ($result === SPFLib\Check\Result::CODE_PASS) {
          return TRUE;
        }
        $explains = $checkResult->getMessages();
        if (!empty($explains)) {
          return CRM_Utils_Array::implode("\n", $explains);
        }
        switch ($result) {
          case SPFLib\Check\Result::CODE_NONE:
            return 'No SPF record found.';
          case SPFLib\Check\Result::CODE_NEUTRAL:
          case SPFLib\Check\Result::CODE_FAIL:
          case SPFLib\Check\Result::CODE_SOFTFAIL:
          case SPFLib\Check\Result::CODE_ERROR_PERMANENT:
            return 'SPF syntax error or configuration error.';
          case SPFLib\Check\Result::CODE_ERROR_TEMPORARY:
            return 'Unknown temporary error occurred, please try again.';
          default:
            return 'Unknown error occurred.';
        }
      }
      else {
        require_once 'SPFCheck/autoload.php';
        $checker = new Mika56\SPFCheck\SPFCheck(new Mika56\SPFCheck\DNSRecordGetter());
        $result = $checker->isIPAllowed($ip, $domain);
        if ($result === Mika56\SPFCheck\SPFCheck::RESULT_PASS) {
          return TRUE;
        }
        switch ($result) {
          case Mika56\SPFCheck\SPFCheck::RESULT_NONE:
            return 'No SPF record found.';
          case Mika56\SPFCheck\SPFCheck::RESULT_MULTIPLE:
            return 'Too many SPF records or configuration error.';
          case Mika56\SPFCheck\SPFCheck::RESULT_PERMERROR:
            return 'SPF syntax error or configuration error.';
          case Mika56\SPFCheck\SPFCheck::RESULT_TEMPERROR:
            return 'Unknown temporary error occurred, please try again.';
          case Mika56\SPFCheck\SPFCheck::RESULT_DEFINITIVE_PERMERROR:
            return 'Too many DNS lookups have been performed (max limit is 10)';
        }
        return 'Unknown error occurred.';
      }
    }
    return FALSE;
  }

  /**
   * Retrieve SPF (TXT) DNS records for the domain of an email address.
   *
   * @param string $email An email address or domain name.
   *
   * @return array The DNS TXT records, or an empty array if none found.
   */
  public static function getSPF($email) {
    if (strstr($email, '@')) {
      list($user, $domain) = explode('@', trim($email));
    }
    else {
      $domain = $email;
    }
    $records = dns_get_record($domain, DNS_TXT);
    if (!empty($records)) {
      return $records;
    }
    return [];
  }

  /**
   * Check if DKIM record valid
   *
   * @param string $email Email or domain name.
   * @return bool|null
   *   Return NULL when there is no dkim selector or domain.
   *   Return bool when apply the validation.
   */
  public static function checkDKIM($email) {
    global $civicrm_conf;

    // skip check when there were no selector
    if (empty($civicrm_conf['mailing_dkim_domain']) || empty($civicrm_conf['mailing_dkim_selector'])) {
      return NULL;
    }

    $records = self::getDKIM($email);
    if (!empty($records)) {
      foreach ($records as $r) {
        if (!empty($r['target']) && $r['target'] === $civicrm_conf['mailing_dkim_domain']) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieve DKIM CNAME DNS records for the domain of an email address.
   *
   * Uses the DKIM selector and domain from $civicrm_conf to construct
   * the DNS lookup hostname.
   *
   * @param string $email An email address or domain name.
   *
   * @return array The DNS CNAME records, or an empty array if none found or DKIM is not configured.
   */
  public static function getDKIM($email) {
    global $civicrm_conf;

    // skip check when there were no selector
    if (empty($civicrm_conf['mailing_dkim_domain']) || empty($civicrm_conf['mailing_dkim_selector'])) {
      return [];
    }

    if (strstr($email, '@')) {
      list($user, $domain) = explode('@', trim($email));
    }
    else {
      $domain = $email;
    }
    $dkimCheck = $civicrm_conf['mailing_dkim_selector'].'._domainkey.'.$domain;
    $records = dns_get_record($dkimCheck, DNS_CNAME);
    if (!empty($records)) {
      return $records;
    }
    return [];
  }

  /**
   * Check email has whitelist domain
   *
   * @param string $email
   * @param array $domains
   * @return bool
   */
  public static function checkMailInDomains($email, $domains) {
    if (strstr($email, '<')) {
      $email = self::pluckEmailFromHeader($email);
    }
    if (empty($email) || !CRM_Utils_Rule::email($email)) {
      return FALSE;
    }
    foreach ($domains as $domain) {
      if (strstr($email, '@'.$domain)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get a list of domains with valid DKIM records from verified sender email addresses.
   *
   * Optionally saves the verified domain list to a configuration file.
   *
   * @param bool        $externalOnly If TRUE, exclude domains matching the default from-email domain.
   * @param string|null $saveFile     If provided, save the domain list to this filename in the CMS config directory.
   *
   * @return array|null List of verified domain names, empty array if none found, or NULL if DKIM is not configured.
   */
  public static function validDKIMDomainList($externalOnly = FALSE, $saveFile = NULL) {
    global $civicrm_conf;
    if (empty($civicrm_conf['mailing_dkim_domain']) || empty($civicrm_conf['mailing_dkim_selector'])) {
      return NULL;
    }
    $defaultFrom = CRM_Mailing_BAO_Mailing::defaultFromMail();
    $defaultDomain = substr($defaultFrom, strpos($defaultFrom, '@') + 1);
    if ($saveFile) {
      $confPath = CRM_Utils_System::cmsRootPath().DIRECTORY_SEPARATOR.CRM_Utils_System::confPath().DIRECTORY_SEPARATOR;
      $savePath = $confPath.basename($saveFile);
      $existsDomains = [];
      if (file_exists($savePath)) {
        $existsList = file_get_contents($savePath);
        $existsDomains = explode("\n", $existsList);
      }
    }

    $validType = CRM_Admin_Form_FromEmailAddress::VALID_EMAIL | CRM_Admin_Form_FromEmailAddress::VALID_SPF | CRM_Admin_Form_FromEmailAddress::VALID_DKIM;
    $verifiedDomains = CRM_Admin_Form_FromEmailAddress::getVerifiedEmail($validType, 'domain');
    foreach ($verifiedDomains as $idx => $domain) {
      if ($externalOnly && preg_match('/'.preg_quote($defaultDomain).'$/', $domain)) {
        unset($verifiedDomains[$idx]);
      }
      $isValid = FALSE;

      // do not trigger dns query when list exists
      if (in_array($domain, $existsDomains)) {
        $isValid = TRUE;
      }
      // this will do dns query
      else {
        $isValid = self::checkDKIM($domain);
      }
      if (!$isValid) {
        unset($verifiedDomains[$idx]);
      }
    }
    if (!empty($verifiedDomains)) {
      if ($savePath) {
        $addDomains = array_diff($verifiedDomains, $existsDomains);
        if (!empty($addDomains)) {
          $file = fopen($savePath, 'w');
          if ($file !== FALSE) {
            $written = fwrite($file, implode("\n", $verifiedDomains));
            if (!$written) {
              CRM_Core_Error::debug_log_message('File save failed: '.$savePath);
            }
            fclose($file);
          }
          else {
            CRM_Core_Error::debug_log_message('Could not open file to save: '.$savePath);
          }
          if (file_exists($savePath)) {
            CRM_Utils_File::chmod($savePath, 0666);
          }
        }
      }
      return $verifiedDomains;
    }
    return [];
  }

  /**
   * Sanitize Email for compatible with RFC822 / RFC2822
   *
   * @param string $email
   * @return string return empty string when false
   */
  public static function sanitizeEmail($email) {
    $sanitized = '';
    $email = trim($email);
    $filtered = filter_var($email, FILTER_SANITIZE_EMAIL);

    // still not ok, try sanitize harder
    if (!CRM_Utils_Rule::email($filtered)) {
      list($local, $domain) = explode('@', $filtered, 2);
      $local = preg_replace('/[^a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-]/', '', $local);
      $local = strtolower($local);

      // no any name part left, definitely error
      if ($local === '') {
        return $sanitized;
      }

      $domain = trim($domain, " \t\n\r\0\x0B.");
      if ($domain === '') {
        return $sanitized;
      }
      if (!strstr($domain, '.')) {
        return $sanitized;
      }
      $subs = explode('.', $domain);
      $sanitizedDomain = [];
      foreach ($subs as $sub) {
        $sub = trim($sub, " \t\n\r\0\x0B-");
        $sub = preg_replace('/[^a-z0-9-]+/i', '', $sub);
        if ($sub !== '') {
          $sanitizedDomain[] = strtolower($sub);
        }
      }
      if (count($sanitizedDomain) < 2) {
        return $sanitized;
      }
      $domain = implode('.', $sanitizedDomain);

      $filtered = $local.'@'.$domain;
      if (CRM_Utils_Rule::email($filtered)) {
        $sanitized = $filtered;
      }
    }
    else {
      $sanitized = $filtered;
    }

    return $sanitized;
  }

  /**
   * Sanitize Email Name to escape quote
   *
   * @param string $name
   * @return string
   */
  public static function sanitizeName($name) {
    $string = trim($name, '"');
    if (strstr($string, '"')) {
      $string = str_replace('"', '\\"', $string);
    }
    return $string;
  }
}
