<?php
class CRM_Core_BAO_TrackEntity extends CRM_Core_DAO_TrackEntity {
  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   *
   * The function extracts all the params it needs to create a
   * track object. the params array contains additional unused name/value
   * pairs
   *
   * @param array  $params         (reference) an assoc array of name/value pairs
   *
   * @return object    CRM_Core_DAO_Track object on success, otherwise null
   * @access public
   * @static
   */
  static function add(&$params) {
    if (!empty($params['entity_table']) && !empty($params['entity_id']) && !empty($params['track_id'])) {
      // remove state because it will affect find logic
      $state = $params['state'];
      unset($params['state']);

      $trackEntity = new CRM_Core_DAO_TrackEntity();
      $trackEntity->copyValues($params);
      if ($trackEntity->find(TRUE)) {
        $trackEntity->state = $state;
        $trackEntity->update();
      }
      else {
        $trackEntity->state = $state;
        $trackEntity->insert();
      }
    }
  }
}
