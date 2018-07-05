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
    $params['session_key'] = "{$params['session_key']}_{$params['page_type']}_{$params['page_id']}";
    if ($params['session_key']) {
      $track = new CRM_Core_DAO_Track();
      $track->session_key = $params['session_key'];
      if ($track->find(TRUE)) {
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

  public static function referrerTypeByPage($pageType, $pageId, $start = NULL, $end = NULL) {
    $params = array(
      1 => array($pageType, 'String'),
      2 => array($pageId, 'Integer'),
    );
    $whereClause = array(
      't.page_type = %1',
      't.page_id = %2',
    );

    if($start_date){
      $whereClause[] = ' t.visit_date >= %3 ';
      $params[3] = array($start_date . ' 00:00:00', 'String');
    }
    if($end_date){
      $whereClause[] = ' t.visit_date <= %4 ';
      $params[4] = array($end_date . ' 23:59:59', 'String');
    }

    $where = implode(' AND ', $whereClause);
    $sql = "SELECT COUNT(t.id) FROM civicrm_track t WHERE $where";
    $total = CRM_Core_DAO::singleValueQuery($sql, $params);
    $sql = "SELECT COUNT(t.id) as `count`, t.referrer_type FROM civicrm_track t WHERE $where GROUP BY t.referrer_type ORDER BY count DESC";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $return = array();
    while($dao->fetch()){
      $type = !empty($dao->referrer_type) ? $dao->referrer_type : 'unknown';
      $return[$type] = array(
        'name' => $type,
        'label' => empty($dao->referrer_type) ? ts("Unknown") : ts($dao->referrer_type),
        'count' => $dao->count,
        'percent' => number_format(($dao->count / $total) * 100 ),
      );
    }

    return $return;
  }
}
