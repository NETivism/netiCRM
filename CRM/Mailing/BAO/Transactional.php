<?php

/**
 * A transactional email helper function
 *
 * Have interface to be a alternative of CRM_Utils_Mail::send for common mail send.
 * Will inherited from CRM_Mailing_BAO_Mailing for mass malling usage.
 */

class CRM_Mailing_BAO_Transactional extends CRM_Mailing_BAO_Mailing {
  /**
   * Activity Names that can invoke transactional email
   */
  const ALLOWED_ACTIVITY_TYPES = 'Email,Email Receipt,Contribution Notification Email,Event Notification Email,Membership Notification Email,PCP Notification Email,Mailing Notification Email';

  /**
   * job object of this mailing
   *
   * @var CRM_Mailing_DAO_Job
   */
  public $_job;

  /**
   * Additional Header to override compose header
   *
   * @var array
   */
  private $_additionalHeaders = array();

  /**
   * Send transactional mail
   *
   * Alternative of CRM_Utils_Mail::send
   *
   * @param array &$params Is an associative array which holds the values of field needed to send an email. These are:
   *   contactId : contact id is required
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
   *     fullPath : complete pathname to the file
   *     mime_type: mime type of the attachment
   *     cleanName: the user friendly name of the attachmment
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
   * @access public
   *
   * @return boolean true if a mail was sent, else false
   */
  public static function send(&$params, $callback = NULL) {
    $config = CRM_Core_Config::singleton();

    // when transactional email not enabled, fallback to use common send
    if (!$config->enableTransactionalEmail) {
      return CRM_Utils_Mail::send($params, $callback);
    }
    // validate required params
    $required = array(
      'contactId' => 'positiveInteger',
      'activityId' => 'positiveInteger',
      'toEmail' => 'email',
      'subject' => 'string',
      'html' => 'string',
    );
    foreach($required as $field => $type) {
      $rule = array('CRM_Utils_Rule', $type);
      if (empty($params[$field])) {
        CRM_Core_Error::debug_log_message('Transactional Email Error: missing required field '.$field);
        return;
      }
      $valid = call_user_func($rule, $params[$field]);
      if (!$valid) {
        CRM_Core_Error::debug_log_message('Transactional Email Error: type validation error of field: '.$field.' - '.$type);
        return;
      }
    }

    // use CRM_Utils_Mail to send cc / bcc
    $additionalRecipients = array();
    foreach(array('cc', 'bcc') as $ccType) {
      if (CRM_Utils_Array::value($ccType, $params)) {
        $aRecipients = explode(',', $params[$ccType]);
        unset($params[$ccType]);
        if (!empty($aRecipients)) {
          foreach($aRecipients as $rec) {
            $rec = trim($rec);
            if (CRM_Utils_Rule::email($rec)) {
              $additionalRecipients[] = $rec;
            }
          }
        }
      }
    }

    if (empty($params['from'])) {
      $defaultNameEmail = CRM_Core_BAO_Domain::getNameAndEmail( );
      $params['from'] = CRM_Utils_Mail::formatRFC822Email($defaultNameEmail[0], $defaultNameEmail[1]);
    }

    $tmail = new CRM_Mailing_BAO_Transactional($params);
    if (empty($tmail->id)) {
      CRM_Core_Error::debug_log_message("Cannot start transactional email because init error. ".__LINE__);

    }

    $emailId = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_email WHERE contact_id = %1 AND email = %2 ORDER BY is_primary DESC', array(
      1 => array($params['contactId'], 'Integer'),
      2 => array($params['toEmail'], 'String'),
    ));

    if (empty($emailId)) {
      CRM_Core_Error::debug_log_message('Transactional Email Error: contact '.$params['contactId'].' doesn\'t have email '.$params['toEmail']);
      return;
    }

    // create mailing recipient
    $recipient = array(
      1 => array($tmail->id, 'Integer'),
      2 => array($params['contactId'], 'Integer'),
      3 => array($emailId, 'Integer'),
    );
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing_recipients (mailing_id, contact_id, email_id) VALUES (%1, %2, %3)", $recipient);

    // create mailing event queue
    $queueParams = array(
      'job_id' => $tmail->_job->id,
      'email_id' => $emailId,
      'contact_id' => $params['contactId'],
    );
    $queue = CRM_Mailing_Event_BAO_Queue::create($queueParams);

    // create mailing event transactional
    $transParams = array(
      'job_id' => $tmail->_job->id,
      'event_queue_id' => $queue->id,
      'hash' => $queue->hash,
      'activity_id' => $params['activityId'],
    );
    $trans = CRM_Mailing_Event_BAO_Transactional::create($transParams);

    if ($queue->id && $trans->id) {
      $recipient = '';
      $attachments = CRM_Utils_Array::value('attachments', $params);
      $embedImages = CRM_Utils_Array::value('images', $params);
      $attachFiles = array(
        'attachments' => $attachments,
        'images' => $embedImages,
      );
      $message = $tmail->compose($tmail->_job->id, $queue->id, $queue->hash, $params['contactId'], $params['toEmail'], $recipient, FALSE, NULL, $attachFiles, $tmail->from_email);
      if (!empty($params['mailerType'])) {
        $mailer = &CRM_Core_Config::getMailer($params['mailerType']);
      }
      else {
        $mailer = &CRM_Core_Config::getMailer();
      }
      if (is_object($mailer)) {
        $body = &$message->get();
        $headers = &$message->headers();
        $recipient = $headers['To'];
        $result = NULL;
        CRM_Core_Error::ignoreException();

        // refs #30289, for valid DKIM
        if (empty(CRM_Core_Config::singleton()->enableDMARC) && empty($headers['Sender']) && !empty($mailer->_mailSetting['return_path'])) {
          $headers['Sender'] = $mailer->_mailSetting['return_path'];
        }

        // only send non-blocking when there is a callback
        if (isset($callback) && is_array($callback)) {
          $sendParams = array(
            'headers' => $headers,
            'to' => $recipient,
            'body' => $body,
            'callback' => $callback,
            'queue' => $queue,
          );
          // Non-blocking only make sense when there is fastcgi_finish_request
          if (php_sapi_name() === 'fpm-fcgi') {
            CRM_Core_Config::addShutdownCallback('after', 'CRM_Mailing_BAO_Transactional::sendNonBlocking', array($mailer, $sendParams));
          }
          else {
            CRM_Mailing_BAO_Transactional::sendNonBlocking($mailer, $sendParams);
          }
          if (!empty($additionalRecipients)) {
            self::additionalRecipients($additionalRecipients, $params);
          }
          return TRUE;
        }
        else {
          $result = $mailer->send($recipient, $headers, $body);
          if (!empty($additionalRecipients)) {
            self::additionalRecipients($additionalRecipients, $params);
          }
          CRM_Core_Error::setCallback();
          if (is_a($result, 'PEAR_Error')) {
            self::bounced($queue->id, $tmail->_job->id, $queue->hash, $result->getMessage());
            return FALSE;
          }
          else {
            self::delivered($queue->id, $tmail->_job->id, $queue->hash);
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Non-blocking mail send
   *
   * @param object $mailer From CRM_Core_Config::getMailer($params['mailerType'])
   * @param array $params An associative array for to send email.
   *   $params = array(
   *     'headers' => (array) associative array from Mail_mime->headers
   *     'to' => (string) recipient email address, required.
   *     'body' => (string) string from Mail_mime->body
   *     'callback' => (string) associative array for callbacks
   *     'queue' => (object) object from CRM_Mailing_Event_DAO_Queue
   *   );
   * @return void
   */
  public static function sendNonBlocking($mailer, $params) {
    $result = $mailer->send($params['to'], $params['headers'], $params['body']);
    CRM_Core_Error::setCallback();
    $error = 0;
    $queue = NULL;
    if (isset($params['queue']) && !empty($params['queue']) && (is_a($params['queue'], 'CRM_Mailing_Event_BAO_Queue') || is_a($params['queue'], 'CRM_Mailing_Event_DAO_Queue'))) {
      $queue = $params['queue'];
    }
    if (is_a($result, 'PEAR_Error')) {
      if ($queue) {
        self::bounced($queue->id, $queue->job_id, $queue->hash, $result->getMessage());
      }
      $error = 1;
    }
    else {
      if ($queue) {
        self::delivered($queue->id, $queue->job_id, $queue->hash);
      }
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
   * Mark this transactional email delivered
   *
   * @param int $event_queue_id
   * @param int $job_id
   * @param string $hash hash
   * @return void
   */
  public static function delivered($event_queue_id, $job_id, $hash) {
    $params = array(
      'event_queue_id' => $event_queue_id,
      'job_id' => $job_id,
      'hash' => $hash,
    );
    CRM_Mailing_Event_BAO_Delivered::create($params);
  }

  /**
   * Mark this transactional email bounced
   *
   * Email bounced immediately when deliver failed.
   *
   * @param int $event_queue_id
   * @param int $job_id
   * @param string $hash hash
   * @param string $resultMessage text message after mailer object return result
   * @return void
   */
  public static function bounced($event_queue_id, $job_id, $hash, $resultMessage) {
    $params = array(
      'event_queue_id' => $event_queue_id,
      'job_id' => $job_id,
      'hash' => $hash,
    );
    $params = array_merge($params, CRM_Mailing_BAO_BouncePattern::match($resultMessage));
    CRM_Mailing_Event_BAO_Bounce::create($params);
  }

  public static function additionalRecipients($recipients, $params) {
    unset($params['activityId'], $params['toName'], $params['toMail']);
    $tidyParams = $params;
    foreach($recipients as $email) {
      if (CRM_Utils_Rule::email($email)) {
        $sendParams = $tidyParams;
        $sendParams['toEmail'] = $email;
        CRM_Utils_Mail::send($sendParams, array(
          0 => array('CRM_Utils_Callback::nullCallback' => array()),
        ));
      }
    }
  }

  /**
   * Transactional Object
   *
   * For transactional object, we got to create new body, sender address for each email.
   * We summarize here
   *
   */
  function __construct($params = NULL) {
    parent::__construct();
    $this->is_hidden = 1;
    $this->name = 'Transactional Email';
    $this->find(TRUE);
    $this->free();

    if (!$this->id) {
      CRM_Core_Error::debug_log_message('Transactional Email Error: Cannot find transactional email base object.');
      return;
    }

    if (empty($params)) {
      CRM_Core_Error::debug_log_message('Transactional Email Error: needs init params.');
      return;
    }

    // basic Mailing object
    $this->subject = CRM_Utils_Array::value('subject', $params);
    $this->body_html = CRM_Utils_Array::value('html', $params);
    $this->body_text = CRM_Utils_Array::value('text', $params);
    $this->from_name = CRM_Utils_Mail::pluckNameFromHeader($params['from']);
    $this->from_email = CRM_Utils_Mail::pluckEmailFromHeader($params['from']);
    $this->replyto_email = $this->from_email;
    $this->dedupe_email = 0;
    $this->override_verp = 0;
    $this->msg_template_id = NULL;
    $this->open_tracking = 1;
    $this->forward_replies = 0;
    $this->auto_responder = 0;
    $this->_domain = CRM_Core_BAO_Domain::getDomain();
    // disable all the mass mailing component template
    $this->header_id = $this->footer_id = $this->reply_id = $this->unsubscribe_id = $this->resubscribe_id = $this->optout_id = 0;

    // disable url tracking by default
    $this->url_tracking = 0;

    // mailing job here
    $this->_job = new CRM_Mailing_DAO_Job();
    $this->_job->mailing_id = $this->id;
    $this->_job->find(TRUE);
  }


  /**
   * Compose transactional mailing
   * 
   * Because we extends from CRM_Mailing_BAO_Mailing, we reserve these args
   *
   * @param int $job_id
   * @param int $event_queue_id
   * @param string $hash
   * @param int $contactId
   * @param string $email
   * @param string &$recipient
   * @param book $test
   * @param array $contactDetails
   * @param array $attachFiles array element include attachments / images
   * @param bool $isForward
   * @param string $fromEmail
   * @param string $replyToEmail
   *
   * @return Mail_Mime
   * @access public
   */
  public function &compose($job_id, $event_queue_id, $hash, $contactId,
  $email, &$recipient, $test,
  $contactDetails, &$attachFiles, $isForward = FALSE,
  $fromEmail = NULL, $replyToEmail = NULL
) {
    $config = CRM_Core_Config::singleton();
    if ($this->_domain == NULL) {
      $this->_domain = CRM_Core_BAO_Domain::getDomain();
    }

    list($verp, $urls, $headers) = $this->getVerpAndUrlsAndHeaders($job_id, $event_queue_id, $hash, $email, $isForward);
    //set from email who is forwarding it and not original one.
    if ($fromEmail && CRM_Utils_Rule::email($fromEmail)) {
      unset($headers['From']);
      $headers['From'] = CRM_Utils_Mail::formatRFC822Email('', $fromEmail);
    }

    if ($replyToEmail && ($fromEmail != $replyToEmail) && CRM_Utils_Mail::checkRFC822Email($fromEmail)) {
      $headers['Reply-To'] = "{$replyToEmail}";
    }


    // refs #32614, disable smarty evaluation functions

    if ($contactDetails) {
      $contact = $contactDetails;
    }
    else {
      $params = array(array('contact_id', '=', $contactId, 0, 0));
      list($contactArray, $_) = CRM_Contact_BAO_Query::apiQuery($params);

      //CRM-4524
      $contact = reset($contactArray);

      if (!$contact || is_a($contact, 'CRM_Core_Error')) {
        // setting this because function is called by reference
        //@todo test not calling function by reference
        $res = NULL;
        return $res;
      }

      // also call the hook to get contact details
      require_once 'CRM/Utils/Hook.php';
      $contactIds = array($contactId);
      CRM_Utils_Hook::tokenValues($contactArray, $contactIds, $job_id, array(), 'CRM_Mailing_BAO_Mailing_compose');
    }

    $pTemplates = $this->getPreparedTemplates();
    $pEmails = array();

    foreach ($pTemplates as $type => $pTemplate) {
      $html = ($type == 'html') ? TRUE : FALSE;
      $pEmails[$type] = array();
      $pEmail = &$pEmails[$type];
      $template = &$pTemplates[$type]['template'];
      $tokens = &$pTemplates[$type]['tokens'];
      $idx = 0;
      if (!empty($tokens)) {
        foreach ($tokens as $idx => $token) {
          $token_data = $this->getTokenData($token, $html, $contact, $verp, $urls, $event_queue_id);
          array_push($pEmail, $template[$idx]);
          array_push($pEmail, $token_data);
        }
      }
      else {
        array_push($pEmail, $template[$idx]);
      }

      if (isset($template[($idx + 1)])) {
        array_push($pEmail, $template[($idx + 1)]);
      }
    }

    $html = NULL;
    if (isset($pEmails['html']) && is_array($pEmails['html']) && count($pEmails['html'])) {
      $html = &$pEmails['html'];
    }

    $text = NULL;
    if (isset($pEmails['text']) && is_array($pEmails['text']) && count($pEmails['text'])) {
      $text = &$pEmails['text'];
    }
    else {
      // this is where we create a text template from the html template if the text template did not exist
      // this way we ensure that every recipient will receive an email even if the pref is set to text and the
      // user uploads an html email only
      $text = CRM_Utils_String::htmlToText(join('', $html));
    }

    // push the tracking url on to the html email if necessary
    if ($this->open_tracking && $html) {
      $trackedOpen = FALSE;
      $openTrack = '<img src="' . $config->userFrameworkResourceURL . "extern/open.php?q=$event_queue_id\" width='1' height='1' alt='' border='0'>\n";
      foreach($html as $idx => $document) {
        if (stristr($document, '</body>')) {
          $html[$idx] = preg_replace('@</body>@i', $openTrack.'</body>', $document);
          $trackedOpen = TRUE;
          break;
        }
      }
      if (!$trackedOpen){
        array_push($html, "\n".$openTrack);
      }
    }

    $message = new Mail_mime("\n");

    // refs #32614, disable smarty evaluation functions

    $mailParams = $headers;
    if (!empty($this->_additionalHeaders)) {
      $mailParams = array_merge($mailParams, $this->_additionalHeaders);
    }
    if ($text && ($test || $contact['preferred_mail_format'] == 'Text' ||
        $contact['preferred_mail_format'] == 'Both' ||
        ($contact['preferred_mail_format'] == 'HTML' && !CRM_Utils_Array::arrayKeyExists('html', $pEmails))
      )) {
      if (is_array($text)) {
        $textBody = join('', $text);
      }
      else {
        $textBody = $text;
      }
      $mailParams['text'] = $textBody;
    }

    if ($html && ($test || ($contact['preferred_mail_format'] == 'HTML' || $contact['preferred_mail_format'] == 'Both'))) {
      $htmlBody = join('', $html);

      // refs #32614, disable smarty evaluation functions
      // #17688, rwd support for newsletter image
      $htmlBody = CRM_Utils_String::removeImageHeight($htmlBody);
      $mailParams['html'] = $htmlBody;
    }

    if (empty($mailParams['text']) && empty($mailParams['html'])) {
      // CRM-9833
      // something went wrong, lets log it and return null (by reference)
      CRM_Core_Error::debug_log_message(ts('CiviMail will not send an empty mail body, Skipping: %1', array(1 => $email)));
      $res = NULL;
      return $res;
    }

    $mailParams['attachments'] = $attachFiles['attachments'];
    $mailParams['images'] = $attachFiles['images'];
    $mailingSubject = CRM_Utils_Array::value('subject', $pEmails);
    if (is_array($mailingSubject)) {
      $mailingSubject = join('', $mailingSubject);
    }
    $mailParams['Subject'] = $mailingSubject;

    $mailParams['toName'] = CRM_Utils_Array::value('display_name', $contact);
    $mailParams['toEmail'] = $email;
    $mailParams['alterTag'] = 'transactional';
    CRM_Utils_Hook::alterMailParams($mailParams);
    unset($mailParams['alterTag']);

    //cycle through mailParams and set headers array
    foreach ($mailParams as $paramKey => $paramValue) {
      //exclude values not intended for the header
      if (!in_array($paramKey, array(
            'text', 'html', 'toName', 'toEmail', 'attachments', 'images'
          ))) {
        $headers[$paramKey] = $paramValue;
      }
    }

    if (!empty($mailParams['text'])) {
      $message->setTxtBody($mailParams['text']);
    }

    if (!empty($mailParams['html'])) {
      $message->setHTMLBody($mailParams['html']);
    }

    if (!empty($mailParams['attachments'])) {
      foreach ($mailParams['attachments'] as $fileID => $attach) {
        $message->addAttachment($attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName']
        );
      }
    }
    if (!empty($mailParams['images'])) {
      foreach ($mailParams['images'] as $imageID => $attach) {
        $message->addHTMLImage(
          $attach['fullPath'],
          $attach['mime_type'],
          $attach['cleanName'],
          TRUE,
          $imageID
        );
      }
    }

    $headers['To'] = CRM_Utils_Mail::formatRFC822Email($mailParams['toName'], $mailParams['toEmail']);
    // Will test in the mail processor if the X-VERP is set in the bounced email.
    // (As an option to replace real VERP for those that can't set it up)
    $headers['X-CiviMail-Bounce'] = $verp['bounce'];

    // refs #30565, add google feedback loop header
    $campaignID = $this->id.'-'.substr(str_replace(array('.', '-'), '', $_SERVER['HTTP_HOST']), 0, 10);
    $identifier = "j{$job_id}q{$event_queue_id}";
    $senderID = 'civimail';
    $headers['Feedback-ID'] = "$campaignID:$contactId-$identifier:$senderID";

    //CRM-5058
    //token replacement of subject
    $headers['Subject'] = $mailingSubject;

    CRM_Utils_Mail::setMimeParams($message);
    $headers = $message->headers($headers);

    //get formatted recipient
    $recipient = $headers['To'];

    // make sure we unset a lot of stuff
    unset($verp);
    unset($urls);
    unset($params);
    unset($contact);
    unset($ids);

    return $message;
  }


  /**
   * Get transactional details for an email
   *
   * @param  array   $contactId
   * @param  array   $activityId
   *
   * @return array
   * @access public
   */
  public static function getActivityReport($contactId, $activityId) {
    $eq = CRM_Mailing_Event_DAO_Queue::getTableName();
    $ea = CRM_Mailing_Event_DAO_Transactional::getTableName();
    $ed = CRM_Mailing_Event_DAO_Delivered::getTableName();
    $eb = CRM_Mailing_Event_DAO_Bounce::getTableName();
    $eo = CRM_Mailing_Event_DAO_Opened::getTableName();
    $ec = CRM_Mailing_Event_DAO_TrackableURLOpen::getTableName();
    $eu = CRM_Mailing_Event_DAO_Unsubscribe::getTableName();

    $from = array();
    $from[] = "INNER JOIN $ea ON $ea.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $ed ON $ed.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $eo ON $eo.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $ec ON $ec.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $eb ON $eb.event_queue_id = $eq.id";
    $from[] = "LEFT JOIN $eu as unsubscribe ON unsubscribe.event_queue_id = $eq.id AND unsubscribe.org_unsubscribe = 0";
    $from[] = "LEFT JOIN $eu as optout ON optout.event_queue_id = $eq.id AND optout.org_unsubscribe = 1";

    $select = "SELECT $eq.contact_id, COUNT($ed.time_stamp) as delivered, COUNT($eo.time_stamp) as opened, COUNT($ec.time_stamp) as clicks, COUNT($eb.time_stamp) as bounce, COUNT(unsubscribe.time_stamp) as unsubscribe, COUNT(optout.time_stamp)  as optout";
    $from  = "\n FROM $eq ".CRM_Utils_Array::implode("\n ", $from);
    $where = "\n WHERE $eq.contact_id = %1 AND $ea.activity_id = %2";
    $sql = $select . $from . $where;
    $dao = CRM_Core_DAO::executeQuery($sql, array(
      1 => array($contactId, 'Positive'),
      2 => array($activityId, 'Positive'),
    ));
    $dao->fetch();
    return array(
      'Delivered' => $dao->delivered,
      'Opened' => $dao->opened,
      'Clicked' => $dao->clicks,
      'Bounced' => $dao->bounce,
      'Unsubscribed' => $dao->unsubscribe,
      'Opt-Outed' => $dao->optout,
    );
  }
}