<?php
class CRM_Core_BAO_Track extends CRM_Core_DAO_Track {
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
    if (empty($params['page_type']) || empty($params['page_id'])) {
      return FALSE;
    }
    if (empty($params['visit_date'])) {
      $params['visit_date'] = date('Y-m-d H:i:s');
    }
    $params['session_key'] = session_id();
    $params['session_key'] = "{$params['session_key']}_{$params['page_type']}_{$params['page_id']}}";
    if ($params['session_key']) {
      $track = new CRM_Core_DAO_Track();
      $track->session_key = $params['session_key'];
      if ($track->find()) {
        $track->copyValues($params);
        $track->update();
      }
      else {
        // check if qfkey exists
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

  /**
   * Function to receive json object
   */
  static function ajax() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      CRM_Utils_System::notFound();
      CRM_Utils_System::civiExit();
    }
    $post = file_get_contents('php://input');
    if (empty($post)) {
      CRM_Utils_System::notFound();
      CRM_Utils_System::civiExit();
    }
    $json = json_decode($post);
    if (empty($json) || empty($json->page_type) || empty($json->page_id)) {
      exit();
    }
    $track = new CRM_Core_BAO_Track();
    $fields = $track->fields();
    $params = (array) $json;
    $params = array_intersect_key($params, $fields);
    $params = array_filter($params);
    CRM_Core_BAO_Track::add($params);
    CRM_Utils_System::civiExit();
  }

}
