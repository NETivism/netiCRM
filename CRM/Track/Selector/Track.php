<?php
class CRM_Track_Selector_Track extends CRM_Core_Selector_Base implements CRM_Core_Selector_API {

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
      $this->_allowedGet = array(
        'rtype' => ts('Referrer Type'),
        'rnetwork' => ts('Referrer Network'),
        'state' => ts('Visit State'),
        'entity_id' => ts('Referenced Record'),
      );
      $this->_drillDown = $this->_base;
      foreach($get as $filter => $value) {
        if ($this->_allowedGet [$filter]) {
          $this->_drillDown .= '&'.$filter."=".$value;
        }
      }
    }

    $this->_pageTypes = array(
      'civicrm_contribution_page' => ts('Contribution Page'),
      'civicrm_event' => ts('Event'),
      'civicrm_uf_group' => ts('Profile'),
    );
    $this->_pageUrl = array(
      'civicrm_contribution_page' => 'civicrm/admin/contribute?action=update&reset=1&id=%%id%%',
      'civicrm_event' => 'civicrm/event/search?reset=1&force=1&event=%%id%%',
      'civicrm_uf_group' => 'civicrm/admin/uf/group/field?reset=1&action=browse&gid=%%id%%',
    );
    $this->_referencedRecordType = array(
      'civicrm_contribution' => ts('Donor'),
      'civicrm_participant' => ts('Participant'),
      'civicrm_contact' => ts('Contact'),
    );
    $this->_referencedRecordUrl = array(
      'civicrm_contribution' => 'civicrm/contact/view/contribution?reset=1&id=%%id%%&cid=%%cid%%&action=view',
      'civicrm_participant' => 'civicrm/contact/view/participant?reset=1&id=%%id%%&cid=%%cid%%&action=view',
      'civicrm_contact' => 'civicrm/contact/view?reset=1&cid=%%cid%%',
    );
    $this->_trackState = CRM_Core_PseudoConstant::trackState();
    $this->_referrerTypes = CRM_Core_PseudoConstant::referrerTypes();
    if ($this->_pageType && $this->_pageId) {
      // breadcrumb starter
      $breadcrumbs = array(
        array('url' => CRM_Utils_System::url(str_replace('%%id%%', $this->_pageId, $this->_pageUrl[$this->_pageType])), 'title' => $this->_pageTypes[$this->_pageType]),
      );
      CRM_Utils_System::appendBreadCrumb($breadcrumbs);
    }
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
    $params['status'] = ts('%1 %%StatusMessage%%', array(1 => $this->referrerToTitle()));

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
      $this->_columnHeaders = array(
        array(
          'name' => ts('Page Type'),
          'sort' => 'page_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Page Name'),
          'sort' => 'page_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Visit Date'),
          'sort' => 'visit_date',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Visit State'),
          'sort' => 'state',
          'direction' => CRM_Utils_Sort::DESCENDING,
        ),
        array(
          'name' => ts('Referrer Type'),
          'sort' => 'referrer_type',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Referrer Network'),
          'sort' => 'referrer_network',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Referrer URL'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Landing Page'),
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
        array(
          'name' => ts('Referenced Record'),
          'sort' => 'entity_id',
          'direction' => CRM_Utils_Sort::DONTCARE,
        ),
      );
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

    $result = array();
    $recordTables = array();
    $pageTables = array();
    while ($dao->fetch()) {
      $id = $dao->id.'-'.$dao->tid;
      $results[$id] = array();
      $results[$id]['page_type'] = $this->_pageTypes[$dao->page_type];
      $results[$id]['page_id'] = $dao->page_id;
      $results[$id] += array(
        'visit_date' => CRM_Utils_Date::customFormat($dao->visit_date),
        'state' => empty($this->_state) ? '<a href="'.CRM_Utils_System::url($this->_drillDown."&state=$dao->state").'">'.$this->_trackState[$dao->state].'</a>' : $this->_trackState[$dao->state],
        'referrer_type' => empty($this->_referrerType) ? '<a href="'.CRM_Utils_System::url($this->_drillDown."&rtype=$dao->referrer_type").'">'.$this->_referrerTypes[$dao->referrer_type].'</a>' : $this->_referrerTypes[$dao->referrer_type],
        'referrer_network' => empty($this->_referrerNetwork) ? '<a href="'.CRM_Utils_System::url($this->_drillDown."&rnetwork=$dao->referrer_network").'">'.$dao->referrer_network.'</a>' : $dao->referrer_network,
        'referrer_url' => $dao->referrer_url,
        'landing' => $dao->landing,
        'entity_id' => $dao->entity_id,
      );
      $pageTables[$dao->page_type][$dao->page_id][$id] = $id;
      if ($dao->entity_table) {
        $recordTables[$dao->entity_table][$dao->entity_id][$id] = $id;
      }
    }
    foreach($pageTables as $table => $pages) {
      $pageDAO = CRM_Core_DAO::executeQuery("SELECT id, title FROM $table WHERE id IN(".implode(',', array_keys($pages)).")");
      while($pageDAO->fetch()) {
        foreach($pages[$pageDAO->id] as $resultId) {
          $url = str_replace('%%id%%', $pageDAO->id, $this->_pageUrl[$table]);
          $results[$resultId]['page_id'] = $pageDAO->title.'<a href="'.CRM_Utils_System::url($url).'" target="_blank"><i class="zmdi zmdi-info"></i></a>';
        }
      }
    }
    foreach($recordTables as $table => $records) {
      if ($table === 'civicrm_contact') {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT c.id, c.id as cid, c.sort_name FROM $table c WHERE c.id IN(".implode(',', array_keys($records)).")");
      }
      else {
        $recordDAO = CRM_Core_DAO::executeQuery("SELECT t.id, t.contact_id as cid, c.sort_name FROM $table t INNER JOIN civicrm_contact c ON c.id = t.contact_id WHERE t.id IN(".implode(',', array_keys($records)).")");
      }
      while($recordDAO->fetch()) {
        foreach($records[$recordDAO->id] as $resultId) {
          $url = str_replace(array('%%cid%%', '%%id%%'), array($recordDAO->cid, $recordDAO->id), $this->_referencedRecordUrl[$table]);
          $results[$resultId]['entity_id'] = $this->_referencedRecordType[$table].': '.'<a href="'.CRM_Utils_System::url($this->_drillDown.'&entity_id='.$recordDAO->id).'">'.$recordDAO->sort_name.'</a><a href="'.CRM_Utils_System::url($url).'" target="_blank"><i class="zmdi zmdi-info"></i></a>';
        }
      }
    }
    return $results;
  }


  function getQuery($select = '*', $groupBy = NULL, $offset = NULL, $rowCount = NULL, $sort = NULL) {
    $where = $args = array();
    if ($this->_pageType) {
      $where[] = "page_type = %1";
      $args[1] = array($this->_pageType, 'String');
    }
    if ($this->_pageId) {
      $where[] = "page_id= %2";
      $args[2] = array($this->_pageId, 'Integer');
    }
    if ($this->_referrerType) {
      $where[] = "referrer_type = %3";
      $args[3] = array($this->_referrerType, 'String');
    }
    if ($this->_referrerNetwork) {
      $where[] = "referrer_network = %4";
      $args[4] = array($this->_referrerNetwork, 'String');
    }
    if ($this->_referrerNetwork) {
      $where[] = "referrer_network = %4";
      $args[4] = array($this->_referrerNetwork, 'String');
    }
    if ($this->_entityId) {
      $where[] = "entity_id = %5";
      $args[5] = array($this->_entityId, 'Integer');
    }
    if ($this->_visitDateStart) {
      $where[] = "visit_date >= %6";
      $args[6] = array($this->_visitDateStart, 'String');
    }
    if ($this->_visitDateEnd) {
      $where[] = "visit_date <= %7";
      $args[7] = array($this->_visitDateEnd, 'String');
    }
    if ($this->_state) {
      $where[] = "state = %8";
      $args[8] = array($this->_state, 'Integer');
    }

    $where = implode(" AND ", $where);

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
    return implode(' - ', $name);
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
          $filters[$name] = array(
            'value' => $value,
            'title' => $this->_allowedGet[$name],
            'url' => $this->_base."&".http_build_query($removeGet, '', '&'),
          );
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
              break;
          }
        }
      }
      $page->set('filters', $filters);
    }
    else {
      $page->set('filters', array());
    }
    if ($filters = $page->get('filters')) {
      $page->assign('filters', $filters);
    }
  }
}
//end of class

