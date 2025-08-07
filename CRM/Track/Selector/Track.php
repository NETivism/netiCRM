<?php
class CRM_Track_Selector_Track extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

  /**
   * @var mixed
   */
  public $_scope;
  /**
   * @var string
   */
  public $_base;
  public $_allowedGet;
  public $_drillDown;
  public $_pageTypes;
  public $_pageUrl;
  /**
   * @var array<string, mixed>
   */
  public $_referencedRecordType;
  public $_referencedRecordUrl;
  public $_trackState;
  public $_referrerTypes;
  public $_utm;
  /**
   * array of supported links, currenly null
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * which page are we browsing tracking from?
   */
  private $_pageType;
  private $_pageId;

  /**
   * what referrer type are we browsing?
   */
  private $_state;

  /**
   * what referrer type are we browsing?
   */
  private $_referrerType;

  /**
   * do we want events tied to a specific network?
   */
  private $_referrerNetwork;

  /**
   * utm*
   */
  private $_utmSource;
  private $_utmMedium;
  private $_utmCampaign;
  private $_utmTerm;
  private $_utmContent;

  /**
   * for the submitted transaction which eneityId we had?
   */
  private $_visitDateStart;

  /**
   * for the submitted transaction which eneityId we had?
   */
  private $_visitDateEnd;

  /**
   * for the submitted transaction which eneityId we had?
   */
  private $_entityTable;

  /**
   * for the submitted transaction which eneityId we had?
   */
  private $_entityId;

  /**
   * we use desc to remind us what that column is, name is used in the tpl
   *
   * @var array
   */
  public $_columnHeaders;


  /**
   * Class constructor
   *
   * @param string $event         The event type (queue/delivered/open...)
   * @param boolean $distinct     Count only distinct contact events?
   * @param int $mailing          ID of the mailing to query
   * @param int $job              ID of the job to query.  If null, all jobs from $mailing are queried.
   * @param int $url              If the event type is a click-through, do we want only those from a specific url?
   *
   * @return CRM_Contact_Selector_Profile
   * @access public
   */
  function __construct($filters, $scope = NULL) {
    foreach($filters as $filter => $value) {
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
        'utm_source' => 'utm_source',
        'utm_medium' => 'utm_medium',
        'utm_campaign' => 'utm_campaign',
        'utm_term' => 'utm_term',
        'utm_content' => 'utm_content',
        'start' => ts('Start Date'),
        'end' => ts('End Date'),
      ];
      $this->_drillDown = $this->_base;
      foreach($get as $filter => $value) {
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
  //end of constructor

  /**
   * This method returns the links that are given for each search row.
   *
   * @return array
   * @access public
   * @static
   */
  static function &links() {
    return self::$_links;
  }
  //end of function

  /**
   * getter for array of the parameters required for creating pager.
   *
   * @param
   * @access public
   */
  function getPagerParams($action, &$params) {
    $params['csvString'] = NULL;
    $params['rowCount'] = CRM_Utils_Pager::ROWCOUNT;
    $params['status'] = ts('%1 %%StatusMessage%%', [1 => $this->referrerToTitle()]);

    $params['buttonTop'] = 'PagerTopButton';
    $params['buttonBottom'] = 'PagerBottomButton';
  }
  //end of function

  /**
   * returns the column headers as an array of tuples:
   * (name, sortName (key to the sort array))
   *
   * @param string $action the action being performed
   * @param enum   $output what should the result set include (web/email/csv)
   *
   * @return array the column headers that need to be displayed
   * @access public
   */
  function &getColumnHeaders($action = NULL, $type = NULL) {

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
   * @param
   *
   * @return int Total number of rows
   * @access public
   */
  function getTotalCount($action) {
    $dao = $this->getQuery();
    if (!empty($dao->N)) {
      return $dao->N;
    }
    return 0;
  }

  /**
   * returns all the rows in the given offset and rowCount
   *
   * @param enum   $action   the action being performed
   * @param int    $offset   the row number to start from
   * @param int    $rowCount the number of rows to return
   * @param string $sort     the sql string that describes the sort order
   * @param enum   $output   what should the result set include (web/email/csv)
   *
   * @return int   the total number of rows for this action
   */
  function &getRows($action, $offset, $rowCount, $sort, $output = NULL) {
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
      foreach($this->_utm as $k => $v) {
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
      if (empty($dao->referrer_type)){
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
    foreach($pageTables as $table => $pages) {
      $pageDAO = CRM_Core_DAO::executeQuery("SELECT id, title FROM $table WHERE id IN(".CRM_Utils_Array::implode(',', array_keys($pages)).")");
      while($pageDAO->fetch()) {
        foreach($pages[$pageDAO->id] as $resultId) {
          $url = str_replace('%%id%%', $pageDAO->id, $this->_pageUrl[$table]);
          $results[$resultId]['page_id'] = $pageDAO->title.'<a href="'.CRM_Utils_System::url($url).'" target="_blank"><i class="zmdi zmdi-info"></i></a>';
        }
      }
    }
    foreach($recordTables as $table => $records) {
      if ($table === 'civicrm_contact') {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT c.id, c.id as cid, c.sort_name FROM $table c WHERE c.id IN(".CRM_Utils_Array::implode(',', array_keys($records)).")");
      }
      else {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT t.id, t.contact_id as cid, c.sort_name FROM $table t INNER JOIN civicrm_contact c ON c.id = t.contact_id WHERE t.id IN(".CRM_Utils_Array::implode(',', array_keys($records)).")");
      }
      while($recordDAO->fetch()) {
        foreach($records[$recordDAO->id] as $resultId) {
          $url = str_replace(['%%cid%%', '%%id%%'], [$recordDAO->cid, $recordDAO->id], $this->_referencedRecordUrl[$table]);
          $results[$resultId]['entity_id'] = $this->_referencedRecordType[$table].': '.'<a href="'.CRM_Utils_System::url($this->_drillDown.'&entity_id=%').'">'.$recordDAO->sort_name.'</a><a href="'.CRM_Utils_System::url($url).'" target="_blank"><i class="zmdi zmdi-info"></i></a>';
        }
      }
    }
    return $results;
  }


  function getQuery($select = '*', $groupBy = NULL, $offset = NULL, $rowCount = NULL, $sort = NULL) {
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
          $args[5] = [0, 'Integer'];
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
   * name of export file.
   *
   * @param string $output type of output
   *
   * @return string name of the file
   */
  function getExportFileName($output = 'csv') {}

  function referrerToTitle() {
    $name[] = ts('Traffic Source');
    if ($this->_pageType) {
      $name[] = $this->_pageTypes[$this->_pageType];
      switch($this->_pageType) {
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

  function getTitle() {
    return $this->referrerToTitle();
  }

  function filters($page) {
    // generate breadcrumbs
    $get = $_GET;
    foreach($get as $name => $value) {
      if (!$this->_allowedGet[$name]) {
        unset($get[$name]);
      }
    }
    if (count($get)) {
      foreach($get as $name => $value) {
        if (!empty($this->_allowedGet[$name])) {
          $removeGet = $get;
          unset($removeGet[$name]);
          $filters[$name] = [
            'value' => $value,
            'title' => $this->_allowedGet[$name],
            'url' => $this->_base."&".http_build_query($removeGet, '', '&'),
          ];
          switch($name) {
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
              $filters[$name]['value_display'] = $value;
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

  function breadcrumbs($page) {
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
//end of class

