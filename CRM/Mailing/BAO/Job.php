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






class CRM_Mailing_BAO_Job extends CRM_Mailing_DAO_Job {
  public $dedupe_email;
  CONST MAX_CONTACTS_TO_PROCESS = 1000;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  function create($params) {
    $job = new CRM_Mailing_BAO_Job();
    $job->mailing_id = $params['mailing_id'];
    $job->status = $params['status'];
    $job->scheduled_date = $params['scheduled_date'];
    $job->is_test = $params['is_test'];
    $job->save();
    $mailing = new CRM_Mailing_BAO_Mailing();
    $eq = $mailing->getRecipients($job->id, $params['mailing_id'], NULL, NULL, TRUE, FALSE);
    return $job;
  }

  /**
   * Initiate all pending/ready jobs
   *
   * @return void
   * @access public
   * @static
   */
  public static function runJobs($testParams = NULL) {
    $job = new CRM_Mailing_BAO_Job();

    $config = CRM_Core_Config::singleton();
    $jobTable = CRM_Mailing_DAO_Job::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();

    if (!empty($testParams)) {
      $query = "
			SELECT *
			  FROM $jobTable
			 WHERE id = {$testParams['job_id']}";
      $job->query($query);
    }
    else {
      $currentTime = date('YmdHis');
      $domainID = CRM_Core_Config::domainID();

      // Select the first child job that is scheduled
      // CRM-6835
      $query = "
SELECT j.*
  FROM $jobTable j
  INNER JOIN $mailingTable m ON m.id = j.mailing_id AND m.domain_id = {$domainID} AND m.is_hidden <= 0
WHERE j.is_test = 0
  AND (
    ( j.start_date IS null AND j.scheduled_date <= $currentTime AND  j.status = 'Scheduled' )
    OR
    ( j.status = 'Running' AND j.end_date IS null)
  )
  AND j.job_type = 'child'
ORDER BY j.scheduled_date ASC, m.scheduled_date ASC, j.mailing_id ASC, j.id ASC";
      $job->query($query);
    }



    while ($job->fetch()) {
      // still use job level lock for each child job
      $lockName = "civimail.job.{$job->id}";

      $lock = new CRM_Core_Lock($lockName);
      if (!$lock->isAcquired()) {
        continue;
      }

      // for test jobs we do not change anything, since its on a short-circuit path
      if (empty($testParams)) {
        // we've got the lock, but while we were waiting and processing
        // other emails, this job might have changed under us
        // lets get the job status again and check
        $job->status = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Job',
          $job->id,
          'status'
        );

        if ($job->status != 'Running' &&
          $job->status != 'Scheduled'
        ) {
          // this includes Cancelled and other statuses, CRM-4246
          $lock->release();
          continue;
        }
      }

      /* Queue up recipients for the child job being launched */
      // Get the mailer
      // make it a persistent connection, CRM-9349
      $mailerType = array_search('Mass Mailing', CRM_Core_BAO_MailSettings::$_mailerTypes);
      $mailer = CRM_Core_Config::getMailer($mailerType);
      // refs #30585, test mailer first before start

      if ($job->status != 'Running') {

        $transaction = new CRM_Core_Transaction();

        // have to queue it up based on the offset and limits
        // get the parent ID, and limit and offset
        $job->queue($testParams);

        // Mark up the starting time
        $saveJob = new CRM_Mailing_DAO_Job();
        $saveJob->id = $job->id;
        $saveJob->start_date = date('YmdHis');
        $saveJob->status = 'Running';
        $saveJob->save();

        $transaction->commit();
      }

      // Compose and deliver each child job
      $isComplete = $job->deliver($mailer, $testParams);


      CRM_Utils_Hook::post('create', 'CRM_Mailing_DAO_Spool', $job->id, $isComplete);

      // Mark the child complete
      if ($isComplete) {
        /* Finish the job */

        $transaction = new CRM_Core_Transaction();

        $saveJob = new CRM_Mailing_DAO_Job();
        $saveJob->id = $job->id;
        $saveJob->end_date = date('YmdHis');
        $saveJob->status = 'Complete';
        $saveJob->save();

        $transaction->commit();

        // don't mark the mailing as complete
      }

      // Release the child joblock
      $lock->release();

      if ($testParams) {
        return $isComplete;
      }
    }
  }

  // post process to determine if the parent job
  // as well as the mailing is complete after the run
  public static function runJobs_post() {

    $job = new CRM_Mailing_BAO_Job();

    $mailing = new CRM_Mailing_BAO_Mailing();

    $config = CRM_Core_Config::singleton();
    $jobTable = CRM_Mailing_DAO_Job::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();

    $currentTime = date('YmdHis');
    $domainID = CRM_Core_Config::domainID();

    $query = "
SELECT j.*
  FROM $jobTable j
  INNER JOIN $mailingTable m ON m.id = j.mailing_id AND m.domain_id = {$domainID} AND m.is_hidden <= 0
WHERE j.is_test = 0
  AND j.scheduled_date <= $currentTime
  AND j.status = 'Running'
  AND j.end_date IS null
  AND (j.job_type != 'child' OR j.job_type is NULL)
ORDER BY j.scheduled_date ASC, j.start_date ASC";

    $job->query($query);

    // For each parent job that is running, let's look at their child jobs
    while ($job->fetch()) {

      $child_job = new CRM_Mailing_BAO_Job();

      $child_job_sql = "
SELECT count(j.id)
  FROM civicrm_mailing_job j
  INNER JOIN civicrm_mailing m ON m.id = j.mailing_id
WHERE j.job_type = 'child'
  AND j.parent_id = %1
  AND j.status <> 'Complete'";
      $params = [1 => [$job->id, 'Integer']];

      $anyChildLeft = CRM_Core_DAO::singleValueQuery($child_job_sql, $params);

      // all of the child jobs are complete, update
      // the parent job as well as the mailing status
      if (!$anyChildLeft) {


        $transaction = new CRM_Core_Transaction();

        $saveJob = new CRM_Mailing_DAO_Job();
        $saveJob->id = $job->id;
        $saveJob->end_date = date('YmdHis');
        $saveJob->status = 'Complete';
        $saveJob->save();

        $mailing->reset();
        $mailing->id = $job->mailing_id;
        $mailing->is_completed = TRUE;
        $mailing->save();
        $transaction->commit();
      }
    }
  }


  // before we run jobs, we need to split the jobs
  public static function runJobs_pre($offset = 200) {
    $job = new CRM_Mailing_BAO_Job();

    $config = CRM_Core_Config::singleton();
    $jobTable = CRM_Mailing_DAO_Job::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();

    $currentTime = date('YmdHis');

    $domainID = CRM_Core_Config::domainID();

    // Select all the mailing jobs that are created from
    // when the mailing is submitted or scheduled.
    $query = "
SELECT j.*, m.dedupe_email
  FROM $jobTable j
  INNER JOIN $mailingTable m ON m.id = j.mailing_id AND m.domain_id = {$domainID}
WHERE j.is_test = 0
  AND ( j.start_date IS null AND j.scheduled_date <= $currentTime AND j.status = 'Scheduled' AND j.end_date IS null )
  AND ( j.job_type is NULL OR j.job_type <> 'child' )
ORDER BY j.scheduled_date ASC, j.start_date ASC LIMIT 1";
    $job->query($query);



    // For each of the "Parent Jobs" we find, we split them into
    // X Number of child jobs
    $processedMailing = [];
    while ($job->fetch()) {
      // refs #22088 calculate recipients before job start
      $hasChild = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM $jobTable WHERE is_test = 0 AND job_type = 'child'");
      if (!isset($processedMailing[$job->mailing_id]) && !$hasChild) {
        $rlockName = "civimail.mailing_recipients.{$job->id}";
        $rlock = new CRM_Core_Lock($rlockName);
        if (!$rlock->isAcquired()) {
          continue;
        }
        CRM_Mailing_BAO_Mailing::getRecipients($job->mailing_id, $job->mailing_id, NULL, NULL, TRUE, $job->dedupe_email);
        $rlock->release();
        $processedMailing[$job->mailing_id] = TRUE;
        sleep(mt_rand(10, 40));
      }
      // still use job level lock for each child job
      $lockName = "civimail.job.{$job->id}";

      $lock = new CRM_Core_Lock($lockName);
      if (!$lock->isAcquired()) {
        continue;
      }

      // Re-fetch the job status in case things
      // changed between the first query and now
      // to avoid race conditions
      $job->status = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Job',
        $job->id,
        'status'
      );
      if ($job->status != 'Scheduled') {
        $lock->release();
        continue;
      }

      $job->split_job($offset);

      // update the status of the parent job

      $transaction = new CRM_Core_Transaction();

      $saveJob = new CRM_Mailing_DAO_Job();
      $saveJob->id = $job->id;
      $saveJob->start_date = date('YmdHis');
      $saveJob->status = 'Running';
      $saveJob->save();

      $transaction->commit();

      // Release the job lock
      $lock->release();
    }
  }

  // Split the parent job into n number of child job based on an offset
  // If null or 0 , we create only one child job
  public function split_job($offset = 200) {

    $recipient_count = CRM_Mailing_BAO_Recipients::mailingSize($this->mailing_id);

    $jobTable = CRM_Mailing_DAO_Job::getTableName();



    $dao = new CRM_Core_DAO();

    $sql = "
INSERT INTO civicrm_mailing_job
(`mailing_id`, `scheduled_date`, `status`, `job_type`, `parent_id`, `job_offset`, `job_limit`)
VALUES (%1, %2, %3, %4, %5, %6, %7)
";
    $params = [1 => [$this->mailing_id, 'Integer'],
      2 => [$this->scheduled_date, 'String'],
      3 => ['Scheduled', 'String'],
      4 => ['child', 'String'],
      5 => [$this->id, 'Integer'],
      6 => [0, 'Integer'],
      7 => [$recipient_count, 'Integer'],
    ];

    // create one child job if the mailing size is less than the offset
    // probably use a CRM_Mailing_DAO_Job( );
    if (empty($offset) ||
      $recipient_count <= $offset
    ) {
      CRM_Core_DAO::executeQuery($sql, $params);
    }
    else {
      // Creating 'child jobs'
      $jobLimit = $offset;
      for ($i = 0; $i < $recipient_count; $i = $i + $offset) {
        $params[6][0] = $i;
        $params[7][0] = $jobLimit;
        CRM_Core_DAO::executeQuery($sql, $params);
      }
    }
  }

  public function queue($testParams = NULL) {

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->mailing_id;
    if (!empty($testParams)) {
      $mailing->getTestRecipients($testParams);
    }
    else {
      // We are still getting all the recipients from the parent job
      // so we don't mess with the include/exclude logic.

      $recipients = CRM_Mailing_BAO_Recipients::mailingQuery($this->mailing_id, $this->job_offset, $this->job_limit);

      // FIXME: this is not very smart, we should move this to one DB call
      // INSERT INTO ... SELECT FROM ..
      // the thing we need to figure out is how to generate the hash automatically
      $now = time();
      $params = [];
      $count = 0;
      while ($recipients->fetch()) {
        $params[] = [
          $this->id,
          $recipients->email_id,
          $recipients->contact_id,
        ];
        $count++;
        if ($count % CRM_Core_DAO::BULK_MAIL_INSERT_COUNT == 0) {
          CRM_Mailing_Event_BAO_Queue::bulkCreate($params, $now);
          $count = 0;
          $params = [];
        }
      }

      if (!empty($params)) {
        CRM_Mailing_Event_BAO_Queue::bulkCreate($params, $now);
      }
    }
  }

  /**
   * Send the mailing
   *
   * @param object $mailer  A Mail settings object retrieve from CRM_Core_Config::getMailer
   *
   * @return void
   * @access public
   */
  public function deliver(&$mailer, $testParams = NULL) {

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $this->mailing_id;
    $mailing->find(TRUE);
    $mailing->free();
    if ($mailing->is_hidden) {
      return FALSE;
    }

    $eq = new CRM_Mailing_Event_BAO_Queue();
    $eqTable = CRM_Mailing_Event_BAO_Queue::getTableName();
    $emailTable = CRM_Core_BAO_Email::getTableName();
    $contactTable = CRM_Contact_BAO_Contact::getTableName();
    $edTable = CRM_Mailing_Event_BAO_Delivered::getTableName();
    $ebTable = CRM_Mailing_Event_BAO_Bounce::getTableName();

    $query = "  SELECT      $eqTable.id,
                                $emailTable.email as email,
                                $eqTable.contact_id,
                                $eqTable.hash
                    FROM        $eqTable
                    INNER JOIN  $emailTable
                            ON  $eqTable.email_id = $emailTable.id
                    INNER JOIN  $contactTable
                            ON  $contactTable.id = $emailTable.contact_id
                    LEFT JOIN   $edTable
                            ON  $eqTable.id = $edTable.event_queue_id
                    LEFT JOIN   $ebTable
                            ON  $eqTable.id = $ebTable.event_queue_id
                    WHERE       $eqTable.job_id = " . $this->id . "
                        AND     $edTable.id IS null
                        AND     $ebTable.id IS null
                        AND		$contactTable.is_opt_out = 0 AND $contactTable.is_deceased = 0";

    $eq->query($query);

    static $config = NULL;
    static $mailsProcessed = 0;

    if ($config == NULL) {
      $config = CRM_Core_Config::singleton();
    }

    $job_date = CRM_Utils_Date::isoToMysql($this->scheduled_date);
    $fields = [];

    if (!empty($testParams)) {
      $mailing->from_name = ts('CiviCRM Test Mailer (%1)',
        [1 => $mailing->from_name]
      );
      $mailing->subject = ts('Test Mailing:') . ' ' . $mailing->subject;
    }

    CRM_Mailing_BAO_Mailing::tokenReplace($mailing);

    // get and format attachments

    $attachments = &CRM_Core_BAO_File::getEntityFile('civicrm_mailing',
      $mailing->id
    );

    // refs #32614, disable smarty evaluation functions
    $isDelivered = FALSE;

    // make sure that there's no more than $config->mailerBatchLimit mails processed in a run
    $ranQueue = 0;
    while ($eq->fetch()) {
      $ranQueue++;
      // if ( ( $mailsProcessed % 100 ) == 0 ) {
      // CRM_Utils_System::xMemory( "$mailsProcessed: " );
      // }

      if ($config->mailerBatchLimit > 0 &&
        $mailsProcessed >= $config->mailerBatchLimit
      ) {
        if (!empty($fields)) {
          $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
        }
        $eq->free();
        return FALSE;
      }
      $mailsProcessed++;

      $fields[] = [
        'id' => $eq->id,
        'hash' => $eq->hash,
        'contact_id' => $eq->contact_id,
        'email' => $eq->email,
      ];
      if (count($fields) == self::MAX_CONTACTS_TO_PROCESS) {
        $isDelivered = $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
        if (!$isDelivered) {
          $eq->free();
          return $isDelivered;
        }
        $fields = [];
      }
    }

    $eq->free();

    if (!empty($fields)) {
      $isDelivered = $this->deliverGroup($fields, $mailing, $mailer, $job_date, $attachments);
    }
    else if (!$ranQueue) { // running job in empty queue
      $isDelivered = TRUE;
    }
    return $isDelivered;
  }

  /**
   * Undocumented function
   *
   * @param array $fields
   * @param CRM_Mailing_BAO_Mailing $mailing
   * @param object $mailer get from CRM_Core_Config::getMailer
   * @param string $job_date mysql date string from job->scheduled_date
   * @param array $attachments
   *
   * @return bool
   */
  public function deliverGroup(&$fields, &$mailing, &$mailer, &$job_date, &$attachments) {
    static $smtpConnectionErrors = 0;
    if (!empty($mailing->is_hidden)) {
      CRM_Core_Error::fatal('Mailing is hidden. We can not deliver hidden mailing by mailing job.');
      return;
    }

    if (!is_object($mailer) || empty($fields)) {
      CRM_Core_Error::fatal();
    }

    // get the return properties
    $returnProperties = $mailing->getReturnProperties();
    $params = $targetParams = $deliveredParams = [];
    $count = 0;

    foreach ($fields as $key => $field) {
      $params[] = $field['contact_id'];
    }

    $details = CRM_Utils_Token::getTokenDetails($params,
      $returnProperties,
      FALSE, TRUE, NULL,
      $mailing->getFlattenedTokens(),
      get_class($this)
    );

    foreach ($fields as $key => $field) {
      $contactID = $field['contact_id'];
      /* Compose the mailing */


      $recipient = $replyToEmail = NULL;
      $replyValue = strcmp($mailing->replyto_email, $mailing->from_email);
      if ($replyValue) {
        $replyToEmail = $mailing->replyto_email;
      }

      $message = &$mailing->compose($this->id, $field['id'], $field['hash'],
        $field['contact_id'], $field['email'],
        $recipient, FALSE, $details[0][$contactID], $attachments,
        FALSE, NULL, $replyToEmail
      );

      // handling compose failing
      if (empty($message) || !is_object($message) || !is_a($message, 'Mail_mime')) {
        CRM_Core_Error::debug_log_message("Failed to compose message for queue: {$field['id']}, contact {$field['contact_id']}, email {$field['email']}");
        $params = [
          'event_queue_id' => $field['id'],
          'job_id' => $this->id,
          'hash' => $field['hash'],
        ];
        $params = array_merge($params, CRM_Mailing_BAO_BouncePattern::match('Mailing compose system error, check system log for detail.'));
        CRM_Mailing_Event_BAO_Bounce::create($params);

        continue;
      }

      /* Send the mailing */
      $body = &$message->get();
      $headers = &$message->headers();
      // make $recipient actually be the *encoded* header, so as not to baffle Mail_RFC822, CRM-5743
      $recipient = $headers['To'];
      $result = NULL;

      // disable error reporting on real mailings (but leave error reporting for tests), CRM-5744
      if ($job_date) {
        CRM_Core_Error::ignoreException();
      }

      // refs #30289, for valid DKIM
      if (empty($headers['Sender']) && !empty($mailer->_mailSetting['return_path'])) {
        $headers['Sender'] = $mailer->_mailSetting['return_path'];
      }

      // use localpart as regular expression to check if recipient needs another mailer
      $sent = FALSE;
      if (!empty($mailer->_filters)) {
        foreach($mailer->_filters as &$filter) {
          if ($filter->_mailSetting['localpart']) {
            if(preg_match('/'.$filter->_mailSetting['localpart'].'/i', $recipient)) {
              $result = $filter->send($recipient, $headers, $body, $this->id);
              $sent = TRUE;
              break;
            }
          }
        }
      }
      if (!$sent) {
        $result = $mailer->send($recipient, $headers, $body, $this->id);
      }

      if ($job_date) {
        CRM_Core_Error::setCallback();
      }

      if (is_a($result, 'PEAR_Error')) {
        // CRM-9191
        $msg = $result->getMessage();
        $code = $result->getCode();
        if (strpos($msg, 'to write to socket') !== FALSE) {
          // lets log this message and code
          CRM_Core_Error::debug_log_message("SMTP Socket Error. Message: $msg, Code: $code");

          // these are socket write errors which most likely means smtp connection errors
          // lets skip them
          $smtpConnectionErrors++;
          if ($smtpConnectionErrors > 2) {
            // seems like we have too many of them in a row, we should
            // write stuff to disk and abort the cron job
            $this->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);

            CRM_Core_Error::debug_log_message("Too many SMTP Socket Errors. Exiting");
            CRM_Utils_System::civiExit();
            return FALSE;
          }
        }
        else {
          CRM_Core_Error::debug_log_message("SMTP Error. Message: $msg, Code: $code");
        }

        /* Register the bounce event */
        $params = [
          'event_queue_id' => $field['id'],
          'job_id' => $this->id,
          'hash' => $field['hash'],
        ];
        $params = array_merge($params, CRM_Mailing_BAO_BouncePattern::match($result->getMessage()));
        CRM_Mailing_Event_BAO_Bounce::create($params);

        // sleep 0.3 sec each error, to prevent continue smtp connection error
        usleep(300000);
      }
      else {
        /* Register the delivery event */
        $deliveredParams[] = $field['id'];
        $targetParams[] = $field['contact_id'];

        $count++;
        if ($count % CRM_Core_DAO::BULK_MAIL_INSERT_COUNT == 0) {
          $this->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);
          $count = 0;

          // hack to stop mailing job at run time, CRM-4246.
          // to avoid making too many DB calls for this rare case
          // lets do it when we snapshot
          $status = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Job', $this->id, 'status');
          if ($status != 'Running') {
            return FALSE;
          }
        }
      }

      unset($result);

      // seems like a successful delivery or bounce, lets decrement error count
      // only if we have smtp connection errors
      if ($smtpConnectionErrors > 0) {
        $smtpConnectionErrors--;
      }
    }

    $result = $this->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);
    return $result;
  }

  /**
   * cancel a mailing
   *
   * @param int $mailingId  the id of the mailing to be canceled
   * @static
   */
  public static function cancel($mailingId) {
    $sql = "
SELECT *
FROM   civicrm_mailing_job
WHERE  mailing_id = %1
AND    is_test = 0
AND    ( ( job_type IS NULL ) OR
           job_type <> 'child' )
";
    $params = [1 => [$mailingId, 'Integer']];
    $job = CRM_Core_DAO::executeQuery($sql, $params);
    if ($job->fetch() &&
      in_array($job->status, ['Scheduled', 'Running', 'Paused'])
    ) {

      $newJob = new CRM_Mailing_BAO_Job();
      $newJob->id = $job->id;
      $newJob->end_date = date('YmdHis');
      $newJob->status = 'Canceled';
      $newJob->save();

      // also cancel all child jobs
      $sql = "
UPDATE civicrm_mailing_job
SET    status = 'Canceled',
       end_date = %2
WHERE  parent_id = %1
AND    is_test = 0
AND    job_type = 'child'
AND    status IN ( 'Scheduled', 'Running', 'Paused' )
";
      $params = [1 => [$job->id, 'Integer'],
        2 => [date('YmdHis'), 'Timestamp'],
      ];
      CRM_Core_DAO::executeQuery($sql, $params);

      CRM_Core_Session::setStatus(ts('The mailing has been canceled.'));
    }
  }

  /**
   * Return a translated status enum string
   *
   * @param string $status        The status enum
   *
   * @return string               The translated version
   * @access public
   * @static
   */
  public static function status($status) {
    static $translation = NULL;

    if (empty($translation)) {
      $translation = [
        'Scheduled' => ts('Scheduled'),
        'Running' => ts('Running'),
        'Complete' => ts('Complete'),
        'Paused' => ts('Paused'),
        'Canceled' => ts('Canceled'),
      ];
    }
    return CRM_Utils_Array::value($status, $translation, ts('Not scheduled'));
  }

  /**
   * Return a workflow clause for use in SQL queries,
   * to only process jobs that are approved.
   *
   * @return string        For use in a WHERE clause
   * @access public
   * @static
   */
  public static function workflowClause() {
    // add an additional check and only process
    // jobs that are approved
    /*

    if (CRM_Mailing_Info::workflowEnabled()) {

      $approveOptionID = CRM_Core_OptionGroup::getValue('mail_approval_status',
        'Approved',
        'name'
      );
      if ($approveOptionID) {
        return " AND m.approval_status_id = $approveOptionID ";
      }
    }
    */

    return '';
  }

  public function writeToDB(&$deliveredParams,
    &$targetParams,
    &$mailing,
    $job_date
  ) {
    static $activityTypeID = NULL;

    if (!empty($deliveredParams)) {
      CRM_Mailing_Event_BAO_Delivered::bulkCreate($deliveredParams);
      $deliveredParams = [];
    }

    $result = TRUE;
    if (!empty($targetParams) &&
      !empty($mailing->scheduled_id)
    ) {

      if (!$activityTypeID) {
        $activityTypeID = CRM_Core_OptionGroup::getValue('activity_type',
          'Bulk Email',
          'name'
        );
        if (!$activityTypeID) {
          CRM_Core_Error::fatal();
        }
      }

      $activity = [
        'source_contact_id' => $mailing->scheduled_id,
        'target_contact_id' => $targetParams,
        'activity_type_id' => $activityTypeID,
        'source_record_id' => $this->mailing_id,
        'activity_date_time' => $job_date,
        'subject' => $mailing->subject,
        'status_id' => 2,
        'deleteActivityTarget' => FALSE,
        'campaign_id' => $mailing->campaign_id,
      ];

      //check whether activity is already created for this mailing.
      //if yes then create only target contact record.
      $query = "
SELECT id
FROM   civicrm_activity
WHERE  civicrm_activity.activity_type_id = %1
AND    civicrm_activity.source_record_id = %2";

      $queryParams = [1 => [$activityTypeID, 'Integer'],
        2 => [$this->mailing_id, 'Integer'],
      ];
      $activityID = CRM_Core_DAO::singleValueQuery($query,
        $queryParams
      );

      if ($activityID) {
        $activity['id'] = $activityID;
      }


      if (is_a(CRM_Activity_BAO_Activity::create($activity),
          'CRM_Core_Error'
        )) {
        $result = FALSE;
      }

      $targetParams = [];
    }

    return $result;
  }
}

