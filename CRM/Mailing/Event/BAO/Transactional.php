<?php

class CRM_Mailing_Event_BAO_Transactional extends CRM_Mailing_Event_DAO_Transactional {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Create a new delivery event
   *
   * @param array $params     Associative array of delivery event values
   *
   * @return object
   * @access public
   * @static
   */
  public static function create(&$params) {
    if (empty($params['activity_id'])) {
      return NULL;
    }
    if(!CRM_Utils_Rule::positiveInteger($params['activity_id'])) {
      return NULL;
    }
    $q = CRM_Mailing_Event_BAO_Queue::verify($params['job_id'], $params['event_queue_id'], $params['hash']);
    if (!$q) {
      return NULL;
    }
    $q->free();

    $trans = new CRM_Mailing_Event_BAO_Transactional();
    $trans->copyValues($params);
    $trans->save();

    return $trans;
  }

  /**
   * Get Transactional mailing events
   *
   * @param int $activityId
   * @return void
   */
  public static function getEventsByActivity($activityId) {
    $eq = CRM_Mailing_Event_DAO_Queue::getTableName();
    $et = CRM_Mailing_Event_DAO_Transactional::getTableName();
    $ed = CRM_Mailing_Event_DAO_Delivered::getTableName();
    $eb = CRM_Mailing_Event_DAO_Bounce::getTableName();
    $eo = CRM_Mailing_Event_DAO_Opened::getTableName();
    $ec = CRM_Mailing_Event_DAO_TrackableURLOpen::getTableName();
    $eu = CRM_Mailing_Event_DAO_Unsubscribe::getTableName();

    $select = [];
    $select[] = "SELECT 'delivered' as act, $ed.time_stamp, '' as detail, '' as additional FROM $ed INNER JOIN $et ON $et.event_queue_id = $ed.event_queue_id WHERE $et.activity_id = %1";
    $select[] = "SELECT 'bounce' as act, $eb.time_stamp, $eb.bounce_reason as detail, CONCAT(bt.description, ' (',bt.name,')') as additional FROM $eb INNER JOIN civicrm_mailing_bounce_type bt ON bt.id = $eb.bounce_type_id INNER JOIN $et ON $et.event_queue_id = $eb.event_queue_id WHERE $et.activity_id = %1";
    $select[] = "SELECT 'opened' as act, $eo.time_stamp, '' as detail, '' as additional FROM $eo INNER JOIN $et ON $et.event_queue_id = $eo.event_queue_id WHERE $et.activity_id = %1";
    $select[] = "SELECT 'trackableurlopen' as act, $ec.time_stamp, mu.url as detail, '' as additional FROM $ec INNER JOIN $et ON $et.event_queue_id = $ec.event_queue_id INNER JOIN civicrm_mailing_trackable_url mu ON mu.id = $ec.trackable_url_id WHERE $et.activity_id = %1";
    $select[] = "SELECT 'unsubscribe' as act, $eu.time_stamp, $eu.org_unsubscribe as detail, '' as additional FROM $eu INNER JOIN $et ON $et.event_queue_id = $eu.event_queue_id WHERE $et.activity_id = %1";

    $sql = CRM_Utils_Array::implode("\nUNION\n", $select);
    $sql .= "\nORDER BY time_stamp ASC";
    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [$activityId, 'Positive'],
    ]);
    $rows = [];
    while($dao->fetch()) {
      $rows[] = [
        'act' => $dao->act,
        'time' => $dao->time_stamp,
        'detail' => $dao->detail,
        'additional' => $dao->additional,
      ];
    }
    return $rows;
  }

  /**
   * Format event got from database
   *
   * @param array $mailingEvents
   * @return void
   */
  public static function formatMailingEvents($mailingEvents) {
    foreach($mailingEvents as $idx => $event) {
      switch($event['act']) {
        case 'delivered':
          $event['action'] = ts('Delivered');
          break;
        case 'bounce':
          $event['action'] = ts('Bounced');
          $event['detail'] = ts('Bounce Reason').": ".$event['additional']."\n".CRM_Utils_String::htmlToText($event['detail']);
          break;
        case 'opened':
          $event['action'] = ts('Opened');
          break;
        case 'trackableurlopen':
          $event['action'] = ts('Clicked');
          break;
        case 'unsubscribe':
          $event['action'] = ts('Unsubscribed');
          if (!empty($event['detail'])) {
            $event['detail'] = ts('Opt-Out');
          }
          else {
            $event['detail'] = ts('Unsubscription Group');
          }
          break;
      }
      $mailingEvents[$idx] = $event;
    }
    return $mailingEvents;
  }
}