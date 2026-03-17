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
 * @copyright CiviCRM LLC (c) 2004-2011
 *
 */

class CRM_Mailing_Event_BAO_Delivered extends CRM_Mailing_Event_DAO_Delivered {

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Create a new delivery event.
   *
   * @param array $params Associative array of delivery event values.
   *
   * @return CRM_Mailing_Event_BAO_Delivered|null The created delivery event object or null on failure.
   */
  public static function &create(&$params) {
    $q = &CRM_Mailing_Event_BAO_Queue::verify(
      $params['job_id'],
      $params['event_queue_id'],
      $params['hash']
    );

    if (!$q) {
      return NULL;
    }
    $q->free();

    $delivered = new CRM_Mailing_Event_BAO_Delivered();
    $delivered->time_stamp = date('YmdHis');
    $delivered->copyValues($params);
    $delivered->save();

    $queue = new CRM_Mailing_Event_BAO_Queue();
    $queue->id = $params['event_queue_id'];
    $queue->find(TRUE);

    while ($queue->fetch()) {
      $email = new CRM_Core_BAO_Email();
      $email->id = $queue->email_id;
      $email->hold_date = '';
      $email->reset_date = date('YmdHis');
      $email->save();
    }

    return $delivered;
  }

  /**
   * Get row count for the event selector.
   *
   * @param int $mailing_id ID of the mailing.
   * @param int|null $job_id Optional ID of a job to filter on.
   * @param bool $is_distinct Group by queue ID?
   *
   * @return int|null Number of rows in result set.
   */
  public static function getTotalCount(
    $mailing_id,
    $job_id = NULL,
    $is_distinct = FALSE
  ) {
    $dao = new CRM_Core_DAO();

    $delivered = self::getTableName();
    $bounce = CRM_Mailing_Event_BAO_Bounce::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();

    $query = "
            SELECT      COUNT($delivered.id) as delivered
            FROM        $delivered
            INNER JOIN  $queue
                    ON  $delivered.event_queue_id = $queue.id
            LEFT JOIN   $bounce
                    ON  $delivered.event_queue_id = $bounce.event_queue_id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
                    AND $job.is_test = 0
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
            WHERE       $bounce.id IS null
                AND     $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    // query was missing
    $dao->query($query);

    if ($dao->fetch()) {
      return $dao->delivered;
    }

    return NULL;
  }

  /**
   * Get rows for the event browser.
   *
   * @param int $mailing_id ID of the mailing.
   * @param int|null $job_id Optional ID of the job.
   * @param bool $is_distinct Group by queue id?
   * @param int|null $offset Offset for the query.
   * @param int|null $rowCount Number of rows to return.
   * @param CRM_Utils_Sort|string|null $sort Sort object or string.
   *
   * @return array Result set of delivery events.
   */
  public static function &getRows(
    $mailing_id,
    $job_id = NULL,
    $is_distinct = FALSE,
    $offset = NULL,
    $rowCount = NULL,
    $sort = NULL
  ) {

    $dao = new CRM_Core_Dao();

    $delivered = self::getTableName();
    $bounce = CRM_Mailing_Event_BAO_Bounce::getTableName();
    $queue = CRM_Mailing_Event_BAO_Queue::getTableName();
    $mailing = CRM_Mailing_BAO_Mailing::getTableName();
    $job = CRM_Mailing_BAO_Job::getTableName();
    $contact = CRM_Contact_BAO_Contact::getTableName();
    $email = CRM_Core_BAO_Email::getTableName();

    $query = "
            SELECT      $contact.display_name as display_name,
                        $contact.id as contact_id,
                        $email.email as email,
                        $delivered.time_stamp as date
            FROM        $contact
            INNER JOIN  $queue
                    ON  $queue.contact_id = $contact.id
            INNER JOIN  $email
                    ON  $queue.email_id = $email.id
            INNER JOIN  $delivered
                    ON  $delivered.event_queue_id = $queue.id
            LEFT JOIN   $bounce
                    ON  $bounce.event_queue_id = $queue.id
            INNER JOIN  $job
                    ON  $queue.job_id = $job.id
                    AND $job.is_test = 0
            INNER JOIN  $mailing
                    ON  $job.mailing_id = $mailing.id
            WHERE       $bounce.id IS null
                AND     $mailing.id = " . CRM_Utils_Type::escape($mailing_id, 'Integer');

    if (!empty($job_id)) {
      $query .= " AND $job.id = " . CRM_Utils_Type::escape($job_id, 'Integer');
    }

    if ($is_distinct) {
      $query .= " GROUP BY $queue.id ";
    }

    $orderBy = "sort_name ASC, {$delivered}.time_stamp DESC";
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
      $url = CRM_Utils_System::url(
        'civicrm/contact/view',
        "reset=1&cid={$dao->contact_id}"
      );
      $results[] = [
        'name' => "<a href=\"$url\">{$dao->display_name}</a>",
        'email' => $dao->email,
        'date' => CRM_Utils_Date::customFormat($dao->date),
      ];
    }
    return $results;
  }

  /**
   * Bulk create delivery events.
   *
   * @param array $eventQueueIDs Array of event queue IDs.
   * @param string|null $time Optional timestamp.
   *
   * @return void
   */
  public static function bulkCreate($eventQueueIDs, $time = NULL) {
    if (!$time) {
      $time = date('YmdHis');
    }

    // construct a bulk insert statement
    $values = [];
    foreach ($eventQueueIDs as $eqID) {
      $values[] = "( $eqID, '{$time}' )";
    }

    while (!empty($values)) {
      $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str = CRM_Utils_Array::implode(',', $input);
      $sql = "INSERT INTO civicrm_mailing_event_delivered ( event_queue_id, time_stamp ) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
  }
}
