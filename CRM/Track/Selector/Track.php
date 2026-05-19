<?php
/**
 * Class CRM_Track_Selector_Track.
 */
class CRM_Track_Selector_Track extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * The scope of the selector.
   *
   * @var mixed
   */
  public $_scope;

  /**
   * The base URL for the selector.
   *
   * @var string
   */
  public $_base;

  /**
   * Allowed GET parameters.
   *
   * @var array
   */
  public $_allowedGet;

  /**
   * The drill-down URL.
   *
   * @var string
   */
  public $_drillDown;

  /**
   * Page types.
   *
   * @var array
   */
  public $_pageTypes;

  /**
   * Page URLs.
   *
   * @var array
   */
  public $_pageUrl;

  /**
   * Referenced record types.
   *
   * @var array<string, mixed>
   */
  public $_referencedRecordType;

  /**
   * Referenced record URLs.
   *
   * @var array
   */
  public $_referencedRecordUrl;

  /**
   * Tracking states.
   *
   * @var array
   */
  public $_trackState;

  /**
   * Referrer types.
   *
   * @var array
   */
  public $_referrerTypes;

  /**
   * UTM parameters.
   *
   * @var array
   */
  public $_utm;

  /**
   * Array of supported links, currently null.
   *
   * @var array
   */
  public static $_links = NULL;

  /**
   * Which page are we browsing tracking from?
   *
   * @var string
   */
  private $_pageType;

  /**
   * The ID of the page.
   *
   * @var int
   */
  private $_pageId;

  /**
   * What is the visit state?
   *
   * @var int
   */
  private $_state;

  /**
   * What referrer type are we browsing?
   *
   * @var string
   */
  private $_referrerType;

  /**
   * Do we want events tied to a specific network?
   *
   * @var string
   */
  private $_referrerNetwork;

  /**
   * UTM source.
   *
   * @var string
   */
  private $_utmSource;

  /**
   * UTM medium.
   *
   * @var string
   */
  private $_utmMedium;

  /**
   * UTM campaign.
   *
   * @var string
   */
  private $_utmCampaign;

  /**
   * UTM term.
   *
   * @var string
   */
  private $_utmTerm;

  /**
   * UTM content.
   *
   * @var string
   */
  private $_utmContent;

  /**
   * The start date for the visit.
   *
   * @var string
   */
  private $_visitDateStart;

  /**
   * The end date for the visit.
   *
   * @var string
   */
  private $_visitDateEnd;

  /**
   * The entity table for the submitted transaction.
   *
   * @var string
   */
  private $_entityTable;

  /**
   * The entity ID for the submitted transaction.
   *
   * @var int|string
   */
  private $_entityId;

  /**
   * We use desc to remind us what that column is, name is used in the tpl.
   *
   * @var array
   */
  public $_columnHeaders;

  /**
   * Class constructor.
   *
   * @param array $filters
   *   The filters for the query.
   * @param string|null $scope
   *   The scope of the selector.
   *
   * @return \CRM_Track_Selector_Track
   */
  public function __construct($filters, $scope = NULL) {
    foreach ($filters as $filter => $value) {
      if (!empty($value)) {
        $filter = '_'.$filter;
        $this->$filter = $value;
      }
    }
    if ($scope) {
      $this->_scope = $scope;
      $get = $_GET;
      $this->_base = "civicrm/track/report?reset=1&ptype={$get['ptype']}&pid={$get['pid']}&pageKey={$this->_scope}";
      $this->_allowedGet = [
        'rtype' => ts('Referrer Type'),
        'rnetwork' => ts('Referrer Network'),
        'state' => ts('Visit State'),
        'entity_id' => ts('Referenced Record'),
        'referrer_url' => ts('Referrer URL'),
        'landing' => ts('Landing Page'),
        'page_title' => ts('Page Name'),
        'utm_source' => 'utm_source',
        'utm_medium' => 'utm_medium',
        'utm_campaign' => 'utm_campaign',
        'utm_term' => 'utm_term',
        'utm_content' => 'utm_content',
        'start' => ts('Start Date'),
        'end' => ts('End Date'),
      ];
      $this->_drillDown = $this->_base;
      foreach ($get as $filter => $value) {
        if ($this->_allowedGet [$filter]) {
          $this->_drillDown .= '&'.$filter."=".$value;
        }
      }
    }

    $this->_pageTypes = [
      'civicrm_contribution_page' => ts('Contribution Page'),
      'civicrm_event' => ts('Event'),
      'civicrm_uf_group' => ts('Profile'),
    ];
    $this->_pageUrl = [
      'civicrm_contribution_page' => 'civicrm/admin/contribute?action=update&reset=1&id=%%id%%',
      'civicrm_event' => 'civicrm/event/search?reset=1&force=1&event=%%id%%',
      'civicrm_uf_group' => 'civicrm/admin/uf/group/field?reset=1&action=browse&gid=%%id%%',
    ];
    $this->_referencedRecordType = [
      'civicrm_contribution' => ts('Donor'),
      'civicrm_participant' => ts('Participant'),
      'civicrm_contact' => ts('Contact'),
    ];
    $this->_referencedRecordUrl = [
      'civicrm_contribution' => 'civicrm/contact/view/contribution?reset=1&id=%%id%%&cid=%%cid%%&action=view',
      'civicrm_participant' => 'civicrm/contact/view/participant?reset=1&id=%%id%%&cid=%%cid%%&action=view',
      'civicrm_contact' => 'civicrm/contact/view?reset=1&cid=%%cid%%',
    ];
    $this->_trackState = CRM_Core_PseudoConstant::trackState();
    $this->_referrerTypes = CRM_Core_PseudoConstant::referrerTypes();
    $this->_utm = [
      'utm_source' => 'utm_source',
      'utm_medium' => 'utm_medium',
      'utm_campaign' => 'utm_campaign',
      'utm_term' => 'utm_term',
      'utm_content' => 'utm_content',
    ];
  }

  /**
   * This method returns the links that are given for each search row.
   *
   * @return array
   */
  public static function &links() {
    return self::$_links;
  }

  /**
   * Getter for array of the parameters required for creating pager.
   *
   * @param string $action
   *   The action being performed.
   * @param array $params
   *   The parameters for the pager.
   *
   */
  public function getPagerParams($action, &$params) {
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    $params['status'] = ts('%1 %%StatusMessage%%', [1 => $this->referrerToTitle()]);

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }

  /**
   * Returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param string $type what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   */
  public function &getColumnHeaders($action = NULL, $type = NULL) {
    if ($type == CRM_Core_Selector_Controller::EXPORT) {
      $exportHeaders = [
        ts('Page Type'),
        ts('Page Name'),
        ts('Visit Date'),
        ts('Visit State'),
        ts('Referrer Type'),
        ts('Referrer Network'),
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        ts('Referrer URL'),
        ts('Landing Page'),
        ts('Referenced Record'),
      ];
      return $exportHeaders;
    }
    if (!isset($this->_columnHeaders)) {
      $this->_columnHeaders = [
        [
          'name' => ts('Page Type'),
          'sort' => 'page_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Page Name'),
          'sort' => 'page_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Visit Date'),
          'sort' => 'visit_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ],
        [
          'name' => ts('Visit State'),
          'sort' => 'state',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ],
        [
          'name' => ts('Referrer Type'),
          'sort' => 'referrer_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Referrer Network'),
          'sort' => 'referrer_network',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Custom Campaign'),
        ],
        [
          'name' => ts('Referrer URL'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Landing Page'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
        [
          'name' => ts('Referenced Record'),
          'sort' => 'entity_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ],
      ];
    }
    return $this->_columnHeaders;
  }

  /**
   * Returns total number of rows for the query.
   *
   * @param string $action
   *   The action being performed.
   *
   * @return int Total number of rows
   */
  public function getTotalCount($action) {
    $dao = $this->getQuery();
    if (!empty($dao->N)) {
      return $dao->N;
    }
    return 0;
  }

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param string $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param string $output   what should the result set include (web/email/csv)
   *
   * @return array the total number of rows for this action
   */
  public function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
    if ($output == CRM_Core_Selector_Controller::EXPORT) {
      return $this->buildExportRows($offset, $rowCount, $sort);
    }
    $dao = $this->getQuery('*', NULL, $offset, $rowCount, $sort);

    $result = [];
    $recordTables = [];
    $pageTables = [];
    while ($dao->fetch()) {
      $id = $dao->id.'-'.$dao->tid;
      $referrerUrl = $landing = '';
      if ($dao->referrer_url) {
        if (strstr($dao->referrer_url, 'http')) {
          $url = parse_url($dao->referrer_url);
          $referrerUrl = $url['host'].'... <a href="'.$dao->referrer_url.'" target="_blank"><i class="zmdi zmdi-arrow-right-top"></i></a>';
        }
        else {
          $referrerUrl = substr($dao->referrer_url, 0, 15).'...';
        }
      }
      if ($dao->landing) {
        $url = parse_url($dao->landing);
        $landing = $url['path'].' <a href="'.$dao->landing.'" target="_blank"><i class="zmdi zmdi-arrow-right-top"></i></a>';
      }
      $utmInfo = [];
      foreach ($this->_utm as $k => $v) {
        if (!empty($dao->$k)) {
          $utmInfo[$k] = $v.":".'<a href="'.CRM_Utils_System::url($this->_drillDown."&{$k}={$dao->$k}").'">'.$dao->$k.'</a>';
        }
      }
      if (!empty($utmInfo)) {
        $utmInfo = '<ul><li>'.CRM_Utils_Array::implode("</li><li>", $utmInfo).'</li></ul>';
      }
      else {
        $utmInfo = '';
      }
      if (empty($dao->referrer_type)) {
        $dao->referrer_type = 'unknown';
      }
      $results[$id] = [];
      $results[$id]['page_type'] = $this->_pageTypes[$dao->page_type];
      $results[$id]['page_id'] = $dao->page_id;
      $results[$id] += [
        'visit_date' => CRM_Utils_Date::customFormat($dao->visit_date),
        'state' => empty($this->_state) ? '<a href="'.CRM_Utils_System::url($this->_drillDown."&state=$dao->state").'">'.$this->_trackState[$dao->state].'</a>' : $this->_trackState[$dao->state],
        'referrer_type' => empty($this->_referrerType) ? '<a href="'.CRM_Utils_System::url($this->_drillDown."&rtype=$dao->referrer_type").'">'.$this->_referrerTypes[$dao->referrer_type].'</a>' : $this->_referrerTypes[$dao->referrer_type],
        'referrer_network' => empty($this->_referrerNetwork) ? '<a href="'.CRM_Utils_System::url($this->_drillDown."&rnetwork=$dao->referrer_network").'">'.$dao->referrer_network.'</a>' : $dao->referrer_network,
        'utm' => $utmInfo,
        'referrer_url' => $referrerUrl,
        'landing' => $landing,
        'entity_id' => $dao->entity_id,
      ];
      $pageTables[$dao->page_type][$dao->page_id][$id] = $id;
      if ($dao->entity_table) {
        $recordTables[$dao->entity_table][$dao->entity_id][$id] = $id;
      }
    }
    $allowedPageTables = array_keys($this->_pageTypes);
    foreach ($pageTables as $table => $pages) {
      if (!in_array($table, $allowedPageTables, true)) {
        continue;
      }
      $pageDAO = CRM_Core_DAO::executeQuery("SELECT id, title FROM $table WHERE id IN(".CRM_Utils_Array::implode(',', array_keys($pages)).")");
      while ($pageDAO->fetch()) {
        foreach ($pages[$pageDAO->id] as $resultId) {
          $url = str_replace('%%id%%', $pageDAO->id, $this->_pageUrl[$table]);
          $results[$resultId]['page_id'] = $pageDAO->title.'<a href="'.CRM_Utils_System::url($url).'" target="_blank"><i class="zmdi zmdi-info"></i></a>';
        }
      }
    }
    $allowedRecordTables = array_keys($this->_referencedRecordType);
    foreach ($recordTables as $table => $records) {
      if (!in_array($table, $allowedRecordTables, true)) {
        continue;
      }
      if ($table === 'civicrm_contact') {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT c.id, c.id as cid, c.sort_name FROM $table c WHERE c.id IN(".CRM_Utils_Array::implode(',', array_keys($records)).")");
      }
      else {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT t.id, t.contact_id as cid, c.sort_name FROM $table t INNER JOIN civicrm_contact c ON c.id = t.contact_id WHERE t.id IN(".CRM_Utils_Array::implode(',', array_keys($records)).")");
      }
      while ($recordDAO->fetch()) {
        foreach ($records[$recordDAO->id] as $resultId) {
          $url = str_replace(['%%cid%%', '%%id%%'], [$recordDAO->cid, $recordDAO->id], $this->_referencedRecordUrl[$table]);
          $results[$resultId]['entity_id'] = $this->_referencedRecordType[$table].': '.'<a href="'.CRM_Utils_System::url($this->_drillDown.'&entity_id=%').'">'.$recordDAO->sort_name.'</a><a href="'.CRM_Utils_System::url($url).'" target="_blank"><i class="zmdi zmdi-info"></i></a>';
        }
      }
    }
    return $results;
  }

  /**
   * Build plain-text rows for spreadsheet export.
   *
   * @param int $offset
   * @param int $rowCount
   * @param mixed $sort
   *
   * @return array
   */
  private function buildExportRows($offset, $rowCount, $sort = NULL) {
    $dao = $this->getQuery('*', NULL, $offset, $rowCount, $sort);

    $rows = [];
    $pageTables = [];
    $recordTables = [];

    while ($dao->fetch()) {
      $rowId = $dao->id;
      if (empty($dao->referrer_type)) {
        $dao->referrer_type = 'unknown';
      }
      $rows[$rowId] = [
        'page_type'       => $this->_pageTypes[$dao->page_type] ?? $dao->page_type,
        'page_title'      => $dao->page_id,
        'visit_date'      => CRM_Utils_Date::customFormat($dao->visit_date),
        'state'           => $this->_trackState[$dao->state] ?? '',
        'referrer_type'   => $this->_referrerTypes[$dao->referrer_type] ?? $dao->referrer_type,
        'referrer_network' => $dao->referrer_network ?? '',
        'utm_source'      => $dao->utm_source ?? '',
        'utm_medium'      => $dao->utm_medium ?? '',
        'utm_campaign'    => $dao->utm_campaign ?? '',
        'utm_term'        => $dao->utm_term ?? '',
        'utm_content'     => $dao->utm_content ?? '',
        'referrer_url'    => $dao->referrer_url ?? '',
        'landing'         => $dao->landing ?? '',
        'entity_id'       => '',
      ];
      $pageTables[$dao->page_type][$dao->page_id][$rowId] = $rowId;
      if (!empty($dao->entity_table) && !empty($dao->entity_id)) {
        $recordTables[$dao->entity_table][$dao->entity_id][$rowId] = $rowId;
      }
    }

    // Batch-fetch page titles to avoid N+1 queries
    $allowedPageTables = array_keys($this->_pageTypes);
    foreach ($pageTables as $table => $pages) {
      if (!in_array($table, $allowedPageTables, true)) {
        continue;
      }
      $pageIds = CRM_Utils_Array::implode(',', array_map('intval', array_keys($pages)));
      if (!$pageIds) {
        continue;
      }
      $pageDAO = CRM_Core_DAO::executeQuery("SELECT id, title FROM $table WHERE id IN($pageIds)");
      while ($pageDAO->fetch()) {
        foreach ($pages[$pageDAO->id] as $rowId) {
          $rows[$rowId]['page_title'] = $pageDAO->title;
        }
      }
    }

    // Batch-fetch contact IDs (no sort_name to avoid PII in plain export)
    $allowedRecordTables = array_keys($this->_referencedRecordType);
    foreach ($recordTables as $table => $records) {
      if (!in_array($table, $allowedRecordTables, true)) {
        continue;
      }
      $entityIds = CRM_Utils_Array::implode(',', array_map('intval', array_keys($records)));
      if (!$entityIds) {
        continue;
      }
      if ($table === 'civicrm_contact') {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT id, id AS cid FROM $table WHERE id IN($entityIds)");
      }
      else {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT id, contact_id AS cid FROM $table WHERE id IN($entityIds)");
      }
      while ($recordDAO->fetch()) {
        foreach ($records[$recordDAO->id] as $rowId) {
          $rows[$rowId]['entity_id'] = $recordDAO->cid;
        }
      }
    }

    return $rows;
  }

  /**
   * Get the query object.
   *
   * @param string $select
   *   The SELECT clause.
   * @param string $groupBy
   *   The GROUP BY clause.
   * @param int $offset
   *   The offset.
   * @param int $rowCount
   *   The number of rows to return.
   * @param string $sort
   *   The ORDER BY clause.
   *
   * @return CRM_Core_DAO
   */
  public function getQuery($select = '*', $groupBy = NULL, $offset = NULL, $rowCount = NULL, $sort = NULL) {
    $where = $args = [];
    $where[] = "referrer_type IS NOT NULL";
    if ($this->_pageType) {
      $where[] = "page_type = %1";
      $args[1] = [$this->_pageType, 'String'];
    }
    if ($this->_pageId) {
      $where[] = "page_id= %2";
      $args[2] = [$this->_pageId, 'Integer'];
    }
    if ($this->_referrerType) {
      $where[] = "referrer_type = %3";
      $args[3] = [$this->_referrerType, 'String'];
    }
    if ($this->_referrerNetwork) {
      $where[] = "referrer_network = %4";
      $args[4] = [$this->_referrerNetwork, 'String'];
    }
    if ($this->_entityId) {
      if (is_numeric($this->_entityId)) {
        $where[] = "entity_id = %5";
        $args[5] = [$this->_entityId, 'Integer'];
      }
      else {
        if ($this->_entityId == '%') {
          $where[] = "entity_id IS NOT NULL";
        }
        elseif ($this->_entityId == 'null') {
          $where[] = "entity_id IS NULL";
        }
        elseif (is_array($this->_entityId)) {
          $where[] = "entity_id IN (%5)";
          $args[5] = [CRM_Utils_Array::implode(',', $this->_entityId), 'CommaSeparatedIntegers'];
        }
      }
    }
    if ($this->_visitDateStart) {
      $where[] = "visit_date >= %6";
      $args[6] = [$this->_visitDateStart, 'String'];
    }
    if ($this->_visitDateEnd) {
      if (strlen($this->_visitDateEnd) <= 10) {
        $this->_visitDateEnd .= ' 23:59:59';
      }
      $where[] = "visit_date <= %7";
      $args[7] = [$this->_visitDateEnd, 'String'];
    }
    if ($this->_state) {
      $where[] = "state = %8";
      $args[8] = [$this->_state, 'Integer'];
    }

    if ($this->_utmSource) {
      $where[] = "utm_source = %9";
      $args[9] = [$this->_utmSource, 'String'];
    }
    if ($this->_utmMedium) {
      $where[] = "utm_medium = %10";
      $args[10] = [$this->_utmMedium, 'String'];
    }
    if ($this->_utmCampaign) {
      $where[] = "utm_campaign = %11";
      $args[11] = [$this->_utmCampaign, 'String'];
    }
    if ($this->_utmTerm) {
      $where[] = "utm_term = %12";
      $args[12] = [$this->_utmTerm, 'String'];
    }
    if ($this->_utmContent) {
      $where[] = "utm_content= %13";
      $args[13] = [$this->_utmContent, 'String'];
    }
    if ($this->_entityTable) {
      $where[] = "entity_table = %14";
      $args[14] = [$this->_entityTable, 'String'];
    }
    if ($this->_referrerUrl) {
      $where[] = "referrer_url LIKE %15";
      $args[15] = ['%' . $this->_referrerUrl . '%', 'String'];
    }
    if ($this->_landing) {
      $where[] = "landing LIKE %16";
      $args[16] = ['%' . $this->_landing . '%', 'String'];
    }
    if ($this->_pageTitle) {
      $titleParam = [1 => ['%' . $this->_pageTitle . '%', 'String']];
      $allPageTables = [
        'civicrm_contribution_page',
        'civicrm_event',
        'civicrm_uf_group',
      ];
      // When page_type is already filtered, only search that table
      $searchTables = ($this->_pageType && in_array($this->_pageType, $allPageTables))
        ? [$this->_pageType]
        : $allPageTables;

      $pageConds = [];
      foreach ($searchTables as $table) {
        $idDao = CRM_Core_DAO::executeQuery("SELECT id FROM {$table} WHERE title LIKE %1", $titleParam);
        $ids = [];
        while ($idDao->fetch()) {
          $ids[] = (int)$idDao->id;
        }
        if (!empty($ids)) {
          $idList = implode(',', $ids);
          if (count($searchTables) === 1) {
            $pageConds[] = "page_id IN ({$idList})";
          }
          else {
            $pageConds[] = "(page_type = '{$table}' AND page_id IN ({$idList}))";
          }
        }
      }

      if (!empty($pageConds)) {
        $where[] = '(' . implode(' OR ', $pageConds) . ')';
      }
      else {
        $where[] = '1 = 0';
      }
    }

    $where = CRM_Utils_Array::implode(" AND ", $where);

    $query = "SELECT $select FROM civicrm_track WHERE $where $groupBy";
    if ($sort) {
      if (is_string($sort)) {
        $orderBy = $sort;
      }
      else {
        $orderBy = trim($sort->orderBy());
      }
      $query .= " ORDER BY {$orderBy} ";
    }
    if ($offset || $rowCount) {
      //Added "||$rowCount" to avoid displaying all records on first page
      $query .= ' LIMIT ' . CRM_Utils_Type::escape($offset, 'Integer') . ', ' . CRM_Utils_Type::escape($rowCount, 'Integer');
    }

    $dao = CRM_Core_DAO::executeQuery($query, $args);
    return $dao;
  }

  /**
   * Name of export file.
   *
   * @param string $output
   *   Type of output.
   *
   * @return string
   *   Name of the file.
   */
  public function getExportFileName($output = 'csv') {
    return date('Ymd_') . 'traffic_source.xlsx';
  }

  /**
   * Get the title for the referrer.
   *
   * @return string
   */
  public function referrerToTitle() {
    $name[] = ts('Traffic Source');
    if ($this->_pageType) {
      $name[] = $this->_pageTypes[$this->_pageType];
      switch ($this->_pageType) {
        case 'civicrm_contribution_page':
          if ($this->_pageId) {
            $name[] = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_pageId, 'title');
          }
          break;
        case 'civicrm_event':
          if ($this->_pageId) {
            $name[] = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $this->_pageId, 'title');
          }
          break;
        case 'civicrm_uf_group':
          if ($this->_pageId) {
            $name[] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $this->_pageId, 'title');
          }
          break;
      }
    }
    return CRM_Utils_Array::implode(' - ', $name);
  }

  /**
   * Get the title.
   *
   * @return string
   */
  public function getTitle() {
    return $this->referrerToTitle();
  }

  /**
   * Set the filters for the page.
   *
   * @param CRM_Core_Page $page
   *   The page object.
   */
  public function filters($page) {
    // generate breadcrumbs
    $get = $_GET;
    foreach ($get as $name => $value) {
      if (!$this->_allowedGet[$name]) {
        unset($get[$name]);
      }
    }
    if (count($get)) {
      foreach ($get as $name => $value) {
        if (!empty($this->_allowedGet[$name])) {
          $removeGet = $get;
          unset($removeGet[$name]);
          $filters[$name] = [
            'value' => $value,
            'title' => $this->_allowedGet[$name],
            'url' => $this->_base."&".http_build_query($removeGet, '', '&'),
          ];
          switch ($name) {
            case 'rtype':
              $filters[$name]['value_display'] = $this->_referrerTypes[$value];
              break;
            case 'rnetwork':
              $filters[$name]['value_display'] = $value;
              break;
            case 'state':
              $filters[$name]['value_display'] = $this->_trackState[$value];
              break;
            case 'entity_id':
              if ($value == '%') {
                $filters[$name]['value_display'] = ts('Has Record');
              }
              elseif ($value == 'null') {
                $filters[$name]['value_display'] = ts('No Record');
              }
              else {
                $filters[$name]['value_display'] = $value;
              }
              break;
            // no break
            case 'referrer_url':
            case 'landing':
            case 'page_title':
            case 'utm_source':
            case 'utm_medium':
            case 'utm_campaign':
            case 'utm_term':
            case 'utm_content':
              $filters[$name]['value_display'] = $value;
              break;
            case 'start':
            case 'end':
              $filters[$name]['value_display'] = $value;
              break;
          }
        }
      }
      $page->set('filters', $filters);
    }
    else {
      $page->set('filters', []);
    }
    if ($filters = $page->get('filters')) {
      $page->assign('filters', $filters);
    }
    $page->assign('drill_down_base', CRM_Utils_System::url($this->_drillDown));
  }

  /**
   * Set the breadcrumbs for the page.
   *
   * @param CRM_Core_Page $page
   *   The page object.
   */
  public function breadcrumbs($page) {
    if ($this->_pageType && $this->_pageId && !$page->_breadcrumbs) {
      // breadcrumb starter
      $breadcrumbs = [
        ['url' => CRM_Utils_System::url(str_replace('%%id%%', $this->_pageId, $this->_pageUrl[$this->_pageType])), 'title' => $this->_pageTypes[$this->_pageType]],
      ];
      CRM_Utils_System::appendBreadCrumb($breadcrumbs);
      $page->_breadcrumbs = $breadcrumbs;
    }
  }
}
