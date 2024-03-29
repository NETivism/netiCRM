<?php
/**
 * Fetching mail by imap / pop3 acount stored in civicrm_mail_settings
 */
function run_civimail_process(){
  global $config;

  $lock = new CRM_Core_Lock('CiviMailProcessor');

  if ($lock->isAcquired()) {
      // try to unset any time limits
      if (!ini_get('safe_mode')) set_time_limit(0);
      
      // cleanup directories with old mail files (if they exist): CRM-4452
      CiviMailProcessor::cleanupDir($config->customFileUploadDir . DIRECTORY_SEPARATOR . 'CiviMail.ignored');
      CiviMailProcessor::cleanupDir($config->customFileUploadDir . DIRECTORY_SEPARATOR . 'CiviMail.processed');
      
      // if there are named sets of settings, use them - otherwise use the default (null)
      $names = isset($_REQUEST['names']) && is_array($_REQUEST['names']) ? $_REQUEST['names'] : array( null );
      
      foreach ($names as $name) {
          CiviMailProcessor::process($name);
      }
  } else {
      throw new Exception('Could not acquire lock, another CiviMailProcessor process is running');
  }

  $lock->release();
}
class CiviMailProcessor {
    /**
     * Delete old files from a given directory (recursively)
     *
     * @param string $dir  directory to cleanup
     * @param int    $age  files older than this many seconds will be deleted (default: 60 days)
     * @return void
     */
    static function cleanupDir($dir, $age = 5184000) {
        // return early if we can’t read/write the dir
        if (!is_writable($dir) or !is_readable($dir) or !is_dir($dir)) return;

        foreach (scandir($dir) as $file) {

            // don’t go up the directory stack and skip new files/dirs
            if ($file == '.' or $file == '..')           continue;
            if (filemtime("$dir/$file") > time() - $age) continue;

            // it’s an old file/dir, so delete/recurse
            is_dir("$dir/$file") ? self::cleanupDir("$dir/$file", $age) : unlink("$dir/$file");
        }
    }

