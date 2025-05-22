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

    $this->_recurringStatus = array(
      2 => ts('All'),
      1 => ts("Recurring Contribution"),
      0 => ts("Non-recurring Contribution"),
    );
  }

  function buildColumn(){
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
  }

  function setDefaultValues() {
    return [
      'rfm_r_value' => $this->_defaultThresholds['recency'],
      'rfm_f_value' => $this->_defaultThresholds['frequency'],
      'rfm_m_value' => $this->_defaultThresholds['monetary']
    ];
  }

  function qill(){
    $qill = array();
    return $qill;
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  function count(){
  }


  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE){
    return $this->sql($fields, $offset, $rowcount, $sort, $includeContactIDs);
  }

  function sql($selectClause, $offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $groupBy = NULL) {
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
    return "FROM {$this->_tableName} contact_a";
  }

  function where($includeContactIDs = false) {
    $sql = '';
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
    $summary = array();
    return $summary;
  }

  function alterRow(&$row) {
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
}