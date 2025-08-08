<?php

class CRM_Contact_Form_Search_Custom_RFM extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  const RECURRING_NONRECURRING = 'all', RECURRING = 'recurring', NONRECURRING = 'non-recurring';
  const DATE_RANGE_DEFAULT = 'last 1 years to yesterday';

  /**
   * @var mixed[]
   */
  public $_instruments;
  /**
   * @var mixed[]
   */
  public $_contributionType;
  public $_queryColumns;
  public $_isExport;
  protected $_form;
  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  protected $_recurringStatus = [];
  protected $_contributionPage = NULL;
  protected $_defaultThresholds = [];
  protected $_template;
  protected $_segmentStats = [];
  protected $_showResults = TRUE;

  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_template = CRM_Core_Smarty::singleton();
    $this->_filled = FALSE;
    $this->_tableName = 'civicrm_temp_custom_RFM_' . CRM_Utils_String::createRandom(6);
    $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->_cstatus = $statuses;
    $this->_recurringStatus = [
      self::RECURRING_NONRECURRING => ts('All'),
      self::RECURRING => ts("Recurring Contribution"),
      self::NONRECURRING => ts("Non-recurring Contribution"),
    ];
    $this->_contributionPage = CRM_Contribute_PseudoConstant::contributionPage();
    $this->_instruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $this->_contributionType = CRM_Contribute_PseudoConstant::contributionType();
    $this->_config = CRM_Core_Config::singleton();
    $this->buildColumn();
  }

  function buildColumn(){
    $this->_queryColumns = [
      'contact_a.id' => 'id',
      'contact_a.sort_name' => 'sort_name',
      'contact_a.display_name' => 'display_name',
      'rfm.R' => 'recency_days',
      'rfm.F' => 'frequency_count',
      'rfm.M' => 'monetary_amount',
    ];
    $this->_columns = [
      ts('Contact ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Recency (Days)') => 'recency_days',
      ts('Frequency (Times)') => 'frequency_count',
      ts('Monetary (Amount)') => 'monetary_amount',
    ];
  }

  function buildForm(&$form){
    $this->_form = $form;
    $this->_form->addDateRange('receive_date', ts('Receive Date').' - '.ts('From'), NULL, FALSE);
    $this->_form->addRadio('recurring', ts('Recurring Contribution'), $this->_recurringStatus);
    $this->_form->assign('elements', ['receive_date', 'recurring']);

    $segment = CRM_Utils_Array::value('segment', $this->_formValues, '');
    $parsedSegment = self::parseRfmSegment($segment);
    if ($parsedSegment) {
      $this->_showResults = TRUE;
      $this->_form->assign('showResults', $this->_showResults);
    }
    $hasSegmentParam = !empty($segment);
    $this->_form->assign('hasSegmentParam', $hasSegmentParam);

    $this->_form->addNumber('rfm_r_value', ts('Recency (days since last donation)'), [
      'size' => 5,
      'maxlength' => 5,
      'min' => 0,
      'placeholder' => ts('e.g., 210'),
      'class' => 'rfm-input'
    ]);
    $this->_form->addNumber('rfm_f_value', ts('Frequency (number of donations)'), [
      'size' => 5,
      'maxlength' => 5,
      'min' => 0,
      'placeholder' => ts('e.g., 3'),
      'class' => 'rfm-input'
    ]);
    $this->_form->addNumber('rfm_m_value', ts('Monetary (total donation amount)'), [
      'size' => 12,
      'maxlength' => 12,
      'min' => 0,
      'placeholder' => ts('e.g., 21600'),
      'class' => 'rfm-input'
    ]);
    $this->_form->add('hidden', 'segment', '');
    $this->_form->add('hidden', 'ct', '');

    $formValues = $this->_formValues;
    if (empty($formValues['rfm_r_value'])) {
      $formValues = $this->setDefaultValues();
    }

    if (!$this->_filled) {
      $this->fillTable();
    }

    // Get RFM segments in original order (0→7)
    $rfmSegments = $this->prepareRfmSegments();

    // Sort segments by numeric_id DESC for display (7→0)
    $sortedRfmSegments = $rfmSegments;
    usort($sortedRfmSegments, function($a, $b) {
      return $b['numeric_id'] - $a['numeric_id']; // DESC sorting
    });

    $highRfmSegments = array_filter($sortedRfmSegments, function($segment) {
      return $segment['numeric_id'] >= 4;
    });

    $lowRfmSegments = array_filter($sortedRfmSegments, function($segment) {
      return $segment['numeric_id'] <= 3;
    });

    $this->_form->assign('highRfmSegments', $highRfmSegments);
    $this->_form->assign('lowRfmSegments', $lowRfmSegments);

    // Assign sorted segments to template
    $this->_form->assign('rfmSegments', $sortedRfmSegments);

    // Prepare RFM segment data for frontend
    $rfmSegmentData = $this->prepareRfmSegmentDataForFrontend($rfmSegments);
    $this->_form->assign('rfmSegmentDataJson', json_encode($rfmSegmentData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));

    $urlParams = $this->prepareUrlParams($formValues);
    $this->_form->assign('urlParams', $urlParams);
    // Set tittle
    $segment = CRM_Utils_Array::value('segment', $this->_formValues, '');
    $rfmModel = $this->getRfmModelName($segment);
    CRM_Utils_System::setTitle($rfmModel);
  }

  /**
   * Prepare RFM segment data for frontend consumption
   * Transform backend format to frontend expected format
   *
   * @param array $segments RFM segments from prepareRfmSegments()
   * @return array Frontend-formatted segment data (array indexed by numeric_id)
   */
  private function prepareRfmSegmentDataForFrontend($segments) {
    $frontendData = [];

    // Sort segments by numeric_id to ensure correct array order (0-7)
    usort($segments, function($a, $b) {
      return $a['numeric_id'] - $b['numeric_id'];
    });

    foreach ($segments as $segment) {
      $frontendData[] = [
        'title' => $segment['name'],
        'content' => $segment['description'],
        'rfm' => $segment['rfm_parsed']
      ];
    }

    return $frontendData;
  }

  /**
   * Parse RFM states from segment ID
   * Extract R/F/M status (low/high) from ID like 'RlFlMl'
   *
   * @param string $segmentId RFM segment ID
   * @return array RFM states array
   */
  private function parseRfmStatesFromId($segmentId) {
    if (strlen($segmentId) !== 6) {
      return ['r' => 'low', 'f' => 'low', 'm' => 'low']; // Default fallback
    }

    return [
      'r' => substr($segmentId, 1, 1) === 'h' ? 'high' : 'low',  // Position 1: R value
      'f' => substr($segmentId, 3, 1) === 'h' ? 'high' : 'low',  // Position 3: F value
      'm' => substr($segmentId, 5, 1) === 'h' ? 'high' : 'low'   // Position 5: M value
    ];
  }

  /**
   * Prepare RFM segment data for quick search links
   * Maintains original array order (by RFM binary sequence 0→7)
   * Enhanced with description and parsed RFM states
   *
   * @return array Array of segment data with calculated numeric IDs
   */
  private function prepareRfmSegments() {
    $segments = [
      [
        'id' => 'RlFlMl',
        'name' => ts('Dormant Small Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'low', 3 => 'low']),
        'css_class' => 'rfm-segment-hibernating-small',
        'description' => ts('These donors haven\'t donated in a long time, and their past donation frequency and amount are both low. They may have low engagement or experienced life changes. Reconnect with them using warm messages, share recent organizational impact, and reawaken their attention with simple and engaging content.')
      ],
      [
        'id' => 'RlFlMh',
        'name' => ts('Dormant Major Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'low', 3 => 'high']),
        'css_class' => 'rfm-segment-hibernating-big',
        'description' => ts('These donors haven’t contributed recently or frequently, but have previously made large donations. They show a strong potential for re-engagement. Personalized outreach by dedicated staff is recommended to understand their current situation and invite them to key events or gatherings.')
      ],
      [
        'id' => 'RlFhMl',
        'name' => ts('Lapsed Small Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'high', 3 => 'low']),
        'css_class' => 'rfm-segment-at-risk-small',
        'description' => ts('These were once regular small-amount donors who recently stopped giving. They may be facing financial difficulties or doubts about the organization. Show concern, emphasize the importance of each contribution, and offer flexible donation options such as monthly giving.')
      ],
      [
        'id' => 'RlFhMh',
        'name' => ts('Lapsed Major Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'high', 3 => 'high']),
        'css_class' => 'rfm-segment-at-risk-big',
        'description' => ts('These were once major and frequent donors who have recently disengaged. This is a high-priority group. Recommend senior leaders personally reach out for a one-on-one meeting to understand their concerns and rebuild trust.')
      ],
      [
        'id' => 'RhFlMl',
        'name' => ts('Recent Small Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'low', 3 => 'low']),
        'css_class' => 'rfm-segment-new-small',
        'description' => ts('These are new supporters with low donation frequency and amount. They represent future growth potential. Send welcome messages and educational materials to help them understand the mission and build a lasting relationship.')
      ],
      [
        'id' => 'RhFlMh',
        'name' => ts('Recent Major Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'low', 3 => 'high']),
        'css_class' => 'rfm-segment-new-big',
        'description' => ts('Although they have donated only a few times, their large gifts show strong alignment with the organization. Provide VIP treatment, invite them to strategy discussions or advisory panels, and foster long-term commitment.')
      ],
      [
        'id' => 'RhFhMl',
        'name' => ts('Loyal Small Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'high', 3 => 'low']),
        'css_class' => 'rfm-segment-loyal-small',
        'description' => ts('These are the most stable donors who give frequently in small amounts. They are emotionally connected to the organization. Show regular appreciation, offer member benefits, and involve them as volunteers or ambassadors.')
      ],
      [
        'id' => 'RhFhMh',
        'name' => ts('Loyal Major Donors'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'high', 3 => 'high']),
        'css_class' => 'rfm-segment-champions',
        'description' => ts('The most valuable supporters with high recency, frequency, and donation amount. They are key ambassadors and assets. Provide premier service, invite them to strategic planning, and consider naming opportunities to recognize their contributions.')
      ]
    ];

    // Calculate numeric_id and parse RFM states for each segment
    foreach ($segments as &$segment) {
      $segment['numeric_id'] = self::rfmCodeToNumericId($segment['id']);
      $segment['rfm_parsed'] = $this->parseRfmStatesFromId($segment['id']);
      $numericId = $segment['numeric_id'];
      if (isset($this->_segmentStats[$numericId])) {
        $segment['count'] = $this->_segmentStats[$numericId]['count'];
        $segment['percentage'] = $this->_segmentStats[$numericId]['percentage'];
      } else {
        $segment['count'] = 0;
        $segment['percentage'] = 0;
      }
    }

    return $segments;
  }

  function setDefaultValues() {
    // First try to get values from form values (for saved searches)
    $dateFrom = CRM_Utils_Array::value('receive_date_from', $this->_formValues);
    $dateTo = CRM_Utils_Array::value('receive_date_to', $this->_formValues);
    $segment = CRM_Utils_Array::value('segment', $this->_formValues);
    $recurring = CRM_Utils_Array::value('recurring', $this->_formValues);
    $rv = CRM_Utils_Array::value('rfm_r_value', $this->_formValues);
    $fv = CRM_Utils_Array::value('rfm_f_value', $this->_formValues);
    $mv = CRM_Utils_Array::value('rfm_m_value', $this->_formValues);

    $ct = CRM_Utils_Request::retrieve('ct', 'Boolean', CRM_Core_DAO::$_nullObject, FALSE, '');

    $dateRange = '';
    if (!empty($dateFrom) && !empty($dateTo)) {
      $dateRange = $dateFrom . '_to_' . $dateTo;
    } elseif (!empty($dateFrom)) {
      $dateRange = $dateFrom . '_to_' . date('Y-m-d');
    }

    if (empty($dateRange)) {
      $date = CRM_Utils_Request::retrieve('date', 'String', CRM_Core_DAO::$_nullObject, FALSE, '');
      if (!empty($date)) {
        $dateRange = $date;
      }
      else {
        $dateRange = self::DATE_RANGE_DEFAULT;
      }
    }
    $dateFilter = CRM_Utils_Date::strtodate($dateRange);
    if (empty($segment)) {
      $segment = CRM_Utils_Request::retrieve('segment', 'String', CRM_Core_DAO::$_nullObject, FALSE, '');
      $this->_showResults = FALSE;
    } else {
      $this->_showResults = TRUE;
      $parsedSegment = self::parseRfmSegment($segment);
      if ($parsedSegment) {
        $defaults['segment'] = $segment;
      }
    }
    // Second try to get values from Url (if not from save search)
    if (empty($recurring)) {
      $recurring = CRM_Utils_Request::retrieve('recurring', 'String', CRM_Core_DAO::$_nullObject, FALSE, self::RECURRING_NONRECURRING);
    }
    if (empty($rv)) {
      $rv = CRM_Utils_Request::retrieve('rv', 'Float', CRM_Core_DAO::$_nullObject);
    }
    if (empty($fv)) {
      $fv = CRM_Utils_Request::retrieve('fv', 'Float', CRM_Core_DAO::$_nullObject);
    }
    if (empty($mv)) {
      $mv = CRM_Utils_Request::retrieve('mv', 'Float', CRM_Core_DAO::$_nullObject);
    }

    // default when nothing
    $defaultThresholds = CRM_Contact_BAO_RFM::defaultThresholds($dateRange, 'all');
    $defaults = [
      'rfm_r_value' => $rv ?? $defaultThresholds['r'],
      'rfm_f_value' => $fv ?? $defaultThresholds['f'],
      'rfm_m_value' => $mv ?? $defaultThresholds['m'],
      'recurring' => $recurring,
      'receive_date_from' => $dateFilter['start'],
      'receive_date_to' => $dateFilter['end'],
      'segment' => $segment ?? '', // empty for landing page
      'ct' => $ct ?? '',
    ];
    $this->_defaultThresholds = [
      'r' => $defaults['rfm_r_value'],
      'f' => $defaults['rfm_f_value'],
      'm' => $defaults['rfm_m_value'],
    ];
    $this->_template->assign('rfmThresholds', $this->_defaultThresholds);

    return $defaults;
  }

  function qill(){
    $qill = [];
    $from = !empty($this->_formValues['receive_date_from']) ? $this->_formValues['receive_date_from'] : NULL;
    $to = !empty($this->_formValues['receive_date_to']) ? $this->_formValues['receive_date_to'] : NULL;
    if ($from || $to) {
      $to = empty($to) ? ts('no limit') : $to;
      $from = empty($from) ? ' ... ' : $from;
      $qill[1]['receiveDateRange'] = ts("Receive Date").': '. $from . ' ~ ' . $to;
    }
    else {
      $qill[1]['receiveDateRange'] = ts("Receive Date").': '. self::DATE_RANGE_DEFAULT;
    }
    $segment = CRM_Utils_Array::value('segment', $this->_formValues, '');
    $rfmModel = $this->getRfmModelName($segment);
    $qill[1]['rfmModel'] = ts('RFM Customer Segments').': '. $rfmModel;

    // Recurring contribution status
    $recurring = CRM_Utils_Array::value('recurring', $this->_formValues, self::RECURRING_NONRECURRING);
    if (isset($this->_recurringStatus[$recurring])) {
      $qill[1]['recurring'] = ts('Recurring Contribution').': '.$this->_recurringStatus[$recurring];
    }

    return $qill;
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  function count(){
    if (!$this->_showResults) {
        return 0;
    }
    if(!$this->_filled){
      $this->fillTable();
    }
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    return $dao->N;
  }

  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE){
    if (!$this->_showResults) {
      return "SELECT contact_a.id as contact_id FROM civicrm_contact contact_a WHERE 1 = 0";
    }
    $fields = !$onlyIDs ? "*" : "contact_a.id" ;
    if(!$this->_filled){
      // prepare rfm talbe
      $this->fillTable();
    }
    return $this->sql($fields, $offset, $rowcount, $sort, $includeContactIDs);
  }

  function sql($selectClause, $offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $groupBy = NULL) {
    if ($selectClause == '*') {
      $select = [];
      foreach ($this->_queryColumns as $tableDotColumn => $alias) {
        $select[] = "$tableDotColumn as $alias";
      }
      $selectClause = implode(', ', $select);
    }
    $sql = "SELECT $selectClause " . $this->from() . " WHERE ". $this->where($includeContactIDs);
    if ($groupBy) {
      $sql .= " $groupBy ";
    }
    $this->addSortOffset($sql, $offset, $rowcount, $sort);
    return $sql;
  }

  /**
   * Functions below generally don't need to be modified
   */
  function from() {
    $from = "
    FROM civicrm_contact contact_a
    INNER JOIN {$this->_tableName} rfm ON contact_a.id = rfm.contact_id
    ";
    return $from;
  }

  function where($includeContactIDs = false) {
    $sql = '';
    $clauses = [];
    $clauses[] = "contact_a.is_deleted = 0";

    if ($includeContactIDs) {
      $this->includeContactIDs($sql, $this->_formValues);
    }
    if (count($clauses)) {
      $sql = '('.CRM_Utils_Array::implode(' AND ', $clauses).')';
    }
    else {
      $sql = '(1)';
    }
    return $sql;
  }

  function having(){
    return '';
  }

  static function includeContactIDs(&$sql, &$formValues, $isExport = FALSE) {
    $contactIDs = [];
    foreach ($formValues as $id => $value) {
      list($contactID, $additionalID) = CRM_Core_Form::cbExtract($id);
      if ($value && !empty($contactID)) {
        $contactIDs[] = $contactID;
      }
    }

    if (!empty($contactIDs)) {
      $contactIDs = CRM_Utils_Array::implode(', ', $contactIDs);
      $sql .= " AND contact_a.contact_id IN ( $contactIDs )";
    }
  }

  function &columns(){
    return $this->_columns;
  }

  function alterRow(&$row) {
    if (!empty($row['monetary_amount']) && empty($this->_isExport)) {
      $row['monetary_amount'] = CRM_Utils_Money::format($row['monetary_amount']);
    }
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/RFM.tpl';
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * parseRfmSegment
   *
   * @param  string|null $segment
   * @return array|null
   */
  public static function parseRfmSegment($segment) {
    // Add null check at the beginning
    if ($segment === null || !isset($segment) || strlen($segment) !== 6) {
      return null;
    }
    // Parse format like "RlFhMl" -> ['r' => 'low', 'f' => 'high', 'm' => 'low']
    $pattern = '/^R([lh])F([lh])M([lh])$/i';
    if (!preg_match($pattern, $segment, $matches)) {
      return null;
    }

    return [
      'r' => strtolower($matches[1]) === 'l' ? 'low' : 'high',
      'f' => strtolower($matches[2]) === 'l' ? 'low' : 'high',
      'm' => strtolower($matches[3]) === 'l' ? 'low' : 'high',
    ];
  }

/**
   * Convert RFM code (e.g., "RlFlMl") to numeric ID (0-7)
   * Using binary encoding: R×4 + F×2 + M×1
   *
   * @param string $rfmCode RFM code in format "RlFlMl"
   * @return int Numeric ID (0-7)
   */
  public static function rfmCodeToNumericId($rfmCode) {
    if (strlen($rfmCode) !== 6) {
      return 0; // Default fallback
    }

    // Extract R, F, M values (l=0, h=1)
    $r = (substr($rfmCode, 1, 1) === 'h') ? 1 : 0;  // Position 1: R value
    $f = (substr($rfmCode, 3, 1) === 'h') ? 1 : 0;  // Position 3: F value
    $m = (substr($rfmCode, 5, 1) === 'h') ? 1 : 0;  // Position 5: M value

    // Binary to decimal: R×4 + F×2 + M×1
    return ($r * 4) + ($f * 2) + ($m * 1);
  }

  /**
   * Convert numeric ID (0-7) to RFM code (e.g., "RlFlMl")
   *
   * @param int $numericId Numeric ID (0-7)
   * @return string RFM code in format "RlFlMl"
   */
  public static function numericIdToRfmCode($numericId) {
    if ($numericId < 0 || $numericId > 7) {
      return 'RlFlMl'; // Default fallback
    }

    // Convert to binary and extract R, F, M
    $r = ($numericId & 4) ? 'h' : 'l';  // Bit 2 (4)
    $f = ($numericId & 2) ? 'h' : 'l';  // Bit 1 (2)
    $m = ($numericId & 1) ? 'h' : 'l';  // Bit 0 (1)

    return "R{$r}F{$f}M{$m}";
  }

  function fillTable(){
    if ($this->_filled) {
      return;
    }
    $currentDefaults = [];
    if (empty($this->_defaultThresholds) && CRM_Utils_Request::retrieve('force', 'Integer', CRM_Core_DAO::$_nullObject)) {
      // duplicate call default values because parent preprocess will handler later
      $currentDefaults = $this->setDefaultValues();
    }
    else {
      $currentDefaults = $this->_formValues;
    }
    // Creaet date string
    $dateFrom = CRM_Utils_Array::value('receive_date_from', $currentDefaults);
    $dateTo = CRM_Utils_Array::value('receive_date_to', $currentDefaults);
    $dateString = '';
    if (!empty($dateFrom) && !empty($dateTo)) {
      $dateString = $dateFrom . '_to_' . $dateTo;
    }
    elseif (!empty($dateFrom)) {
      $dateString = $dateFrom . '_to_' . date('Y-m-d');
    }
    else {
      $dateString = self::DATE_RANGE_DEFAULT;
    }

    $thresholdType = CRM_Utils_Array::value('recurring', $currentDefaults) ?? 'all';

    $segment = $currentDefaults['segment'];
    $parsedSegment = self::parseRfmSegment($segment);
    if (!empty($parsedSegment)) {
      $rThreshold = CRM_Utils_Array::value('rfm_r_value', $currentDefaults);
      $fThreshold = CRM_Utils_Array::value('rfm_f_value', $currentDefaults);
      $mThreshold = CRM_Utils_Array::value('rfm_m_value', $currentDefaults);

      // Use parsedSegment to make thresholds positive or negative
      if ($parsedSegment['r'] === 'low') $rThreshold = -abs($rThreshold);
      else $rThreshold = abs($rThreshold);

      if ($parsedSegment['f'] === 'low') $fThreshold = -abs($fThreshold);
      else $fThreshold = abs($fThreshold);

      if ($parsedSegment['m'] === 'low') $mThreshold = -abs($mThreshold);
      else $mThreshold = abs($mThreshold);
    }
    else {
      $rThreshold = $fThreshold = $mThreshold = 0.0;
    }
    $suffix = CRM_Utils_String::createRandom(6);
    $rfm = new CRM_Contact_BAO_RFM($suffix, $dateString, $rThreshold, $fThreshold, $mThreshold, $thresholdType);
    $result = $rfm->calcRFM();
    $this->_tableName = $result['table'];
    if (empty($parsedSegment)) {
      $this->calculateSegmentStatsFromTable($currentDefaults);
    }
    $this->_filled = TRUE;
  }

  function prepareUrlParams($formValues) {
    $dateFrom = CRM_Utils_Array::value('receive_date_from', $formValues);
    $dateTo = CRM_Utils_Array::value('receive_date_to', $formValues);
    $ct = CRM_Utils_Array::value('ct', $formValues);
    $dateParam = '';
    if (!empty($dateFrom) && !empty($dateTo)) {
      $dateParam = $dateFrom . '_to_' . $dateTo;
    } elseif (!empty($dateFrom)) {
      $dateParam = $dateFrom . '_to_' . date('Y-m-d');
    } else {
      $dateParam = self::DATE_RANGE_DEFAULT;
    }

    $recurring = CRM_Utils_Array::value('recurring', $formValues, self::RECURRING_NONRECURRING);
    $rv = CRM_Utils_Array::value('rfm_r_value', $formValues, $this->_defaultThresholds['r'] ?? 0);
    $fv = CRM_Utils_Array::value('rfm_f_value', $formValues, $this->_defaultThresholds['f'] ?? 0);
    $mv = CRM_Utils_Array::value('rfm_m_value', $formValues, $this->_defaultThresholds['m'] ?? 0);
    $customSearchID = CRM_Utils_Request::retrieve('csid', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, '');
    $params = [
      'reset' => 1,
      'csid' => $customSearchID,
      'force' => 1,
      'date' => $dateParam,
      'recurring' => $recurring,
      'rv' => $rv,
      'fv' => $fv,
      'mv' => $mv
    ];
    if ($ct) {
      $params['ct'] = $ct;
    }

    return $params;
  }

  /**
   * Calculate RFM segment statistics from the existing RFM data table
   */
  function calculateSegmentStatsFromTable($formValues) {
    // Check if a specific segment is selected
    $segment = CRM_Utils_Array::value('segment', $formValues, '');
    $parsedSegment = self::parseRfmSegment($segment);

    // If no segment, do not calculate Segment Status
    if (!empty($parsedSegment)) {
      return;
    }

    $rThreshold = abs(CRM_Utils_Array::value('rfm_r_value', $formValues, $this->_defaultThresholds['r'] ?? 0));
    $fThreshold = abs(CRM_Utils_Array::value('rfm_f_value', $formValues, $this->_defaultThresholds['f'] ?? 0));
    $mThreshold = abs(CRM_Utils_Array::value('rfm_m_value', $formValues, $this->_defaultThresholds['m'] ?? 0));

    $statsSql = "
      SELECT
        COUNT(DISTINCT contact_a.id) as total_count,
        SUM(CASE
          WHEN rfm.R >= {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_0_count,  -- RlFlMl (R low: >=, F low: <=, M low: <=)
        SUM(CASE
          WHEN rfm.R >= {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M >= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_1_count,  -- RlFlMh (R low: >=, F low: <=, M high: >=)
        SUM(CASE
          WHEN rfm.R >= {$rThreshold} AND rfm.F >= {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_2_count,  -- RlFhMl (R low: >=, F high: >=, M low: <=)
        SUM(CASE
          WHEN rfm.R >= {$rThreshold} AND rfm.F >= {$fThreshold} AND rfm.M >= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_3_count,  -- RlFhMh (R low: >=, F high: >=, M high: >=)
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_4_count,  -- RhFlMl (R high: <=, F low: <=, M low: <=)
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M >= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_5_count,  -- RhFlMh (R high: <=, F low: <=, M high: >=)
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F >= {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_6_count,  -- RhFhMl (R high: <=, F high: >=, M low: <=)
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F >= {$fThreshold} AND rfm.M >= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_7_count   -- RhFhMh (R high: <=, F high: >=, M high: >=)
      FROM civicrm_contact contact_a
      INNER JOIN {$this->_tableName} rfm ON contact_a.id = rfm.contact_id
      WHERE contact_a.is_deleted = 0
    ";
    $statsQuery = CRM_Core_DAO::executeQuery($statsSql);
    $statsQuery->fetch();

    $totalCount = $statsQuery->total_count;
    $this->_segmentStats = [];
    for ($i = 0; $i <= 7; $i++) {
      $countField = "segment_{$i}_count";
      $segmentCount = $statsQuery->$countField ?? 0;
      $percentage = $totalCount > 0 ? round(($segmentCount / $totalCount) * 100, 1) : 0;

      $this->_segmentStats[$i] = [
        'count' => $segmentCount,
        'percentage' => $percentage,
        'rfm_code' => self::numericIdToRfmCode($i)
      ];
    }
    $this->_segmentStats['total'] = $totalCount;
  }
  /**
   * get Rfm model name
   */
  function getRfmModelName($segment) {
    $rfmModel = '';
    if (!empty($segment)) {
      $parsedSegment = self::parseRfmSegment($segment);
      if ($parsedSegment) {
        $segments = $this->prepareRfmSegments();
        foreach ($segments as $segmentData) {
          if ($segmentData['id'] === $segment) {
            $rfmModel = $segmentData['name'];
            break;
          }
        }
      }
    }
    if (empty($rfmModel)) {
      $rfmModel = ts('RFM Model - Reactivating Dormant Supporters and Expanding Loyal Supporters');
    }
    return $rfmModel;
  }

  /**
   * Handle form submission and redirect with URL parameters
   */
  function postCustomSearchProcess(&$form) {
    $buttonName = $form->controller->getButtonName();
    if (strpos($buttonName, '_qf_Custom_refresh') !== FALSE) {
      $formValues = $form->exportValues();
      $urlParams = $this->prepareUrlParams($formValues);
      if (empty($urlParams['csid'])) {
         $customSearchID = $form->get('customSearchID');
         $urlParams['csid'] = $customSearchID;
      }
      $queryString = http_build_query($urlParams);
      $redirectUrl = CRM_Utils_System::url('civicrm/contact/search/custom', $queryString, TRUE);
      CRM_Utils_System::redirect($redirectUrl);
    }
  }
}