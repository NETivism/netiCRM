<?php
class CRM_Core_BAO_Track extends CRM_Core_DAO_Track {
  const SESSION_LIMIT = 1800; // second
  const LAST_STATE = 4;
  const FIRST_STATE = 1;

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
    $track = new CRM_Core_DAO_Track();
    if (!empty($params['id']) && is_numeric($params['id'])) {
      $track->id = $params['id'];
      $track->find(TRUE);
      $track->copyValues($params);
      $track->counter++;
      $track->update();
    }
    else {
      // in thirty mins same session visit same page and not completed
      // we treat as same visit
      $sameSession = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_track WHERE session_key = %1 AND visit_date > %2 AND page_type = %3 AND page_id = %4 AND state < %5 ORDER BY visit_date DESC LIMIT 1", array(
        1 => array($params['session_key'], 'String'),
        2 => array(date('Y-m-d H:i:s', time() - self::SESSION_LIMIT), 'String'),
        3 => array($params['page_type'], 'String'),
        4 => array($params['page_id'], 'Integer'),
        5 => array(self::LAST_STATE, 'Integer')
      ));
      
      if ($sameSession->fetch()) {
        $track->id = $sameSession->id;
        $track->find(TRUE);
        if ($params['state'] < $track->state) {
          unset($params['state']);
        }
        $track->copyValues($params);
        if ($track->state <= self::FIRST_STATE) {
          $track->counter++;
        }
        $track->update();
      }
      else {
        $track->copyValues($params);
        $track->insert();
      }
    }
    return $track;
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
      CRM_Utils_System::civiExit();
    }

    $track = new CRM_Core_BAO_Track();
    $fields = $track->fields();
    $params = (array) $json;
    $params = array_intersect_key($params, $fields);
    $params = array_filter($params);
    $track = CRM_Core_BAO_Track::add($params);
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
    $sql = "SELECT COUNT(t.id) FROM civicrm_track t LEFT JOIN civicrm_track_entity e ON t.id = e.track_id WHERE $where";
    $total = CRM_Core_DAO::singleValueQuery($sql, $params);
    $sql = "SELECT COUNT(t.id) as `count`, t.referrer_type FROM civicrm_track t LEFT JOIN civicrm_track_entity e ON t.id = e.track_id WHERE $where GROUP BY t.referrer_type ORDER BY count DESC";
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
