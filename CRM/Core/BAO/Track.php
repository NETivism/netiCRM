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

    // refs #31611, #34038, skip internal page
    if ($params['page_type'] == 'civicrm_contribution_page') {
      $checkQuery = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'civicrm_contribution_page' AND column_name = 'is_internal'";
      $exists = CRM_Core_DAO::singleValueQuery($checkQuery);
      if ($exists) {
        $isInternalPage = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $params['page_id'], 'is_internal');
        if ($isInternalPage > 0) {
          return;
        }
      }
    }

    if (empty($params['visit_date'])) {
      $params['visit_date'] = date('Y-m-d H:i:s');
    }
    $params['session_key'] = CRM_Utils_System::getSessionID();
    $track = new CRM_Core_DAO_Track();
    if (!empty($params['id']) && is_numeric($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'Track', $params['id'], $params);
      $track->id = $params['id'];
      $track->find(TRUE);
      $track->copyValues($params);
      $track->counter++;
      $track->update();
      CRM_Utils_Hook::post('edit', 'Track', $track->id, $track);
    }
    else {
      // in thirty mins same session visit same page and not completed
      // we treat as same visit
      $sameSession = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_track WHERE session_key = %1 AND visit_date > %2 AND page_type = %3 AND page_id = %4 ORDER BY visit_date DESC LIMIT 1", array(
        1 => array($params['session_key'], 'String'),
        2 => array(date('Y-m-d H:i:s', time() - self::SESSION_LIMIT), 'String'),
        3 => array($params['page_type'], 'String'),
        4 => array($params['page_id'], 'Integer')
      ));
      
      if ($sameSession->fetch()) {
        CRM_Utils_Hook::pre('edit', 'Track', $sameSession->id, $params);
        $track->id = $sameSession->id;
        $track->find(TRUE);
        if ($params['state'] < $track->state) {
          unset($params['state']);
        }
        $track->copyValues($params);
        if ($track->state <= self::FIRST_STATE) {
          $track->counter++;
        }
        if (!empty($track->entity_id) && empty($track->referrer_type)) {
          $track->referrer_type = 'unknown';
        }
        $track->update();
        CRM_Utils_Hook::post('edit', 'Track', $track->id, $track);
      }
      else {
        CRM_Utils_Hook::pre('create', 'Track', NULL, $params);
        $track->copyValues($params);
        $track->insert();
        CRM_Utils_Hook::post('create', 'Track', $track->id, $track);
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
    if (!empty($_POST['data'])) {
      $post = $_POST['data'];
    }
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
    $params = array_filter($params);
    foreach($params as $key => $value) {
      if (isset($fields[$key])) {
        $field = $fields[$key];
        switch ($field['type']) {
          case CRM_Utils_Type::T_INT:
            if (!CRM_Utils_Type::validate($value, 'Integer', FALSE)) {
              unset($params[$key]);
            }
            break;
          case CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME:
            if (!CRM_Utils_Type::validate($value, 'Date', FALSE)) {
              unset($params[$key]);
            }
            break;
          case CRM_Utils_Type::T_STRING:
          default:
            if (is_array($value)) {
              $params[$key] = (string) reset($value);
            }
            if (!CRM_Utils_Type::validate($value, 'String', FALSE)) {
              unset($params[$key]);
            }
            break;
        }
      }
      else {
        unset($params[$key]);
      }
    }
    $track = CRM_Core_BAO_Track::add($params);
    CRM_Utils_System::civiExit();
  }

  public static function referrerTypeByPage($pageType, $pageId, $start = NULL, $end = NULL) {
    $params = array(
      'pageType' => $pageType,
      'pageId' => $pageId,
    );
    if($start){
      $params['visitDateStart'] = $start;
    }
    if($end){
      $params['visitDateEnd'] = $end;
    }
    $selector = new CRM_Track_Selector_Track($params);
    $dao = $selector->getQuery("COUNT(id) as `count`, referrer_type, SUM(CASE WHEN state >= 4 THEN 1 ELSE 0 END) as goal, max(visit_date) as end, min(visit_date) as start", 'GROUP BY referrer_type');

    $return = array();
    $total = 0;
    $start = $end = 0;
    while($dao->fetch()){
      $type = !empty($dao->referrer_type) ? $dao->referrer_type : 'unknown';
      $total = $total+$dao->count;
      if (!$start && !$end) {
        $start = strtotime($dao->start);
        $end = strtotime($dao->end);
      }
      else {
        $start = strtotime($dao->start) < $start ? strtotime($dao->start) : $start;
        $end = strtotime($dao->end) > $end ? strtotime($dao->end) : $end;
      }
      $return[$type] = array(
        'name' => $type,
        'label' => empty($dao->referrer_type) ? ts("Unknown") : ts($dao->referrer_type),
        'count' => $dao->count,
        'count_goal' => $dao->goal,
      );
    }
    // sort by count
    uasort($return, array(__CLASS__, 'cmp'));
    foreach($return as $type => $data) {
      $return[$type]['percent'] = number_format(($data['count'] / $total) * 100 );
      $return[$type]['percent_goal'] = number_format(($data['count_goal'] / $total) * 100 );
      $return[$type]['start'] = date('Y-m-d H:i:s', $start);
      $return[$type]['end'] = date('Y-m-d H:i:s', $end);
    }
    return $return;
  }

  public static function getTrack($entityTable, $entityId) {
    if (!empty($entityTable) && is_numeric($entityId)) {
      $params = array(
        'entityTable' => $entityTable,
        'entityId' => $entityId,
      );
      $selector = new CRM_Track_Selector_Track($params);
      $dao = $selector->getQuery();
      $dao->fetch();
      if ($dao->N) {
				$track = new CRM_Core_DAO_Track();
        $fields = $track->fields();
        $values = array();
				foreach ($fields as $name => $value) {
					$dbName = $value['name'];
					if (isset($dao->$dbName) && $dao->$dbName !== 'null') {
						$values[$dbName] = $dao->$dbName;
						if ($name != $dbName) {
							$values[$name] = $dao->$dbName;
						}
					}
				}
        return $values;
      }
      return array();
    } 
  }

  public static function cmp($a, $b) {
    if ($a['count'] == $b['count']) {
      return 0;
    }
    return ($a['count'] > $b['count']) ? -1 : 1;
  }
}
