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
   * Database uniq id
   *
   * @var int
   */
  public $_id;

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
    $this->assign('action', $this->_action);
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
    $filters = [];
    $filters['is_template'] = CRM_Utils_Request::retrieve('is_template', 'Integer', $this);
    $filters['is_shared'] = CRM_Utils_Request::retrieve('is_shared', 'Integer', $this);
    $filters['role'] = CRM_Utils_Request::retrieve('role', 'String', $this);
    $filters['tone'] = CRM_Utils_Request::retrieve('tone', 'String', $this);
    $filters['component'] = CRM_Utils_Request::retrieve('component', 'String', $this);
    $this->validateFilters($filters);
    $where = $params = [];
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
      $this->assign('chartAICompletionQuota', [
        'id' => 'chart-pie-with-legend-aicompletion-usage',
        'classes' => ['ct-chart-pie'],
        'selector' => '#chart-pie-with-legend-aicompletion-usage',
        'type' => 'Pie',
        'series' => json_encode([$quota['used'], $quota['max']]),
        'isFillDonut' => true,
      ]);
      $stats = $this->getStats();
      $this->assign('chartAICompletionUsedfor', [
        'id' => 'chart-pie-with-legend-aicompletion-usedfor',
        'classes' => ['ct-chart-pie'],
        'selector' => '#chart-pie-with-legend-aicompletion-usedfor',
        'type' => 'Pie',
        'series' => json_encode(array_values($stats['component'])),
        'labels' => json_encode(array_keys($stats['component'])),
        'labelType' => 'percent',
        'withLegend' => true,
        'withToolTip' => true,
      ]);
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
    is_template,
    template_title,
    output_text,
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
      if (mb_strlen($dao->output_text) > 30) {
        $output = mb_substr($dao->output_text, 0, 30). ' ...';
      }
      else{
        $output = $dao->output_text;
      }

      $itemLinks = $links;
      $editTemplateLink = [];
      if ($dao->is_template) {
        $editTemplateLink[CRM_Core_Action::UPDATE] = $itemLinks[CRM_Core_Action::UPDATE];
      }
      $action = CRM_Core_Action::formLink($itemLinks, NULL, ['id' => $dao->id]);
      $editTemplateAction = CRM_Core_Action::formLink($editTemplateLink, NULL, ['id' => $dao->id]);
      $rows[] = [
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'display_name' => $details[0],
        'created_date' => $dao->created_date,
        'component' => $dao->component != 'null' ? $dao->component : '',
        'ai_role' => $dao->ai_role != 'null' ? $dao->ai_role : '',
        'tone_style' => $dao->tone_style != 'null' ? $dao->tone_style : '',
        'content' => str_replace("\n", '', $content),
        'output' => str_replace("\n", '', $output),
        'template_title' => !empty($dao->template_title) ? $dao->template_title : '',
        'is_template' => $dao->is_template,
        'edit_template_link' => $editTemplateAction,
        'action' => $action,
      ];
    }

    $this->assign('rows', $rows);
  }

  function view() {
    $this->edit();
  }

  function edit() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
    $controller = new CRM_Core_Controller_Simple('CRM_AI_Form_AICompletion', ts('AI Copywriter'), $this->_action);
    $controller->setEmbedded(TRUE);

    // set the userContext stack
    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/admin/aicompletion', 'reset=1');
    $session->pushUserContext($url);

    $controller->reset();
    $controller->set('id', $this->_id);
    $controller->process();
    $controller->run();
  }

  /**
   * Get action links
   *
   * @return array (reference) of action links
   * @static
   */
  static function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::VIEW=> [
          'name' => ts('View'),
          'url' => 'civicrm/admin/aicompletion',
          'qs' => 'action=view&reset=1&id=%%id%%',
          'title' => ts('Edit Note'),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/aicompletion',
          'qs' => 'action=update&reset=1&id=%%id%%',
          'title' => ts('Edit Note'),
        ],
      ];
    }
    return self::$_links;
  }

  function pager($total) {
    $params = []; 
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
        $params[3] = [$filterValue, 'String'];
      }
      elseif ($ele == 'role') {
        $where[] = 'ai_role = %4';
        $params[4] = [$filterValue, 'String'];
      }
      elseif ($ele == 'component') {
        $where[] = 'component = %5';
        $params[5] = [$filterValue, 'String'];
      }
    }
  }

  function validateFilters(&$filters) {
    $filters = array_filter($filters);
    $available = [];
    $available['role'] = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(ai_role) FROM civicrm_aicompletion GROUP BY ai_role");
    $available['tone'] = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(tone_style) FROM civicrm_aicompletion GROUP BY tone_style");
    $available['component'] = CRM_Core_DAO::singleValueQuery("SELECT GROUP_CONCAT(component) FROM civicrm_aicompletion GROUP BY component");

    $unset = [];
    foreach(['role', 'tone', 'components'] as $ele) {
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
    $stats = [
      'component' => []
    ];
    $dao = CRM_Core_DAO::executeQuery("SELECT component, count(*) as count FROM civicrm_aicompletion WHERE created_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01 00:00:00') AND created_date < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01 00:00:00') GROUP BY component");
    while($dao->fetch()) {
      $stats['component'][ts($dao->component)] = $dao->count;
    }
    return $stats;
  }
}
