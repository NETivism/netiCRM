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
  public function __construct($suffix, $dateString = '', $rThreshold = null, $fThreshold = null, $mThreshold = null, $thresholdType = 'all') {
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
  public function calcR($position = 0.5, $reverse = FALSE) {
    if ($this->_thresholds['r'] !== null) {
      $position = $this->_thresholds['r'];
      $reverse = $this->_reverse['r'];
    }
    
    return $this->calcMetric('r', $position, $reverse, 'duration', 'MIN(DATEDIFF(CURDATE(), DATE(contrib.receive_date)))');
  }
  
  /**
   * Calculate F (Frequency) metric
   * 
   * @param float|int $position Threshold position percentage or specific threshold value
   * @param bool $reverse Whether to use reverse comparison
   * @return array Array containing temporary table name and threshold value
   */
  public function calcF($position = 0.5, $reverse = FALSE) {
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
  public function calcM($position = 0.5, $reverse = FALSE) {
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
  protected function calcMetric($metricType, $position, $reverse, $columnName, $aggregateFunc) {
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
  protected function calculateThreshold($position, $metricType, $aggregateFunc, $dateFilterSQL, $recurFilterSQL = '') {
    if ($position > 1) {
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
  protected function getRecurFilterSQL($thresholdType) {
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
  public function calcRFM() {
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
   * @return string Path to the exported CSV file
   */
  public function exportToCSV($filename = null) {
    if (!$this->_tables['rfm']) {
      $r = $this->calcRFM();
    }
    
    if ($filename === null) {
      // Generate filename based on threshold values
      $rLabel = 'R' . ($this->_reverse['r'] ? 'n' : 'p') . '_' . abs($this->_thresholds['r']);
      $fLabel = 'F' . ($this->_reverse['f'] ? 'n' : 'p') . '_' . abs($this->_thresholds['f']);
      $mLabel = 'M' . ($this->_reverse['m'] ? 'n' : 'p') . '_' . abs($this->_thresholds['m']);
      $filename = __DIR__ . '/RFM_' . $rLabel . '_' . $fLabel . '_' . $mLabel . '_'.$this->_suffix.'.csv';
    }
    
    $sqlSelectTempTable = "
    SELECT contact_id, R, F, M FROM {$this->_tables['rfm']};
    ";
    $result = CRM_Core_DAO::executeQuery($sqlSelectTempTable);
    
    
    // Write data
    while ($result->fetch()) {
      $fields = [
        $result->contact_id,
        $result->R,
        $result->F,
        $result->M,
      ];
      file_put_contents($filename, implode(',' , $fields)."\n", FILE_APPEND);
    }
    
    return $filename;
  }
  
  /**
   * Parse date filter
   * 
   * @param string $dateFilter Date filter string
   * @param bool $returnTotalDays Whether to return total days
   * @return array Array containing start date, end date, total days and total months
   */
  protected function getDateFilter($dateFilter, $returnTotalDays = false) {
    $dateFilterSQL = '';
    $today = new DateTime();
    $startDate = null;
    $endDate = null;
    
    // Parse `last/next N days/weeks/months/years to today`
    if (preg_match('/^(last|next) (\d+) (days|weeks|months|years) to today$/', strtolower($dateFilter), $matches)) {
      $direction = $matches[1]; // last or next
      $amount = (int)$matches[2]; // number
      $unit = $matches[3]; // time unit
      
      if ($direction === 'last') {
        $startDate = (clone $today)->modify("-{$amount} {$unit}")->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
      } else {
        $startDate = $today->format('Y-m-d');
        $endDate = (clone $today)->modify("+{$amount} {$unit}")->format('Y-m-d');
      }
    }
    // Parse `last/next N days/weeks/months/years` (without `to today`)
    elseif (preg_match('/^(last|next) (\d+) (days|weeks|months|years)$/', strtolower($dateFilter), $matches)) {
      $direction = $matches[1];
      $amount = (int)$matches[2];
      $unit = $matches[3];
      
      if ($direction === 'last') {
        $startDate = (clone $today)->modify("-{$amount} {$unit}")->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
      } else {
        $startDate = $today->format('Y-m-d');
        $endDate = (clone $today)->modify("+{$amount} {$unit}")->format('Y-m-d');
      }
    }
    // Parse `this week`, `last month`, `this year`, `last year`
    elseif (preg_match('/^(this|last) (week|month|year)$/', strtolower($dateFilter), $matches)) {
      $modifier = $matches[1]; // this or last
      $unit = $matches[2]; // week, month, year
      
      if ($unit === 'week') {
        if ($modifier === 'this') {
          $startDate = (clone $today)->modify('monday this week')->format('Y-m-d');
          $endDate = $today->format('Y-m-d');
        } else {
          $startDate = (clone $today)->modify('monday last week')->format('Y-m-d');
          $endDate = (clone $today)->modify('sunday last week')->format('Y-m-d');
        }
      } elseif ($unit === 'month') {
        if ($modifier === 'this') {
          $startDate = (clone $today)->modify('first day of this month')->format('Y-m-d');
          $endDate = $today->format('Y-m-d');
        } else {
          $startDate = (clone $today)->modify('first day of last month')->format('Y-m-d');
          $endDate = (clone $today)->modify('last day of last month')->format('Y-m-d');
        }
      } elseif ($unit === 'year') {
        if ($modifier === 'this') {
          $startDate = (clone $today)->modify('first day of January this year')->format('Y-m-d');
          $endDate = $today->format('Y-m-d');
        } else {
          $startDate = (clone $today)->modify('first day of January last year')->format('Y-m-d');
          $endDate = (clone $today)->modify('last day of December last year')->format('Y-m-d');
        }
      }
    }
    // Special fixed values
    elseif ($dateFilter === 'today') {
      $startDate = $today->format('Y-m-d');
      $endDate = $today->format('Y-m-d');
    } elseif ($dateFilter === 'yesterday') {
      $startDate = (clone $today)->modify('-1 day')->format('Y-m-d');
      $endDate = $startDate;
    }
    // Parse single day yyyy-mm-dd
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
      $startDate = $dateFilter;
      $endDate = $dateFilter;
    }
    // Parse range yyyy-mm-dd_to_yyyy-mm-dd
    elseif (preg_match('/^\d{4}-\d{2}-\d{2}_to_\d{4}-\d{2}-\d{2}$/', $dateFilter)) {
      list($startDate, $endDate) = explode('_to_', $dateFilter);
    }
    
    $startDateTime = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    $interval = $startDateTime->diff($endDateTime);
    $totalDays = $interval->days + 1;
    $totalMonths = ($interval->y * 12) + $interval->m;
    if ($interval->d > 0) {
      $totalMonths++;
    }
    
    return [
      'start' => $startDate,
      'end' => $endDate,
      'day' => $totalDays,
      'month' => $totalMonths,
    ];
  }
  
  /**
   * Generate date filter SQL
   * 
   * @param string $dateFilter Date filter string
   * @return string Date filter SQL statement
   */
  protected function getDateFilterSQL($dateFilter) {
    $filter = $this->getDateFilter($dateFilter);
    $startDate = $filter['start'];
    $endDate = $filter['end'];
    
    if (isset($startDate) && isset($endDate)) {
      return " AND contrib.receive_date BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59' ";
    }
    return '';
  }
  
  /**
   * Get default thresholds based on date range and threshold type
   * 
   * @param string $rangeString Date range string
   * @param string $thresholdType Threshold type (recurring, non-recurring, all)
   * @return array Array containing r, f, m thresholds
   */
  public static function defaultThreshold($rangeString, $thresholdType) {
    $suffix = '';
    $rfm = new self($suffix);
    $filter = $rfm->getDateFilter($rangeString, true);
    $totalDays = $filter['day'];
    $totalMonths = $filter['month'];
    $totalYears = ceil($filter['day']/365);
    
    $threshold = [
      'r' => '',
      'f' => '',
      'm' => '',
    ];
    
    switch ($thresholdType) {
      case 'recurring':
        $threshold['r'] = 31;
        $threshold['f'] = (int) ceil($totalMonths/2);
        $threshold['m'] = 600 * $totalMonths;
        break;
        
      case 'non-recurring':
        $threshold['r'] = 180;
        $threshold['f'] = max(1, $totalYears) + 1;
        $threshold['m'] = 10000 * $totalYears;
        break;
        
      case 'all':
      default:
        $threshold['r'] = (int) ceil($totalDays / 5);
        $threshold['f'] = max(1, $totalYears) + 1;
        $threshold['m'] = 600 * $totalMonths;
        break;
    }
    
    return $threshold;
  }
}