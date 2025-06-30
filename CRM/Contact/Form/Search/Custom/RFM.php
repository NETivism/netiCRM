<?php

class CRM_Contact_Form_Search_Custom_RFM extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  const RECURRING_NONRECURRING = 2, RECURRING = 1, NONRECURRING = 0;
  const DATE_RANGE_DEFAULT = 'last 1 years to today';

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

    if (!$this->_filled) {
      $this->fillTable();
      $this->_filled = TRUE;
    }

    // Get RFM segments in original order (0→7)
    $rfmSegments = $this->prepareRfmSegments();

    // Sort segments by numeric_id DESC for display (7→0)
    $sortedRfmSegments = $rfmSegments;
    usort($sortedRfmSegments, function($a, $b) {
      return $b['numeric_id'] - $a['numeric_id']; // DESC sorting
    });

    // Assign sorted segments to template
    $this->_form->assign('rfmSegments', $sortedRfmSegments);

    // Prepare RFM segment data for frontend
    $rfmSegmentData = $this->prepareRfmSegmentDataForFrontend($rfmSegments);
    $this->_form->assign('rfmSegmentDataJson', json_encode($rfmSegmentData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));

    $formValues = $this->_formValues;
    if (empty($formValues)) {
      $formValues = $this->setDefaultValues();
    }
    $urlParams = $this->prepareUrlParams($formValues);
    $this->_form->assign('urlParams', $urlParams);
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
        'name' => ts('RFM Hibernating Small'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'low', 3 => 'low']),
        'css_class' => 'rfm-segment-hibernating-small',
        'description' => ts('These donors have not participated in donations for a long time, with low donation frequency and amounts. They may have reduced attention to the organization or experienced life changes. Recommend re-establishing contact through warm care messages, sharing recent organizational achievements and impact stories with simple, understandable content to rekindle their interest.')
      ],
      [
        'id' => 'RlFlMh',
        'name' => ts('RFM Hibernating Big'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'low', 3 => 'high']),
        'css_class' => 'rfm-segment-hibernating-big',
        'description' => ts('Although they have not donated for a long time and have infrequent donations, they previously provided larger amounts of support, showing a certain level of recognition for the organization. This group has high reactivation potential. Recommend arranging personalized care contact to understand their current situation and invite them to important organizational events or sharing sessions.')
      ],
      [
        'id' => 'RlFhMl',
        'name' => ts('RFM At Risk Small'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'high', 3 => 'low']),
        'css_class' => 'rfm-segment-at-risk-small',
        'description' => ts('Previously stable small-amount donors who participated regularly but recently stopped donating. They may be facing financial pressure or have concerns about the organization. Recommend proactively caring about their situation, emphasizing the importance of every small donation, and providing more flexible donation methods such as monthly small recurring donations.')
      ],
      [
        'id' => 'RlFhMh',
        'name' => ts('RFM At Risk Big'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'low', 2 => 'high', 3 => 'high']),
        'css_class' => 'rfm-segment-at-risk-big',
        'description' => ts('Former important supporters who donated frequently with high amounts but recently stopped participating. This group requires special attention. Recommend senior management personally reach out, arrange face-to-face meetings to understand their thoughts and rebuild trust relationships.')
      ],
      [
        'id' => 'RhFlMl',
        'name' => ts('RFM New Small'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'low', 3 => 'low']),
        'css_class' => 'rfm-segment-new-small',
        'description' => ts('New friends who just started following the organization. Although current donation amounts are small and infrequent, they represent the organization\'s future potential. Recommend welcome messages and regular educational content to help them better understand the organization\'s mission and gradually cultivate long-term support relationships.')
      ],
      [
        'id' => 'RhFlMh',
        'name' => ts('RFM New Big'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'low', 3 => 'high']),
        'css_class' => 'rfm-segment-new-big',
        'description' => ts('Although donation frequency is low, they are willing to provide larger amounts of support at once, showing strong recognition of the organization. Recommend providing VIP-level service experience, inviting participation in organizational strategy discussions or advisory meetings, making them feel valued and having opportunities to develop into long-term major supporters.')
      ],
      [
        'id' => 'RhFhMl',
        'name' => ts('RFM Loyal Small'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'high', 3 => 'low']),
        'css_class' => 'rfm-segment-loyal-small',
        'description' => ts('The organization\'s most stable foundation, continuously and frequently providing small support. They have deep emotional connections with the organization. Recommend regular expressions of gratitude, providing exclusive member benefits, and considering inviting them to become organizational volunteers or ambassadors to exert greater influence.')
      ],
      [
        'id' => 'RhFhMh',
        'name' => ts('RFM Champions'),
        'rfm_code' => ts('R %1 F %2 M %3', [1 => 'high', 2 => 'high', 3 => 'high']),
        'css_class' => 'rfm-segment-champions',
        'description' => ts('The organization\'s most valuable partners, performing excellently in all aspects. They are spokespersons and important resources for the organization. Recommend providing the highest level of service, regularly inviting participation in organizational governance or strategic planning, and considering establishing named projects to make them feel their important contributions to organizational development.')
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
    // default from form values
    // nothing to do

    // default from url
    $date = CRM_Utils_Request::retrieve('date', 'String', CRM_Core_DAO::$_nullObject, FALSE, '');
    if (!empty($date)) {
      $dateRange = $date;
    }
    else {
      $dateRange = self::DATE_RANGE_DEFAULT;
    }
    $dateFilter = CRM_Utils_Date::strtodate($dateRange);

    $segment = CRM_Utils_Request::retrieve('segment', 'String', CRM_Core_DAO::$_nullObject, FALSE, '');
    $parsedSegment = self::parseRfmSegment($segment);
    if ($parsedSegment) {
      $defaults['segment'] = $segment;
    }

    $recurring = CRM_Utils_Request::retrieve('recurring', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, self::RECURRING_NONRECURRING);

    $rv = CRM_Utils_Request::retrieve('rv', 'Float', CRM_Core_DAO::$_nullObject);
    $fv = CRM_Utils_Request::retrieve('fv', 'Float', CRM_Core_DAO::$_nullObject);
    $mv = CRM_Utils_Request::retrieve('mv', 'Float', CRM_Core_DAO::$_nullObject);

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
      $rfmModel = ts('Custom');
      $rValue = CRM_Utils_Array::value('rfm_r_value', $this->_formValues, 0);
      $fValue = CRM_Utils_Array::value('rfm_f_value', $this->_formValues, 0);
      $mValue = CRM_Utils_Array::value('rfm_m_value', $this->_formValues, 0);
      $rfmModel .= " (R: {$rValue}, F: {$fValue}, M: {$mValue})";
    }
    $qill[1]['rfmModel'] = ts('RFM Model').': '. $rfmModel;

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
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    return $dao->N;
  }

  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE){
    $fields = !$onlyIDs ? "*" : "contact_a.id as contact_id" ;
    if(!$this->_filled){
      // prepare rfm talbe
      $this->fillTable();
      $this->_filled = TRUE;
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

  function summary(){
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $sql = "
    SELECT COUNT(DISTINCT contact_a.id) as total_contacts,
    AVG(rfm.R) as avg_recency,
    AVG(rfm.F) as avg_frequency,
    AVG(rfm.M) as avg_monetary,
    SUM(rfm.M) as total_monetary
  FROM civicrm_contact contact_a
  INNER JOIN {$this->_tableName} rfm ON contact_a.id = rfm.contact_id";
  $whereClause = $this->where();
  if (!empty($whereClause) && $whereClause !== '(1)') {
    $sql .= " WHERE $whereClause";
  }
  $query = CRM_Core_DAO::executeQuery($sql);
  $query->fetch();
  $summary = [];
  if ($query->total_contacts) {
    $summary['search_results'] = [
      'label' => ts('RFM Analysis Results'),
      'value' => '',
    ];
    $totalAmount = '$' . CRM_Utils_Money::format($query->total_monetary, ' ');
    $avgAmount = '$' . CRM_Utils_Money::format($query->avg_monetary, ' ');
    $avgRecency = round($query->avg_recency, 1);
    $avgFrequency = round($query->avg_frequency, 1);
    $summary['search_results']['value'] =
      ts('Total Amount: %1', [1 => $totalAmount]) . ' / ' .
      ts('Avg Amount: %1', [1 => $avgAmount]) . ' / ' .
      ts('Avg Recency: %1 days', [1 => $avgRecency]) . ' / ' .
      ts('Avg Frequency: %1 times', [1 => $avgFrequency]);
    }
    return $summary;
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
   * @param  string $segment
   * @return array|null
   */
  public static function parseRfmSegment(string $segment) {
    if (!isset($segment) || strlen($segment) !== 6) {
      return null;
    }

    // Parse format like "RlFhMl" -> ['r' => 'low', 'f' => 'high', 'm' => 'low']
    $pattern = '/^R([lh])F([lh])M([lh])$/i';
    if (!preg_match($pattern, $segment, $matches)) {
      return null;
    }

    return [
      'r' => strtolower($matches[1]) === 'l' ? 'low' : 'high',
      'f' => strtolower($matches[3]) === 'l' ? 'low' : 'high',
      'm' => strtolower($matches[2]) === 'l' ? 'low' : 'high',
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

    $thresholdType = CRM_Utils_Array::value('recurring', $currentDefaults);

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
    $this->calculateSegmentStatsFromTable($currentDefaults);
  }

  function prepareUrlParams($formValues) {
    $dateFrom = CRM_Utils_Array::value('receive_date_from', $formValues);
    $dateTo = CRM_Utils_Array::value('receive_date_to', $formValues);
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

    return [
      'reset' => 1,
      'csid' => $customSearchID,
      'force' => 1,
      'date' => $dateParam,
      'recurring' => $recurring,
      'rv' => $rv,
      'fv' => $fv,
      'mv' => $mv
    ];
  }

  /**
   * Calculate RFM segment statistics from the existing RFM data table
   */
  function calculateSegmentStatsFromTable($formValues) {
    $rThreshold = CRM_Utils_Array::value('rfm_r_value', $formValues, $this->_defaultThresholds['r'] ?? 0);
    $fThreshold = CRM_Utils_Array::value('rfm_f_value', $formValues, $this->_defaultThresholds['f'] ?? 0);
    $mThreshold = CRM_Utils_Array::value('rfm_m_value', $formValues, $this->_defaultThresholds['m'] ?? 0);
    $statsSql = "
      SELECT
        COUNT(DISTINCT contact_a.id) as total_count,
        SUM(CASE
          WHEN rfm.R > {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_0_count,  -- RlFlMl
        SUM(CASE
          WHEN rfm.R > {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M > {$mThreshold}
          THEN 1 ELSE 0 END) as segment_1_count,  -- RlFlMh
        SUM(CASE
          WHEN rfm.R > {$rThreshold} AND rfm.F > {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_2_count,  -- RlFhMl
        SUM(CASE
          WHEN rfm.R > {$rThreshold} AND rfm.F > {$fThreshold} AND rfm.M > {$mThreshold}
          THEN 1 ELSE 0 END) as segment_3_count,  -- RlFhMh
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_4_count,  -- RhFlMl
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F <= {$fThreshold} AND rfm.M > {$mThreshold}
          THEN 1 ELSE 0 END) as segment_5_count,  -- RhFlMh
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F > {$fThreshold} AND rfm.M <= {$mThreshold}
          THEN 1 ELSE 0 END) as segment_6_count,  -- RhFhMl
        SUM(CASE
          WHEN rfm.R <= {$rThreshold} AND rfm.F > {$fThreshold} AND rfm.M > {$mThreshold}
          THEN 1 ELSE 0 END) as segment_7_count   -- RhFhMh
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
      $segmentCount = $statsQuery->$countField;
      $percentage = $totalCount > 0 ? round(($segmentCount / $totalCount) * 100, 1) : 0;

      $this->_segmentStats[$i] = [
        'count' => $segmentCount,
        'percentage' => $percentage,
        'rfm_code' => self::numericIdToRfmCode($i)
      ];
    }
    $this->_segmentStats['total'] = $totalCount;
  }
}