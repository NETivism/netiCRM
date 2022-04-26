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
  public static function &create(&$params) {
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
}