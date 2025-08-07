<?php

/**
 * RFM Analysis Class
 * 
 * Used for calculating and analyzing RFM (Recency, Frequency, Monetary) metrics
 */
class CRM_Contact_BAO_RFM {
  /**
   * @var string Random suffix used for generating temporary table names
   */
  public $_suffix;
  
  /**
   * @var array Stores threshold values for R, F, M
   */
  protected $_thresholds = [
    'r' => null,
    'f' => null,
    'm' => null
  ];
  
  /**
   * @var array Stores whether metrics should use reverse comparison
   */
  protected $_reverse = [
    'r' => false,
    'f' => false,
    'm' => false
  ];
  
  /**
   * @var string Date filter string
   */
  protected $_dateString = '';
  
  /**
   * @var string Threshold type: 'recurring', 'non-recurring', 'all'
   */
  protected $_thresholdType = 'all';

  /**
   * @var array Temporary table names
   */
  protected $_tables = [
    'r' => null,
    'f' => null,
    'm' => null,
    'rfm' => null
  ];
  
  /**
   * Constructor
   * 
   * @param string $suffix temp table suffix of RFM
   * @param string $dateString Date filter string
   * @param float|int|null $rThreshold R threshold value, null means don't process
   *                                   If negative, reverse comparison is used
   *                                   If zero, include all data without threshold
   * @param float|int|null $fThreshold F threshold value, null means don't process
   *                                   If negative, reverse comparison is used
   *                                   If zero, include all data without threshold
   * @param float|int|null $mThreshold M threshold value, null means don't process
   *                                   If negative, reverse comparison is used
   *                                   If zero, include all data without threshold
   * @param string $thresholdType Threshold type: 'recurring', 'non-recurring', 'all'
   */
  public function __construct(string $suffix, string $dateString = '', $rThreshold = null, $fThreshold = null, $mThreshold = null, string $thresholdType = 'all') {
    if (empty($suffix)) {
      // Generate random suffix
      $this->_suffix = CRM_Utils_String::createRandom(6);
    }
    else {
      $this->_suffix = $suffix;
    }
    
    // Set thresholds and reverse flags
    if ($rThreshold !== null) {
      if ($rThreshold < 0) {
        $this->_thresholds['r'] = abs($rThreshold);
        $this->_reverse['r'] = true;
      } else {
        $this->_thresholds['r'] = $rThreshold;
      }
    }
    
    if ($fThreshold !== null) {
      if ($fThreshold < 0) {
        $this->_thresholds['f'] = abs($fThreshold);
        $this->_reverse['f'] = true;
      } else {
        $this->_thresholds['f'] = $fThreshold;
      }
    }
    
    if ($mThreshold !== null) {
      if ($mThreshold < 0) {
        $this->_thresholds['m'] = abs($mThreshold);
        $this->_reverse['m'] = true;
      } else {
        $this->_thresholds['m'] = $mThreshold;
      }
    }
    
    // Set date filter
    $this->_dateString = $dateString;

    // Set threshold type
    $this->_thresholdType = $thresholdType;
  }
  
  /**
   * Calculate R (Recency) metric
   * 
   * @param float|int $position Threshold position percentage or specific threshold value
   * @param bool $reverse Whether to use reverse comparison
   * @return array Array containing temporary table name and threshold value
   */
  public function calcR($position = 0.5, bool $reverse = FALSE) {
    if ($this->_thresholds['r'] !== null) {
      $position = $this->_thresholds['r'];
      $reverse = $this->_reverse['r'];
    }
    
    // Get end date for DATEDIFF calculation
    $endDateStr = $this->getEndDate();
    $aggregateFunc = "MIN(DATEDIFF('$endDateStr', DATE(contrib.receive_date)))";
    
    return $this->calcMetric('r', $position, $reverse, 'duration', $aggregateFunc);
  }
  
  /**
   * Calculate F (Frequency) metric
   * 
   * @param float|int $position Threshold position percentage or specific threshold value
   * @param bool $reverse Whether to use reverse comparison
   * @return array Array containing temporary table name and threshold value
   */
  public function calcF($position = 0.5, bool $reverse = FALSE) {
    if ($this->_thresholds['f'] !== null) {
      $position = $this->_thresholds['f'];
      $reverse = $this->_reverse['f'];
    }
    
    return $this->calcMetric('f', $position, $reverse, 'frequency', 'COUNT(contrib.id)');
  }
  