    /**
     * Process the mailbox defined by the named set of settings from civicrm_mail_settings
     *
     * @param string $name  name of the set of settings from civicrm_mail_settings (null for default set)
     * @return void
     */
    static function process($name = null) {

        $dao = CRM_Core_BAO_MailSettings::defaultDAO();
        $name ? $dao->name = $name : $dao->is_default = 1;
        if (empty($dao->domain)) {
            throw new Exception("Could not find entry named $name in civicrm_mail_settings");
        }

        $config = CRM_Core_Config::singleton();
        date_default_timezone_set("Asia/Taipei");
        $verpSeperator = preg_quote( $config->verpSeparator );
        $twoDigitStringMin = $verpSeperator . '(\d+)' . $verpSeperator . '(\d+)';
        $twoDigitString    = $twoDigitStringMin . $verpSeperator;
        $threeDigitString  = $twoDigitString . '(\d+)' . $verpSeperator;

        // FIXME: legacy regexen to handle CiviCRM 2.1 address patterns, with domain id and possible VERP part
        $commonRegex = '/^' . preg_quote($dao->localpart) . '(b|bounce|c|confirm|o|optOut|r|reply|re|e|resubscribe|u|unsubscribe)' . $threeDigitString . '([0-9a-f]{16})(-.*)?@' . preg_quote($dao->domain) . '$/';
        $subscrRegex = '/^' . preg_quote($dao->localpart) . '(s|subscribe)' . $twoDigitStringMin . '@' . preg_quote($dao->domain) . '$/';

        // a common-for-all-actions regex to handle CiviCRM 2.2 address patterns
        $regex = '/^' . preg_quote($dao->localpart) . '(b|c|e|o|r|u)' . $twoDigitString . '([0-9a-f]{16})@' . preg_quote($dao->domain) . '$/';

        // a tighter regex for finding bounce info in soft bounces’ mail bodies
        $rpRegex = '/Return-Path: ' . preg_quote($dao->localpart) . '(b)' . $twoDigitString . '([0-9a-f]{16})@' . preg_quote($dao->domain) . '/';

        // retrieve the emails
        require_once 'CRM/Mailing/MailStore.php';
        $store = CRM_Mailing_MailStore::getStore($name);

        // process 200 mail per times
        while ($mails = $store->fetchNext(10)) {
            $times++;
            if ($times > 20) {
              break;
            }
            foreach ($mails as $key => $mail) {

                // for every addressee: match address elements if it's to CiviMail
                $matches = array();
                foreach ($mail->to as $address) {
                    if (preg_match($regex, $address->email, $matches)) {
                        list($match, $action, $job, $queue, $hash) = $matches;
                        break;
                    // FIXME: the below elseifs should be dropped when we drop legacy support
                    } elseif (preg_match($commonRegex, $address->email, $matches)) {
                        list($match, $action, $_, $job, $queue, $hash) = $matches;
                        break;
                    } elseif (preg_match($subscrRegex, $address->email, $matches)) {
                        list($match, $action, $_, $job) = $matches;
                        break;
                    }
                }

                // CRM-5471: if $matches is empty, it still might be a soft bounce sent
                // to another address, so scan the body for ‘Return-Path: …bounce-pattern…’
                if (!$matches && preg_match($rpRegex, $mail->generateBody(), $matches)) {
                    list($match, $action, $job, $queue, $hash) = $matches;
                }

                // if all else fails, check Delivered-To for possible pattern
                if (!$matches && preg_match($regex, $mail->getHeader('Delivered-To'), $matches)) {
                    list($match, $action, $job, $queue, $hash) = $matches;
                }

                // if $matches is empty, this email is not CiviMail-bound
                if (!$matches) {
                    $store->markIgnored($key);
                    continue;
                }

                // get $replyTo from either the Reply-To header or from From
                // FIXME: make sure it works with Reply-Tos containing non-email stuff
                $replyTo = $mail->getHeader('Reply-To') ? $mail->getHeader('Reply-To') : $mail->from->email;

                // handle the action by passing it to the proper API call
                // FIXME: leave only one-letter cases when dropping legacy support
                switch ($action) {
                case 'b':
                case 'bounce':
                    $text = '';
                    if ($mail->body instanceof ezcMailText) {
                        $text = $mail->body->text;
                    } elseif ($mail->body instanceof ezcMailMultipart) {
                        if ($mail->body instanceof ezcMailMultipartRelated) {
                           foreach ($mail->body->getRelatedParts() as $part) {
                               if (isset($part->subType) && $part->subType == 'plain') {
                                   $text = $part->text;
                                   break;
                               }
                           }                           
                        } else {   
                           foreach ($mail->body->getParts() as $part) {
                               if (isset($part->subType) && $part->subType == 'plain') {
                                   $text = $part->text;
                                   break;
                               }
                           }
                        }
                    }
                    $params = array (
                      'version' => 3,
                      'job_id' => $job,
                      'event_queue_id' => $queue,
                      'hash' => $hash,
                      'body' => $text,
                    );
                    $result = civicrm_api('Mailing', 'event_bounce', $params);
                    break;
                case 'c':
                case 'confirm':
                    $params = array (
                      'version' => 3,
                      'job_id' => $job,
                      'subscribe_id' => $queue,
                      'hash' => $hash,
                    );
                    $result = civicrm_api('Mailing', 'event_confirm', $params);
                    break;
                case 'o':
                case 'optOut':
                    $params = array (
                      'version' => 3,
                      'job_id' => $job,
                      'event_queue_id' => $queue,
                      'hash' => $hash,
                    );
                    $result = civicrm_api('MailingGroup', 'event_domain_unsubscribe', $params);
                    break;
                case 'r':
                case 'reply':
                    // instead of text and HTML parts (4th and 6th params) send the whole email as the last param
                    $params = array (
                      'version' => 3,
                      'job_id' => $job,
                      'event_queue_id' => $queue,
                      'hash' => $hash,
                      'bodyTxt' => null,
                      'replyTo' => $replyTo,
                      'bodyHTML' => null,
                      'fullEmail' => $mail->generate()
                    );
                    $result = civicrm_api('Mailing', 'event_reply', $params);
                    break;
                case 'e':
                case 're':
                case 'resubscribe':
                    $params = array (
                      'version' => 3,
                      'job_id' => $job,
                      'event_queue_id' => $queue,
                      'hash' => $hash
                    );
                    $result = civicrm_api('MailingGroup', 'event_resubscribe', $params);
                    break;
                case 's':
                case 'subscribe':
                    $params = array (
                      'version' => 3,
                      'email' => $mail->from->email,
                      'group_id' => $job
                    );
                    $result = civicrm_api('MailingGroup', 'event_subscribe', $params);
                    break;
                case 'u':
                case 'unsubscribe':
                    $params = array (
                      'version' => 3,
                      'job_id' => $job,
                      'event_queue_id' => $queue,
                      'hash' => $hash
                    );
                    $result = civicrm_api('MailingGroup', 'event_unsubscribe', $params);
                    break;
                }
                if ($result['is_error']) {
                  $log = "Failed Processing: {$mail->subject}, Action: $action, Job ID: $job, Queue ID: $queue, Hash: $hash. Failed Reason: {$result['error_message']}";
                  CRM_Core_Error::debug_log_message($log);
                }
                
                $store->markProcessed($key);
            }
        }
    }
}

