<?php
class CRM_Core_BAO_Track extends CRM_Core_DAO_Track {
  public const SESSION_LIMIT = 1800; // second
  public const LAST_STATE = 4;
  public const FIRST_STATE = 1;

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Add or update a page visit track record.
   *
   * Handles session-based visit logic and triggers pre/post hooks.
   * Validation and server-assigned fields do not change the caller's array.
   *
   * @param array &$params associative array of track data
   *
   * @return CRM_Core_DAO_Track|bool|null the track object, or FALSE on error
   */
  public static function add(&$params) {
    $values = self::filterParams($params);
    if (empty($values['page_type']) || empty($values['page_id'])) {
      return FALSE;
    }

    // refs #31611, #34038, skip internal page
    if ($values['page_type'] == 'civicrm_contribution_page') {
      $checkQuery = "SELECT column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = 'civicrm_contribution_page' AND column_name = 'is_internal'";
      $exists = CRM_Core_DAO::singleValueQuery($checkQuery);
      if ($exists) {
        $isInternalPage = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $values['page_id'], 'is_internal');
        if ($isInternalPage > 0) {
          return;
        }
      }
    }

    if (empty($values['visit_date'])) {
      $values['visit_date'] = date('Y-m-d H:i:s');
    }
    $values['session_key'] = CRM_Utils_System::getSessionID();
    $track = new CRM_Core_DAO_Track();
    if (!empty($values['id']) && is_numeric($values['id'])) {
      CRM_Utils_Hook::pre('edit', 'Track', $values['id'], $values);
      $track->id = $values['id'];
      $track->find(TRUE);
      $track->copyValues($values);
      $track->counter++;
      $track->update();
      CRM_Utils_Hook::post('edit', 'Track', $track->id, $track);
    }
    else {
      // in thirty mins same session visit same page and not completed
      // we treat as same visit
      $sameSession = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_track WHERE session_key = %1 AND visit_date > %2 AND page_type = %3 AND page_id = %4 ORDER BY visit_date DESC LIMIT 1", [
        1 => [$values['session_key'], 'String'],
        2 => [date('Y-m-d H:i:s', time() - self::SESSION_LIMIT), 'String'],
        3 => [$values['page_type'], 'String'],
        4 => [$values['page_id'], 'Integer']
      ]);

      if ($sameSession->fetch()) {
        CRM_Utils_Hook::pre('edit', 'Track', $sameSession->id, $values);
        $track->id = $sameSession->id;
        $track->find(TRUE);
        if (isset($values['state']) && $values['state'] < $track->state) {
          unset($values['state']);
        }
        $track->copyValues($values);
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
        CRM_Utils_Hook::pre('create', 'Track', NULL, $values);
        $track->copyValues($values);
        $track->insert();
        CRM_Utils_Hook::post('create', 'Track', $track->id, $track);
      }
    }
    return $track;
  }

  /**
   * Validate tracking fields before any lookup or write.
   *
   * Invalid optional fields are omitted. Required page fields are checked by
   * add(). Store plain text, never HTML entities; output still needs escaping.
   *
   * @param array $params
   * @return array
   */
  private static function filterParams($params) {
    if (!is_array($params)) {
      return [];
    }
    $allowed = [
      'page_type' => ['civicrm_contribution_page', 'civicrm_event', 'civicrm_uf_group'],
      'entity_table' => ['civicrm_contribution', 'civicrm_participant', 'civicrm_contact'],
      'referrer_type' => array_keys(CRM_Core_PseudoConstant::referrerTypes()),
      'state' => array_keys(CRM_Core_PseudoConstant::trackState()),
    ];
    $utmFields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    $filtered = [];
    foreach (self::fields() as $name => $field) {
      // The session is always assigned by the server.
      if (!isset($params[$name]) || $name === 'session_key') {
        continue;
      }
      $value = $params[$name];
      if ($field['type'] === CRM_Utils_Type::T_INT) {
        if ((!is_int($value) && !is_string($value))
          || !preg_match('/^[0-9]+$/D', (string) $value)) {
          continue;
        }
        $digits = ltrim((string) $value, '0');
        if (strlen($digits) > 10 || (strlen($digits) === 10 && strcmp($digits, '4294967295') > 0)) {
          continue;
        }
        $value = (int) $value;
        if ($name !== 'state' && $value < 1) {
          continue;
        }
      }
      elseif ($field['type'] === CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME) {
        if (!is_string($value)) {
          continue;
        }
        $date = DateTime::createFromFormat('!Y-m-d H:i:s', $value);
        if (!$date || $date->format('Y-m-d H:i:s') !== $value || (int) $date->format('Y') < 1000) {
          continue;
        }
      }
      elseif ($field['type'] === CRM_Utils_Type::T_STRING) {
        if (!is_string($value) || !mb_check_encoding($value, 'UTF-8')) {
          continue;
        }
        if ($name === 'referrer_url' || $name === 'landing') {
          if (!self::isValidTrackingUrl($value, $name)) {
            continue;
          }
          $value = self::removeClickIds($value);
        }
        else {
          $value = preg_replace('/[\x00-\x1f\x7f]/', '', $value);
          if ($name === 'referrer_network') {
            $value = strip_tags($value);
          }
          // Reject the whole UTM field when it contains HTML markup. Do not
          // strip text such as spring<2024, or clear an existing stored value.
          // This is a data-quality rule; report output must still be escaped.
          elseif (in_array($name, $utmFields, TRUE)
            && preg_match('/<(?:\/?[a-z][a-z0-9:-]*(?=[\s\/>])[^>]*|!--.*?--)>/is', $value)) {
            continue;
          }
        }
        if (isset($field['maxlength']) && mb_strlen($value, 'UTF-8') > $field['maxlength']) {
          $value = mb_substr($value, 0, $field['maxlength'], 'UTF-8');
        }
      }
      else {
        continue;
      }
      // Empty beacons must not clear attribution already saved in this session.
      // Check after stripping tags too, and preserve numeric/string zero.
      if ($value === '') {
        continue;
      }
      if (isset($allowed[$name]) && !in_array($value, $allowed[$name], TRUE)) {
        continue;
      }
      $filtered[$name] = $value;
    }
    return $filtered;
  }

  /**
   * Remove ad click IDs before truncation, preserving other query bytes.
   *
   * Avoid parse_str()/http_build_query(): duplicate keys, encoding and query
   * order must survive unchanged. A fragment is not part of the query.
   */
  private static function removeClickIds($value) {
    $parts = explode('#', $value, 2);
    $url = explode('?', $parts[0], 2);
    if (count($url) < 2) {
      return $value;
    }
    $query = explode('&', $url[1]);
    $kept = [];
    $removed = FALSE;
    foreach ($query as $param) {
      $name = explode('=', $param, 2)[0];
      if (in_array(urldecode($name), ['fbclid', 'gclid'], TRUE)) {
        $removed = TRUE;
        continue;
      }
      $kept[] = $param;
    }
    if (!$removed) {
      return $value;
    }
    $result = $url[0];
    $query = implode('&', $kept);
    if ($query !== '') {
      $result .= '?'.$query;
    }
    if (isset($parts[1])) {
      $result .= '#'.$parts[1];
    }
    return $result;
  }

  /**
   * Accept HTTP(S) URLs and the relative formats emitted by insights.js.
   */
  private static function isValidTrackingUrl($value, $name) {
    if ($value === '') {
      return TRUE;
    }
    // Reject whitespace, raw HTML delimiters and browser URL-parser ambiguity.
    if (preg_match('/[\x00-\x20\x7f<>"\\\\]/', $value)) {
      return FALSE;
    }
    if ($name === 'landing' && substr($value, 0, 1) === '/' && substr($value, 0, 2) !== '//') {
      return TRUE;
    }
    if ($name === 'referrer_url' && preg_match('/^external\/url\.php\?qid=[0-9]+&u=[0-9]+$/D', $value)) {
      return TRUE;
    }
    $url = parse_url($value);
    return $url && !empty($url['host']) && !empty($url['scheme'])
      && in_array(strtolower($url['scheme']), ['http', 'https'], TRUE);
  }

  /**
   * Handle AJAX requests for page visit tracking.
   *
   * @return void
   */
  public static function ajax() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      CRM_Utils_System::notFound();
      CRM_Utils_System::civiExit();
    }
    $post = $_POST['data'] ?? NULL;
    if (!is_string($post) || $post === '') {
      CRM_Utils_System::notFound();
      CRM_Utils_System::civiExit();
    }
    $json = json_decode($post);
    if (!is_object($json) || json_last_error() !== JSON_ERROR_NONE) {
      CRM_Utils_System::civiExit();
    }

    $params = (array) $json;
    // Only trusted internal callers may set IDs, counters, state or visit date.
    // Public tracking must locate records by the server session and page.
    unset($params['id'], $params['counter'], $params['session_key'], $params['entity_table'], $params['entity_id'], $params['state'], $params['visit_date']);
    CRM_Core_BAO_Track::add($params);
    CRM_Utils_System::civiExit();
  }

  /**
   * Get track statistics grouped by referrer type for a specific page.
   *
   * @param string $pageType name of the page table
   * @param int $pageId ID of the page record
   * @param string|null $start optional start date filter
   * @param string|null $end optional end date filter
   *
   * @return array associative array of statistics
   */
  public static function referrerTypeByPage($pageType, $pageId, $start = NULL, $end = NULL) {
    $params = [
      'pageType' => $pageType,
      'pageId' => $pageId,
    ];
    if ($start) {
      $params['visitDateStart'] = $start;
    }
    if ($end) {
      $params['visitDateEnd'] = $end;
    }
    $selector = new CRM_Track_Selector_Track($params);
    $dao = $selector->getQuery("COUNT(id) as `count`, referrer_type, SUM(CASE WHEN state >= 4 THEN 1 ELSE 0 END) as goal, max(visit_date) as end, min(visit_date) as start", 'GROUP BY referrer_type');

    $return = [];
    $total = 0;
    $start = $end = 0;
    while ($dao->fetch()) {
      $type = !empty($dao->referrer_type) ? $dao->referrer_type : 'unknown';
      $total = $total + $dao->count;
      if (!$start && !$end) {
        $start = strtotime($dao->start);
        $end = strtotime($dao->end);
      }
      else {
        $start = strtotime($dao->start) < $start ? strtotime($dao->start) : $start;
        $end = strtotime($dao->end) > $end ? strtotime($dao->end) : $end;
      }
      $return[$type] = [
        'name' => $type,
        'label' => empty($dao->referrer_type) ? ts("Unknown") : ts($dao->referrer_type),
        'count' => $dao->count,
        'count_goal' => $dao->goal,
      ];
    }
    // sort by count
    uasort($return, [__CLASS__, 'cmp']);
    foreach ($return as $type => $data) {
      $return[$type]['percent'] = number_format(($data['count'] / $total) * 100);
      $return[$type]['percent_goal'] = number_format(($data['count_goal'] / $total) * 100);
      $return[$type]['start'] = date('Y-m-d H:i:s', $start);
      $return[$type]['end'] = date('Y-m-d H:i:s', $end);
    }
    return $return;
  }

  /**
   * Get track data for a specific entity.
   *
   * @param string $entityTable name of the entity table
   * @param int $entityId entity ID
   *
   * @return array|null associative array of track fields
   */
  public static function getTrack($entityTable, $entityId) {
    if (!empty($entityTable) && is_numeric($entityId)) {
      $params = [
        'entityTable' => $entityTable,
        'entityId' => $entityId,
      ];
      $selector = new CRM_Track_Selector_Track($params);
      $dao = $selector->getQuery();
      $dao->fetch();
      if ($dao->N) {
        $track = new CRM_Core_DAO_Track();
        $fields = $track->fields();
        $values = [];
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
      return [];
    }
  }

  /**
   * Comparison helper for sorting referrer statistics by count.
   *
   * @param array $a
   * @param array $b
   *
   * @return int
   */
  public static function cmp($a, $b) {
    if ($a['count'] == $b['count']) {
      return 0;
    }
    return ($a['count'] > $b['count']) ? -1 : 1;
  }
}
