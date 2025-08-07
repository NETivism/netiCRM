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











class CRM_Mailing_Event_BAO_Unsubscribe extends CRM_Mailing_Event_DAO_Unsubscribe {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Unsubscribe a contact from the domain
   *
   * @param int $job_id       The job ID
   * @param int $queue_id     The Queue Event ID of the recipient
   * @param string $hash      The hash
   *
   * @return boolean          Was the contact succesfully unsubscribed?
   * @access public
   * @static
   */
  public static function unsub_from_domain($job_id, $queue_id, $hash) {
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
    if (!$q) {
      return FALSE;
    }
    CRM_Contact_BAO_Contact::redirectPreferredLanguage($q->contact_id);

    $transaction = new CRM_Core_Transaction();
    $contact = new CRM_Contact_BAO_Contact();
    $contact->id = $q->contact_id;
    $contact->is_opt_out = TRUE;
    $contact->save();

    $ue = new CRM_Mailing_Event_BAO_Unsubscribe();
    $ue->event_queue_id = $queue_id;
    $ue->org_unsubscribe = 1;
    $ue->time_stamp = date('YmdHis');
    $ue->save();

    $shParams = [
      'contact_id' => $q->contact_id,
      'group_id' => NULL,
      'status' => 'Removed',
      'method' => 'Email',
      'tracking' => $ue->id,
    ];
    CRM_Contact_BAO_SubscriptionHistory::create($shParams);

    $transaction->commit();

    return TRUE;
  }