  /**
   * Calculate M (Monetary) metric
   * 
   * @param float|int $position Threshold position percentage or specific threshold value
   * @param bool $reverse Whether to use reverse comparison
   * @return array Array containing temporary table name and threshold value
   */
  public function calcM($position = 0.5, bool $reverse = FALSE) {
    if ($this->_thresholds['m'] !== null) {
      $position = $this->_thresholds['m'];
      $reverse = $this->_reverse['m'];
    }
    
    return $this->calcMetric('m', $position, $reverse, 'monetary', 'SUM(contrib.total_amount)');
  }
  
  /**
   * Generic metric calculation function
   * 
   * @param string $metricType Metric type (r, f, m)
   * @param float|int $position Threshold position percentage or specific threshold value
   * @param bool $reverse Whether to use reverse comparison
   * @param string $columnName Column name
   * @param string $aggregateFunc Aggregate function expression
   * @return array Array containing temporary table name and threshold value
   */
  protected function calcMetric(string $metricType, $position, bool $reverse, string $columnName, string $aggregateFunc) {
    $order = $reverse ? 'DESC' : 'ASC';
    $dateFilterSQL = '';
    
    if (!empty($this->_dateString)) {
      $dateFilterSQL = $this->getDateFilterSQL($this->_dateString);
    }
    
    // Add recurring filter based on threshold type
    $recurFilterSQL = $this->getRecurFilterSQL($this->_thresholdType);
    
    // Create temporary table
    $metricType = strtolower($metricType);
    $tempTableName = "civicrm_temp_{$metricType}threshold_{$this->_suffix}";
    $dataType = ($metricType === 'm') ? 'DECIMAL(10,2)' : 'INT(10)';
    
    $sqlCreateTempTable = "
    CREATE TEMPORARY TABLE {$tempTableName} (
      contact_id INT(10) UNSIGNED NOT NULL PRIMARY KEY,
      {$columnName} {$dataType} NOT NULL,
      INDEX ({$columnName})
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    CRM_Core_DAO::executeQuery($sqlCreateTempTable);
    
    // Special case for zero threshold - include all data without filtering
    if ($position === 0 || $position === 0.0 || $position === "0") {
      $sqlInsert = "
      INSERT IGNORE INTO {$tempTableName} (contact_id, {$columnName})
      SELECT contrib.contact_id, {$aggregateFunc} AS {$columnName}
      FROM civicrm_contribution contrib
      INNER JOIN civicrm_contact contact ON contrib.contact_id = contact.id
      WHERE contrib.is_test = 0
      AND contrib.total_amount > 0
      AND contrib.contribution_status_id = 1
      AND contact.is_deleted = 0
      {$dateFilterSQL}
      {$recurFilterSQL}
      GROUP BY contrib.contact_id
      ORDER BY {$columnName} {$order}
      ";

      $threshold = 0; // Set threshold to 0 for reporting purposes
    } else {
      // Calculate threshold value for non-zero position
      $threshold = $this->calculateThreshold($position, $metricType, $aggregateFunc, $dateFilterSQL, $recurFilterSQL);

      // Insert contacts meeting criteria
      $comparison = ($metricType === 'r') ?
        ($reverse ? '>=' : '<=') :
        ($reverse ? '<=' : '>=');

      $sqlInsert = "
      INSERT IGNORE INTO {$tempTableName} (contact_id, {$columnName})
      SELECT contrib.contact_id, {$aggregateFunc} AS {$columnName}
      FROM civicrm_contribution contrib
      INNER JOIN civicrm_contact contact ON contrib.contact_id = contact.id
      WHERE contrib.is_test = 0
      AND contrib.total_amount > 0
      AND contrib.contribution_status_id = 1
      AND contact.is_deleted = 0
      {$dateFilterSQL}
      {$recurFilterSQL}
      GROUP BY contrib.contact_id
      HAVING {$columnName} {$comparison} {$threshold}
      ORDER BY {$columnName} {$order}
      ";
    }
    CRM_Core_DAO::executeQuery($sqlInsert);
    
    // Save temporary table name
    $this->_tables[$metricType] = $tempTableName;
    
    return [
      'table' => $tempTableName,
      'threshold' => $threshold,
    ];
  }
  
  /**
   * Calculate threshold value
   * 
   * @param float|int $position Threshold position percentage or specific threshold value
   * @param string $metricType Metric type (r, f, m)
   * @param string $aggregateFunc Aggregate function expression
   * @param string $dateFilterSQL Date filter SQL
   * @param string $recurFilterSQL Recur filter SQL
   * @return float|int Calculated threshold value
   */
  protected function calculateThreshold($position, string $metricType, string $aggregateFunc, string $dateFilterSQL, string $recurFilterSQL = '') {
    if ($position >= 1) {
      return (float) $position;
    }
    
    $sqlMinMax = "
    SELECT MIN({$metricType}Val) AS minVal, MAX({$metricType}Val) AS maxVal
    FROM (
      SELECT contrib.contact_id, {$aggregateFunc} AS {$metricType}Val
      FROM civicrm_contribution contrib
      INNER JOIN civicrm_contact contact ON contrib.contact_id = contact.id
      WHERE contrib.is_test = 0
      AND contrib.total_amount > 0
      AND contrib.contribution_status_id = 1
      AND contact.is_deleted = 0
      {$dateFilterSQL}
      {$recurFilterSQL}
      GROUP BY contrib.contact_id
    ) as subquery
    ";
    
    $result = CRM_Core_DAO::executeQuery($sqlMinMax);
    $result->fetch();
    
    $minVal = ($metricType === 'm') ? (float) $result->minVal : (int) $result->minVal;
    $maxVal = ($metricType === 'm') ? (float) $result->maxVal : (int) $result->maxVal;
    
    return $minVal + (($maxVal - $minVal) * $position);
  }

  /**
   * Get recur filter SQL based on threshold type
   *
   * @param string $thresholdType Threshold type: 'recurring', 'non-recurring', 'all'
   * @return string SQL WHERE clause for recur filtering
   */
  protected function getRecurFilterSQL(string $thresholdType): string {
    switch ($thresholdType) {
      case 'recurring':
        return " AND contrib.contribution_recur_id IS NOT NULL ";
      case 'non-recurring':
        return " AND contrib.contribution_recur_id IS NULL ";
      case 'all':
      default:
        return ""; // No filter
    }
  }
  
  /**
   * Calculate RFM intersection
   * 
   * @return array Array containing temporary table name and record data
   */
  public function calcRFM(): array {
    // Check if R, F, M have been calculated
    if (!$this->_tables['r'] || !$this->_tables['f'] || !$this->_tables['m']) {
      // If not calculated but thresholds are set, calculate automatically
      if ($this->_thresholds['r'] !== null && !$this->_tables['r']) {
        $this->calcR();
      }
      if ($this->_thresholds['f'] !== null && !$this->_tables['f']) {
        $this->calcF();
      }
      if ($this->_thresholds['m'] !== null && !$this->_tables['m']) {
        $this->calcM();
      }
      
      // Check again if all necessary tables have been generated
      if (!$this->_tables['r'] || !$this->_tables['f'] || !$this->_tables['m']) {
        throw new Exception("R, F, M metrics not calculated, cannot calculate RFM intersection");
      }
    }
    
    $tempTableName = "civicrm_temp_rfm_{$this->_suffix}";
    
    // Create temporary table for RFM intersection
    $sqlCreateTempTable = "
    CREATE TEMPORARY TABLE {$tempTableName} (
      contact_id INT(10) UNSIGNED NOT NULL PRIMARY KEY,
      R INT(10) NOT NULL,
      F INT(10) NOT NULL,
      M DECIMAL(20,2) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    CRM_Core_DAO::executeQuery($sqlCreateTempTable);
    
    // Insert RFM intersection data
    $sqlInsert = "
    INSERT INTO {$tempTableName} (contact_id, R, F, M)
    SELECT r.contact_id, r.duration AS R, f.frequency AS F, m.monetary AS M
    FROM {$this->_tables['r']} r
    INNER JOIN {$this->_tables['f']} f ON r.contact_id = f.contact_id
    INNER JOIN {$this->_tables['m']} m ON r.contact_id = m.contact_id;
    ";
    CRM_Core_DAO::executeQuery($sqlInsert);
    
    // Save temporary table name
    $this->_tables['rfm'] = $tempTableName;
    
    // Get data from the temporary table
    $sqlSelectTempTable = "
    SELECT contact_id, R, F, M FROM {$tempTableName};
    ";
    $result = CRM_Core_DAO::executeQuery($sqlSelectTempTable);
    $records = [];
    
    while ($result->fetch()) {
      $records[] = [
        'contact_id' => $result->contact_id,
        'R' => $result->R,
        'F' => $result->F,
        'M' => $result->M
      ];
    }
    
    return [
      'table' => $tempTableName,
      'records' => $records
    ];
  }
  
  /**
   * Export RFM data to CSV
   * 
   * @param string $filename Custom filename (optional)
   * @param  bool $download Bool to indicate print to browser or not
   * @return string Path to the exported CSV file
   */
  public function exportToCSV(string $filename = null, bool $download = TRUE): string {
    if (!$this->_tables['rfm']) {
      $this->calcRFM();
    }
    
    if ($filename === null) {
      // Generate filename based on threshold values
      $rLabel = 'R' . ($this->_reverse['r'] ? 'n' : 'p') . '_' . abs($this->_thresholds['r']);
      $fLabel = 'F' . ($this->_reverse['f'] ? 'n' : 'p') . '_' . abs($this->_thresholds['f']);
      $mLabel = 'M' . ($this->_reverse['m'] ? 'n' : 'p') . '_' . abs($this->_thresholds['m']);
      $filename = 'RFM_' . $rLabel . '_' . $fLabel . '_' . $mLabel . '_'.$this->_suffix.'.csv';
    }
    
    $sqlSelectTempTable = "
    SELECT contact_id, R, F, M FROM {$this->_tables['rfm']};
    ";
    $result = CRM_Core_DAO::executeQuery($sqlSelectTempTable);
    
    $data = [];
    while ($result->fetch()) {
      $fields = [
        $result->contact_id,
        $result->R,
        $result->F,
        $result->M,
      ];
      $data[] = $fields;
    }
    $header = [ts('Contact ID'), ts('Recency'), ts('Frequency'), ts('Monetary')];
    CRM_Core_Report_Excel::writeCSVFile($filename, $header, $data, $download);
    
    if (!$download) {
      return $filename;
    }
    return '';
  }
  
  
  /**
   * Generate date filter SQL
   * 
   * @param string $dateFilter Date filter string
   * @return string Date filter SQL statement
   */
  protected function getDateFilterSQL(string $dateFilter): string {
    $filter = CRM_Utils_Date::strtodate($dateFilter);
    $startDate = $filter['start'];
    $endDate = $filter['end'];
    
    if (isset($startDate) && isset($endDate)) {
      return " AND contrib.receive_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' ";
    }
    return '';
  }
  
  /**
   * Get end date from date filter string
   * 
   * @return string End date string for SQL usage, defaults to current date if no filter
   */
  protected function getEndDate(): string {
    if (!empty($this->_dateString)) {
      $filter = CRM_Utils_Date::strtodate($this->_dateString);
      $endDate = $filter['end'];
      if (isset($endDate)) {
        return $endDate;
      }
    }
    // Default to current date if no date filter
    return date('Y-m-d');
  }
  
  /**
   * Get default thresholds based on date range and threshold type
   * 
   * @param string $rangeString Date range string
   * @param string $thresholdType Threshold type (recurring, non-recurring, all)
   * @return array Array containing r, f, m thresholds
   */
  public static function defaultThresholds(string $rangeString, string $thresholdType): array {
    $filter = CRM_Utils_Date::strtodate($rangeString);
    $totalDays = $filter['day'];
    $totalMonths = $filter['month'];
    $totalYears = $filter['day']/365;
    
    $threshold = [
      'r' => '',
      'f' => '',
      'm' => '',
    ];
    
    switch ($thresholdType) {
      case 'recurring':
        $threshold['r'] = 31;
        $threshold['f'] = max($totalMonths, 2);
        $threshold['m'] = 600 * $totalMonths;
        break;
        
      case 'non-recurring':
        $threshold['r'] = (int) floor($totalDays / 5);
        $threshold['f'] = max($totalYears * 1, 2);
        $threshold['m'] = 600 * $totalMonths;
        break;
        
      case 'all':
      default:
        $threshold['r'] = (int) ceil($totalDays / 5);
        $threshold['f'] = max($totalYears * 1, 2);
        $threshold['m'] = 600 * $totalMonths;
        break;
    }
    
    return $threshold;
  }
}