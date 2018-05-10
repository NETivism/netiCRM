<?php
class CRM_Core_BAO_Track extends CRM_Core_DAO_Track {
  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  static function updateDuplicate() {
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
    if ($params['session_key']) {
      $track = new CRM_Core_DAO_Track();
      $track->session_key = $params['session_key'];
      if ($track->find(TRUE)) {
        $track->copyValues($params);
        $track->update();
      }
      else {
        $track->copyValues($params);
        $track->insert();
      }
      return $track;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Function to delete the track
   *
   * @param int $session_key   track session_key
   *
   * @return boolean
   * @access public
   * @static
   *
   */
  static function del($session_key) {
    // delete all track records with the selected tracked session_key
    $track = new CRM_Core_DAO_Track();
    $track->session_key = $session_key;
    if ($track->delete()) {
      return TRUE;
    }
    return FALSE;
  }

}
