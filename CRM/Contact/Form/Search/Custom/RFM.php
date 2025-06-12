<?php

class CRM_Contact_Form_Search_Custom_RFM extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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
  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  protected $_recurringStatus = array();
  protected $_contributionPage = NULL;
  protected $_defaultThresholds = [
    'recency' => 210,
    'frequency' => 3,
    'monetary' => 21600
  ];

  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_filled = FALSE;
    $this->_tableName = 'civicrm_temp_custom_RFM_' . CRM_Utils_String::createRandom(6);
    $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->_cstatus = $statuses;
    $this->_recurringStatus = array(
      2 => ts('All'),
      1 => ts("Recurring Contribution"),
      0 => ts("Non-recurring Contribution"),
    );
    $this->_contributionPage = CRM_Contribute_PseudoConstant::contributionPage();
    $this->_instruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $this->_contributionType = CRM_Contribute_PseudoConstant::contributionType();
    $this->_config = CRM_Core_Config::singleton();
    $this->buildColumn();
  }

  function buildColumn(){
    $this->_queryColumns = array(
      'contact_a.id' => 'id',
      'contact_a.sort_name' => 'sort_name',
      'contact_a.display_name' => 'display_name',
      'rfm.R' => 'recency_days',
      'rfm.F' => 'frequency_count',
      'rfm.M' => 'monetary_amount',
      'email.email' => 'email',
      'phone.phone' => 'phone',
    );
    $this->_columns = array(
      ts('Contact ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Recency (Days)') => 'recency_days',
      ts('Frequency (Times)') => 'frequency_count',
      ts('Monetary (Amount)') => 'monetary_amount',
      ts('Email') => 'email',
      ts('Phone') => 'phone',
    );
  }

  function buildForm(&$form){
    $form->addDateRange('receive_date', ts('Receive Date').' - '.ts('From'), NULL, FALSE);
    $form->addRadio('recurring', ts('Recurring Contribution'), $this->_recurringStatus);
    $form->assign('elements', array('receive_date', 'recurring'));

    $form->addNumber('rfm_r_value', ts('Recency (days since last donation)'), array(
      'size' => 5,
      'maxlength' => 5,
      'min' => 0,
      'placeholder' => ts('e.g., 210'),
      'class' => 'rfm-input'
    ));
    $form->addNumber('rfm_f_value', ts('Frequency (number of donations)'), array(
      'size' => 5,
      'maxlength' => 5,
      'min' => 0,
      'placeholder' => ts('e.g., 3'),
      'class' => 'rfm-input'
    ));
    $form->addNumber('rfm_m_value', ts('Monetary (total donation amount)'), array(
      'size' => 12,
      'maxlength' => 12,
      'min' => 0,
      'placeholder' => ts('e.g., 21600'),
      'class' => 'rfm-input'
    ));

    $form->assign('rfmThresholds', $this->_defaultThresholds);

    $rfmSegments = $this->prepareRfmSegments();
    $form->assign('rfmSegments', $rfmSegments);

    if (!empty($this->_formValues['rfm_segment_filter'])) {
        $form->assign('selectedSegment', $this->_formValues['rfm_segment_filter']);
    }
  }

  function setDefaultValues() {
    $defaults  = [
      'rfm_r_value' => $this->_defaultThresholds['recency'],
      'rfm_f_value' => $this->_defaultThresholds['frequency'],
      'rfm_m_value' => $this->_defaultThresholds['monetary']
    ];
    $rfmSegment = CRM_Utils_Request::retrieve('rfm_segment', 'String', CRM_Core_DAO::$_nullObject, false);
    if (isset($rfmSegment) && $rfmSegment !== null && is_numeric($rfmSegment)) {
      $segmentId = (int)$rfmSegment;
      $segment = self::parseRfmSegment($rfmSegment);
      if ($segment) {
        $this->_formValues['rfm_segment_filter'] = [
          'id' => $segmentId,
          'recency' => $segment['recency'],
          'frequency' => $segment['frequency'],
          'monetary' => $segment['monetary']
        ];
        $defaults['selected_rfm_segment'] = $segmentId;
      }
    }
    return $defaults;
  }

  function qill(){
    $qill = array();
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
      $select = array();
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
    //TODO JOIN RFM table (rfm table / contact table)
    $from = "
    FROM civicrm_contact contact_a
    INNER JOIN {$this->_tableName} rfm ON contact_a.id = rfm.contact_id
    LEFT JOIN civicrm_email email ON contact_a.id = email.contact_id AND email.is_primary = 1
    LEFT JOIN civicrm_phone phone ON contact_a.id = phone.contact_id AND phone.is_primary = 1
    ";
    return $from;
  }

  function where($includeContactIDs = false) {
    $sql = '';
    $clauses = array();
    $clauses[] = "contact_a.is_deleted = 0";

    if (!empty($this->_formValues['rfm_segment_filter'])) {
        $segment = $this->_formValues['rfm_segment_filter'];
        $rThreshold = CRM_Utils_Array::value('rfm_r_value', $this->_formValues, $this->_defaultThresholds['recency']);
        $fThreshold = CRM_Utils_Array::value('rfm_f_value', $this->_formValues, $this->_defaultThresholds['frequency']);
        $mThreshold = CRM_Utils_Array::value('rfm_m_value', $this->_formValues, $this->_defaultThresholds['monetary']);
        if ($segment['recency'] === 'high') {
            $clauses[] = "rfm.R <= " . (int)$rThreshold;
        } else {
            $clauses[] = "rfm.R > " . (int)$rThreshold;
        }
        if ($segment['frequency'] === 'high') {
            $clauses[] = "rfm.F >= " . (int)$fThreshold;
        } else {
            $clauses[] = "rfm.F < " . (int)$fThreshold;
        }
        if ($segment['monetary'] === 'high') {
            $clauses[] = "rfm.M >= " . (int)$mThreshold;
        } else {
            $clauses[] = "rfm.M < " . (int)$mThreshold;
        }
    }

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
    $contactIDs = array();
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
    $count = $this->count();
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
  $summary = array();
  $summary['search_results'] = array(
    'label' => ts('RFM Analysis Results'),
    'value' => '',
  );
  $totalAmount = '$' . CRM_Utils_Money::format($query->total_monetary, ' ');
  $avgAmount = '$' . CRM_Utils_Money::format($query->avg_monetary, ' ');
  $avgRecency = round($query->avg_recency, 1);
  $avgFrequency = round($query->avg_frequency, 1);

  $summary['search_results']['value'] =
    ts('Total: %1 contacts', array(1 => $count)) . ' | ' .
    ts('Total Amount: %1', array(1 => $totalAmount)) . ' | ' .
    ts('Avg Amount: %1', array(1 => $avgAmount)) . ' | ' .
    ts('Avg Recency: %1 days', array(1 => $avgRecency)) . ' | ' .
    ts('Avg Frequency: %1 times', array(1 => $avgFrequency));
    return $summary;
  }

  function alterRow(&$row) {
    if (!empty($row['monetary_amount']) && empty($this->_isExport)) {
      $row['monetary_amount'] = CRM_Utils_Money::format($row['monetary_amount']);
    }
    if ($this->_isExport) {
      if (empty($row['email'])) {
        $row['email'] = '';
      }
      if (empty($row['phone'])) {
        $row['phone'] = '';
      }
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
   * Parse RFM segment ID to extract R, F, M level values
   * 
   * RFM Segment Mapping Table:
   * +--------+--------+---+---+---+------------------+
   * | Seg ID | Binary | R | F | M | Donor Type       |
   * +--------+--------+---+---+---+------------------+
   * |   0    |  000   | L | L | L | Hibernating Small|
   * |   1    |  001   | L | L | H | Hibernating Big  |
   * |   2    |  010   | L | H | L | At Risk Small    |
   * |   3    |  011   | L | H | H | At Risk Big      |
   * |   4    |  100   | H | L | L | New Small        |
   * |   5    |  101   | H | L | H | New Big          |
   * |   6    |  110   | H | H | L | Loyal Small      |
   * |   7    |  111   | H | H | H | Champions        |
   * +--------+--------+---+---+---+------------------+
   * 
   * Binary representation: First bit = R, Second bit = F, Third bit = M
   * H = High (above threshold), L = Low (below threshold)
   * 
   * @param int $segmentId Segment ID (0-7)
   * @return array|null Array with recency, frequency, monetary levels ('high'/'low')
   *                    Returns null for invalid segment IDs
   * 
   * @example
   * parseRfmSegment(0) => ['recency' => 'low', 'frequency' => 'low', 'monetary' => 'low']
   * parseRfmSegment(7) => ['recency' => 'high', 'frequency' => 'high', 'monetary' => 'high']
   * parseRfmSegment(8) => null (invalid)
   */
  private static function parseRfmSegment($segmentId) {
    if (!isset($segmentId) || $segmentId < 0 || $segmentId > 7) {
      return null;
    }
    
    // Convert to 3-bit binary and extract R, F, M values
    $binary = str_pad(decbin($segmentId), 3, '0', STR_PAD_LEFT);
    
    return [
      'recency' => $binary[0] ? 'high' : 'low',   // Recent vs Old
      'frequency' => $binary[1] ? 'high' : 'low', // Frequent vs Rare
      'monetary' => $binary[2] ? 'high' : 'low'   // Big vs Small
    ];
  }

  /**
   * Prepare RFM segment data for quick search links
   *
   * @return array Array of segment data with ID, name, and CSS class
   */
  private function prepareRfmSegments() {
    return [
      [
        'id' => 0,
        'name' => ts('RFM Hibernating Small'),
        'rfm_code' => ts('R Low F Low M Low'),
        'css_class' => 'rfm-segment-hibernating-small'
      ],
      [
        'id' => 1,
        'name' => ts('RFM Hibernating Big'),
        'rfm_code' => ts('R Low F Low M High'),
        'css_class' => 'rfm-segment-hibernating-big'
      ],
      [
        'id' => 2,
        'name' => ts('RFM At Risk Small'),
        'rfm_code' => ts('R Low F High M Low'),
        'css_class' => 'rfm-segment-at-risk-small'
      ],
      [
        'id' => 3,
        'name' => ts('RFM At Risk Big'),
        'rfm_code' => ts('R Low F High M High'),
        'css_class' => 'rfm-segment-at-risk-big'
      ],
      [
        'id' => 4,
        'name' => ts('RFM New Small'),
        'rfm_code' => ts('R High F Low M Low'),
        'css_class' => 'rfm-segment-new-small'
      ],
      [
        'id' => 5,
        'name' => ts('RFM New Big'),
        'rfm_code' => ts('R High F Low M High'),
        'css_class' => 'rfm-segment-new-big'
      ],
      [
        'id' => 6,
        'name' => ts('RFM Loyal Small'),
        'rfm_code' => ts('R High F High M Low'),
        'css_class' => 'rfm-segment-loyal-small'
      ],
      [
        'id' => 7,
        'name' => ts('RFM Champions'),
        'rfm_code' => ts('R High F High M High'),
        'css_class' => 'rfm-segment-champions'
      ]
    ];
  }

  function fillTable(){
    // Get threshold value
    $rThreshold = CRM_Utils_Array::value('rfm_r_value', $this->_formValues, $this->_defaultThresholds['recency']);
    $fThreshold = CRM_Utils_Array::value('rfm_f_value', $this->_formValues, $this->_defaultThresholds['frequency']);
    $mThreshold = CRM_Utils_Array::value('rfm_m_value', $this->_formValues, $this->_defaultThresholds['monetary']);

    // Creaet date string
    $dateString = $this->buildDateString();

    $thresholdType = CRM_Utils_Array::value('recurring', $this->_formValues, 'all');
    $suffix = CRM_Utils_String::createRandom(6);
    $rfm = new CRM_Contact_BAO_RFM($suffix, $dateString, $rThreshold, $fThreshold, $mThreshold, $thresholdType);
    $result = $rfm->calcRFM();
    $this->_tableName = $result['table'];
  }

  /**
   * Creaet date string
   */
  protected function buildDateString() {
    $dateFrom = CRM_Utils_Array::value('receive_date_from', $this->_formValues);
    $dateTo = CRM_Utils_Array::value('receive_date_to', $this->_formValues);
    if (empty($dateFrom) && empty($dateTo)) {
      return 'last 365 days';
    }
    if (!empty($dateFrom) && !empty($dateTo)) {
      return $dateFrom . '_to_' . $dateTo;
    }
    if (!empty($dateFrom)) {
      return $dateFrom . '_to_' . date('Y-m-d');
    }
    return '1970-01-01_to_' . $dateTo;
  }
}