  /**
   * Unsubscribe a contact from all groups that received this mailing
   *
   * @param int $job_id       The job ID
   * @param int $queue_id     The Queue Event ID of the recipient
   * @param string $hash      The hash
   * @param boolean $preview  If true return the list of groups and not execute unsubscribe
   *
   * @return array|null $groups    Array of all groups from which the contact was removed, or null if the queue event could not be found.
   * @access public
   * @static
   */
  public static function &unsub_from_mailing($job_id, $queue_id, $hash, $preview = FALSE) {
    /* First make sure there's a matching queue event */
    $q = CRM_Mailing_Event_BAO_Queue::verify($job_id, $queue_id, $hash);
    $success = NULL;
    if (!$q) {
      return $success;
    }

    $contact_id = $q->contact_id;

    $transaction = new CRM_Core_Transaction();

    $do = new CRM_Core_DAO();
    $mg = CRM_Mailing_DAO_Group::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $group = CRM_Contact_BAO_Group::getTableName();
    $gc = CRM_Contact_BAO_GroupContact::getTableName();

    //We Need the mailing Id for the hook...
    $do->query("SELECT $job.mailing_id as mailing_id 
                     FROM   $job 
                     WHERE $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer'));
    $do->fetch();
    $mailing_id = $do->mailing_id;

    $do->query("
            SELECT      $mg.entity_table as entity_table,
                        $mg.entity_id as entity_id,
                        $mg.group_type as group_type
            FROM        $mg
            INNER JOIN  $job
                ON      $job.mailing_id = $mg.mailing_id
            INNER JOIN  $group
                ON      $mg.entity_id = $group.id
            WHERE       $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer') . "
                AND     $mg.group_type IN ('Include', 'Base') 
                AND     $group.is_hidden = 0
            ORDER BY    $mg.id ASC"
    );

    /* Make a list of groups and a list of prior mailings that received 
         * this mailing */



    $groups = [];
    $base_groups = [];
    $mailings = [];

    while ($do->fetch()) {
      if ($do->entity_table == $group) {
        if ($do->group_type == 'Base') {
          $base_groups[$do->entity_id] = NULL;
        }
        else {
          $groups[$do->entity_id] = NULL;
        }
      }
      elseif ($do->entity_table == $mailing) {
        $mailings[] = $do->entity_id;
      }
    }

    /* As long as we have prior mailings, find their groups and add to the
         * list */
    $mailings_this_job = $mailings;


    while (!empty($mailings)) {
      $do->query("
                SELECT      $mg.entity_table as entity_table,
                            $mg.entity_id as entity_id
                FROM        $mg
                WHERE       $mg.mailing_id IN (" . CRM_Utils_Array::implode(', ', $mailings) . ")
                    AND     $mg.group_type = 'Include'");

      $mailings = [];

      while ($do->fetch()) {
        if ($do->entity_table == $group) {
          $groups[$do->entity_id] = TRUE;
        }
        elseif ($do->entity_table == $mailing && !in_array($do->entity_id, $mailings_this_job)) {
          $mailings[] = $do->entity_id;
        }
      }
    }

    //Pass the groups to be unsubscribed from through a hook.

    $group_ids = array_keys($groups);
    $base_group_ids = array_keys($base_groups);

    // If you doesn't choose any group as receiver and send test email. You will see this message when unsubscribing mailing group.
    if ( empty($group_ids) && empty($base_group_ids) ) {
      CRM_Core_Error::fatal(ts('This mailing event doesn\'t include any group.'));
    }

    //    CRM_Utils_Hook::unsubscribeGroups('unsubscribe', $mailing_id, $contact_id, $group_ids, $base_group_ids);

    /* Now we have a complete list of recipient groups.  Filter out all
         * those except smart groups, those that the contact belongs to and
         * base groups from search based mailings */


    $baseGroupClause = '';
    if (count($group_ids) && empty($base_group_ids)) {
      $base_group_ids = [
        reset($group_ids),
      ];
    }
    if (!empty($base_group_ids)) {
      $baseGroupClause = "OR  $group.id IN(" . CRM_Utils_Array::implode(', ', $base_group_ids) . ")";
    }
    $do->query("
            SELECT      $group.id as group_id,
                        $group.title as title,
                        $group.description as description
            FROM        $group
            LEFT JOIN   $gc
                ON      $gc.group_id = $group.id
            WHERE       $group.id IN (" . CRM_Utils_Array::implode(', ', array_merge($group_ids, $base_group_ids)) . ")
                AND     $group.is_hidden = 0
                AND     ($group.saved_search_id is not null
                            OR  ($gc.contact_id = $contact_id
                                AND $gc.status = 'Added')
                            $baseGroupClause
                        )");

    if ($preview) {
      $returnGroups = [];
      while ($do->fetch()) {
        $returnGroups[$do->group_id] = [
          'title' => $do->title,
          'description' => $do->description,
        ];
      }
      return $returnGroups;
    }
    else {
      while ($do->fetch()) {
        $groups[$do->group_id] = $do->title;
      }
    }

    $contacts = [$contact_id];
    foreach ($groups as $group_id => $group_name) {
      $notremoved = FALSE;
      if ($group_name) {
        if (in_array($group_id, $base_group_ids)) {
          list($total, $removed, $notremoved) = CRM_Contact_BAO_GroupContact::addContactsToGroup($contacts, $group_id, 'Email', 'Removed');
        }
        else {
          list($total, $removed, $notremoved) = CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contacts, $group_id, 'Email');
        }
      }
      if ($notremoved) {
        unset($groups[$group_id]);
      }
    }

    $ue = new CRM_Mailing_Event_BAO_Unsubscribe();
    $ue->event_queue_id = $queue_id;
    $ue->org_unsubscribe = 0;
    $ue->time_stamp = date('YmdHis');
    $ue->save();

    $transaction->commit();
    return $groups;
  }

  /**
   * Send a reponse email informing the contact of the groups from which he
   * has been unsubscribed.
   *
   * @param string $queue_id      The queue event ID
   * @param array $groups         List of group IDs
   * @param bool $is_domain       Is this domain-level?
   * @param int $job              The job ID
   *
   * @return void
   * @access public
   * @static
   */
  public static function send_unsub_response($queue_id, $groups, $is_domain, $job) {
    $config = CRM_Core_Config::singleton();
    $domain = CRM_Core_BAO_Domain::getDomain();

    $jobTable = CRM_Mailing_BAO_Job::getTableName();
    $mailingTable = CRM_Mailing_DAO_Mailing::getTableName();
    $contacts = CRM_Contact_DAO_Contact::getTableName();
    $email = CRM_Core_DAO_Email::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();

    //get the default domain email address.
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    $dao = new CRM_Mailing_BAO_Mailing();
    $dao->query("   SELECT * FROM $mailingTable 
                        INNER JOIN $jobTable ON
                            $jobTable.mailing_id = $mailingTable.id 
                        WHERE $jobTable.id = $job");
    $dao->fetch();

    $component = new CRM_Mailing_BAO_Component();

    if ($is_domain) {
      $component->id = $dao->optout_id;
    }
    else {
      $component->id = $dao->unsubscribe_id;
    }
    $component->find(TRUE);

    $html = $component->body_html;
    if ($component->body_text) {
      $text = $component->body_text;
    }
    else {
      $text = CRM_Utils_String::htmlToText($component->body_html);
    }

    $eq = new CRM_Core_DAO();
    $eq->query(
      "SELECT     $contacts.preferred_mail_format as format,
                    $contacts.id as contact_id,
                    $email.email as email,
                    $queue.hash as hash
        FROM        $contacts
        INNER JOIN  $queue ON $queue.contact_id = $contacts.id
        INNER JOIN  $email ON $queue.email_id = $email.id
        WHERE       $queue.id = " . CRM_Utils_Type::escape($queue_id, 'Integer')
    );
    $eq->fetch();

    if ($groups) {
      foreach ($groups as $key => $value) {
        if (!$value) {
          unset($groups[$key]);
        }
      }
    }

    $message = new Mail_mime("\n");

    list($addresses, $urls) = CRM_Mailing_BAO_Mailing::getVerpAndUrls($job, $queue_id, $eq->hash, $eq->email);
    $bao = new CRM_Mailing_BAO_Mailing();
    $bao->body_text = $text;
    $bao->body_html = $html;
    $tokens = $bao->getTokens();
    if ($eq->format == 'HTML' || $eq->format == 'Both') {
      $html = CRM_Utils_Token::replaceDomainTokens($html, $domain, TRUE, $tokens['html']);
      $html = CRM_Utils_Token::replaceUnsubscribeTokens($html, $domain, $groups, TRUE, $eq->contact_id, $eq->hash);
      $html = CRM_Utils_Token::replaceActionTokens($html, $addresses, $urls, TRUE, $tokens['html']);
      $html = CRM_Utils_Token::replaceMailingTokens($html, $dao, NULL, $tokens['html']);
      $message->setHTMLBody($html);
    }
    if (!$html || $eq->format == 'Text' || $eq->format == 'Both') {
      $text = CRM_Utils_Token::replaceDomainTokens($text, $domain, FALSE, $tokens['text']);
      $text = CRM_Utils_Token::replaceUnsubscribeTokens($text, $domain, $groups, FALSE, $eq->contact_id, $eq->hash);
      $text = CRM_Utils_Token::replaceActionTokens($text, $addresses, $urls, FALSE, $tokens['text']);
      $text = CRM_Utils_Token::replaceMailingTokens($text, $dao, NULL, $tokens['text']);
      $message->setTxtBody($text);
    }


    $emailDomain = CRM_Core_BAO_MailSettings::defaultDomain();

    $headers = [
      'Subject' => $component->subject,
      'From' => "\"$domainEmailName\" <do-not-reply@$emailDomain>",
      'To' => $eq->email,
      'Reply-To' => "do-not-reply@$emailDomain",
      'Return-Path' => "do-not-reply@$emailDomain",
    ];
    CRM_Mailing_BAO_Mailing::addMessageIdHeader($headers, 'u', $job, $queue_id, $eq->hash);

    $b = CRM_Utils_Mail::setMimeParams($message);
    $h = &$message->headers($headers);

    $mailer = &$config->getMailer();

    if (is_object($mailer)) {
      $mailer->send($eq->email, $h, $b);
      CRM_Core_Error::setCallback();
    }
  }

  /**
   * Get row count for the event selector
   *
   * @param int $mailing_id       ID of the mailing
   * @param int $job_id           Optional ID of a job to filter on
   * @param boolean $is_distinct  Group by queue ID?
   *
   * @return int                  Number of rows in result set
   * @access public
   * @static
   */
  public static function getTotalCount($mailing_id, $job_id = NULL,
    $is_distinct = FALSE
  ) {
    $dao = new CRM_Core_DAO();

    $unsub = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();

    $query = "
            SELECT      COUNT($unsub.id) as unsubs
            FROM        $unsub
            INNER JOIN  $queue
                    ON  $unsub.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $dao->query($query);
    $dao->fetch();
    if ($is_distinct) {
      return $dao->N;
    }
    else {
      return $dao->unsubs ? $dao->unsubs : 0;
    }
  }

  /**
   * Get rows for the event browser
   *
   * @param int $mailing_id       ID of the mailing
   * @param int $job_id           optional ID of the job
   * @param boolean $is_distinct  Group by queue id?
   * @param int $offset           Offset
   * @param int $rowCount         Number of rows
   * @param array $sort           sort array
   *
   * @return array                Result set
   * @access public
   * @static
   */
  public static function &getRows($mailing_id, $job_id = NULL,
    $is_distinct = FALSE, $offset = NULL, $rowCount = NULL, $sort = NULL
  ) {

    $dao = new CRM_Core_Dao();

    $unsub = self::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $unsub.time_stamp as date,
                        $unsub.org_unsubscribe as org_unsubscribe
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $unsub
                    ON  $unsub.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
                    AND $job.is_test = 0
            WHERE       $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $orderBy = "sort_name ASC, {$unsub}.time_stamp DESC";
    if ($sort) {
      if (is_string($sort)) {
        $orderBy = $sort;
      }
      else {
        $orderBy = trim($sort->orderBy());
      }
    }

    $query .= " ORDER BY {$orderBy} ";

    if ($offset || $rowCount) {
      //Added "||$rowCount" to avoid displaying all records on first page
      $query .= ' LIMIT ' . CRM_Utils_Type::escape($offset, 'Integer') . ', ' . CRM_Utils_Type::escape($rowCount, 'Integer');
    }

    $dao->query($query);

    $results = [];

    while ($dao->fetch()) {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[] = [
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'org' => $dao->org_unsubscribe ? ts('Yes') : ts('No'),
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

  public static function getContactInfo($queueID) {
    $query = "
SELECT DISTINCT(civicrm_mailing_event_queue.contact_id) as contact_id,
       civicrm_contact.display_name as display_name
       civicrm_email.email as email
  FROM civicrm_mailing_event_queue,
       civicrm_contact,
       civicrm_email
 WHERE civicrm_mailing_event_queue.contact_id = civicrm_contact.id
   AND civicrm_mailing_event_queue.email_id = civicrm_email.id
   AND civicrm_mailing_event_queue.id = " . CRM_Utils_Type::escape($queueID, 'Integer');

    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    $displayName = 'Unknown';
    $email = 'Unknown';
    if ($dao->fetch()) {
      $displayName = $dao->display_name;
      $email = $dao->email;
    }

    return [$displayName, $email];
  }
}

