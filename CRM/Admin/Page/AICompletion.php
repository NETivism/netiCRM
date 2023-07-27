<?php
/**
 * Page for displaying list of AICompletion
 */
class CRM_Admin_Page_AICompletion extends CRM_Core_Page {

  /**
   * The action links for prompt that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * constants for static parameters of the pager
   */
  const ROWCOUNT = 20;

  /**
   * Action of current page
   *
   * @var string
   */
  private $_action;

  /**
   * pager for current page
   *
   * @var CRM_Utils_Pager
   */
  private $_pager;

  /**
   * This function is the main function that is called
   * when the page loads, it decides the which action has
   * to be taken for the page.
   *
   * return null
   * @access public
   */
  function run() {
    $this->_action = CRM_Utils_Request::retrieve('action', 'String', $this, FALSE, 'browse');
    if ($this->_action & CRM_Core_Action::VIEW) {
      $this->view();
    }
    elseif ($this->_action & CRM_Core_Action::UPDATE) {
      $this->edit();
    }

    $this->browse();
    return parent::run();
  }

  function browse() {
    // filter
    $filters = array();
    $filters['is_template'] = CRM_Utils_Request::retrieve('is_template', 'Integer', $this);
    $filters['is_shared'] = CRM_Utils_Request::retrieve('is_shared', 'Integer', $this);
    $filters['role'] = CRM_Utils_Request::retrieve('role', 'String', $this);
    $filters['tone'] = CRM_Utils_Request::retrieve('tone', 'String', $this);
    $filters['component'] = CRM_Utils_Request::retrieve('component', 'String', $this);
    $this->validateFilters($filters);
    $where = $params = array();
    $this->buildWhere($filters, $where, $params);
    $this->assign('show_reset', TRUE);
    if (empty($where)) {
      $where[] = '1';
      $this->assign('show_reset', FALSE);

      // org intro
      $config = CRM_Core_Config::singleton();
      $this->assign('organization_intro', $config->aiOrganizationIntro);

      // quota
      $quota = CRM_AI_BAO_AICompletion::quota();
      $this->assign('usage', $quota);
      $this->assign('chartAICompletionQuota', array(
        'id' => 'chart-pie-with-legend-aicompletion-usage',
        'classes' => array('ct-chart-pie'),
        'selector' => '#chart-pie-with-legend-aicompletion-usage',
        'type' => 'Pie',
        'series' => json_encode(array($quota['used'], $quota['max'])),
        'isFillDonut' => true,
      ));
      $stats = $this->getStats();
      $this->assign('chartAICompletionUsedfor', array(
        'id' => 'chart-pie-with-legend-aicompletion-usedfor',
        'classes' => array('ct-chart-pie'),
        'selector' => '#chart-pie-with-legend-aicompletion-usedfor',
        'type' => 'Pie',
        'series' => json_encode(array_values($stats['component'])),
        'labels' => json_encode(array_keys($stats['component'])),
        'labelType' => 'percent',
        'withLegend' => true,
        'withToolTip' => true,
      ));
    }


    // query
    $sql = "
SELECT
    id,
    contact_id,
    created_date,
    component,
    ai_role,
    tone_style,
    context
FROM
    civicrm_aicompletion
WHERE
    (".implode(' AND ', $where).")
ORDER BY
    created_date
DESC
";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->N) {
      $this->pager($dao->N);
      list($filter['offset'], $filter['limit']) = $this->_pager->getOffsetAndRowCount();
      unset($dao);
    }

    if (isset($filter['offset']) && !empty($filter['limit'])) {
      $sql .= " LIMIT {$filter['offset']}, {$filter['limit']} ";
    }

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $links = &self::links();

    while ($dao->fetch()) {
      $details = CRM_Contact_BAO_Contact::getContactDetails($dao->contact_id);
      if (mb_strlen($dao->context) > 30) {
        $content = mb_substr($dao->context, 0, 10).' ... '.mb_substr($dao->context, -20);
      }
      else {
        $content = $dao->context;
      }

      $action = CRM_Core_Action::formLink($links, NULL, array('id' => $dao->id));
      $rows[] = array(
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'display_name' => $details[0],
        'created_date' => $dao->created_date,
        'component' => $dao->component != 'null' ? $dao->component : '',
        'ai_role' => $dao->ai_role != 'null' ? $dao->ai_role : '',
        'tone_style' => $dao->tone_style != 'null' ? $dao->tone_style : '',
        'content' => str_replace("\n", '', $content),
        'action' => $action,
      );
    }

    $this->assign('rows', $rows);
  }

  function view() {

  }

  function edit() {

  }

  /**
   * Get action links
   *
   * @return array (reference) of action links
   * @static
   */
  static function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/aicompletion',
          'qs' => 'action=update&reset=1&id=%%id%%',
          'title' => ts('Edit Note'),
        ),
      );
    }
    return self::$_links;
  }

  function pager($total) {
    $params = array(); 
    $params['status'] = '';
    $params['csvString'] = NULL;
    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
    $params['rowCount'] = self::ROWCOUNT;
    $params['total'] = $total;
    $this->_pager = new CRM_Utils_Pager($params);
    $this->assign_by_ref('pager', $this->_pager);
  }

  function buildWhere($filters, &$where, &$params) {
    foreach($filters as $ele => $filterValue) {
      if ($ele == 'is_template') {
        $where[] = 'is_template = 1';
      }
      elseif ($ele == 'is_shared') {
        $where[] = 'is_share_with_others = 1';
      }
      elseif ($ele == 'tone') {
        $where[] = 'tone_style = %3';
        $params[3] = array($filterValue, 'String');
      }
      elseif ($ele == 'role') {
        $where[] = 'ai_role = %4';
        $params[4] = array($filterValue, 'String');
      }
      elseif ($ele == 'component') {
        $where[] = 'component = %5';
        $params[5] = array($filterValue, 'String');
      }
    }
  }

  function validateFilters(&$filters) {
    $filters = array_filter($filters);
    $available = array();
    $available['role'] = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(ai_role) FROM civicrm_aicompletion GROUP BY ai_role");
    $available['tone'] = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(tone_style) FROM civicrm_aicompletion GROUP BY tone_style");
    $available['component'] = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(component) FROM civicrm_aicompletion GROUP BY component");

    $unset = array();
    foreach(array('role', 'tone', 'components') as $ele) {
      if (!empty($available[$ele])) {
        $elements = explode(',', $available[$ele]);
        if (!in_array($filters['role'], $elements)) {
          $unset[$ele] = TRUE;
        }
      }
      else {
        $unset[$ele] = TRUE;
      }
    }
    foreach($unset as $ele) {
      unset($filters[$ele]);
    }
  }

  function getStats() {
    $stats = array();
    $dao = CRM_Core_DAO::executeQuery("SELECT component, count(*) as count FROM civicrm_aicompletion WHERE created_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01 00:00:00') AND created_date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00') GROUP BY component");
    while($dao->fetch()) {
      $stats['component'][ts($dao->component)] = $dao->count;
    }
    return $stats;
  }
}
