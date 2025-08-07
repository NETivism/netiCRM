<?php

class CRM_Contact_Form_Search_Custom_AttendeeNotDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public $_queryColumns;
  protected $_formValues;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;

  protected $_participantStatuses = [
    'Registered',
    'Attended'
  ];
  
  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_filled = FALSE;
    if(empty($this->_tableName)){
      $this->_tableName = 'civicrm_custom_search_singlenotrecurring';
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
    }
  }

  function buildColumn(){
    $this->_queryColumns = [ 
      'contact.id' => 'id',
      'p.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'COUNT(p.id)' => 'register_count',
    ];
    $this->_columns = [
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('register count') => 'register_count',
    ];
  }
  function buildTempTable() {
    $sql = "
CREATE TEMPORARY TABLE IF NOT EXISTS {$this->_tableName} (
  id int unsigned NOT NULL,
";

    foreach ($this->_queryColumns as $field) {
      if (in_array($field, ['id'])) {
        continue;
      }
      if(strstr($field, '_id') || strstr($field, 'count')){
        $type = "INTEGER(10) default NULL";
      }
      else{
        $type = "VARCHAR(32) default ''";
      }
      if(strstr($field, '_date')){
        $type = 'DATETIME NULL default NULL';
      }
      $sql .= "{$field} {$type},\n";
    }

    $sql .= "
PRIMARY KEY (id)
) ENGINE=HEAP DEFAULT CHARSET=utf8mb4
";
    CRM_Core_DAO::executeQuery($sql);
  }
  function dropTempTable() {
    $sql = "DROP TEMPORARY TABLE IF EXISTS `{$this->_tableName}`" ;
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * fill temp table for further use
   */
  function fillTable(){
    $this->buildTempTable();
    $select = [];
    foreach($this->_queryColumns as $k => $v){
      $select[] = $k.' as '.$v;
    }
    $select = CRM_Utils_Array::implode(", \n" , $select);
    $from = $this->tempFrom();
    $where = $this->tempWhere();
    $having = $this->tempHaving();
    if ($having) {
      $having = " HAVING $having ";
    }

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY contact.id
$having
";

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    while ($dao->fetch()) {
      $values = [];
      foreach($this->_queryColumns as $name){
        if($name == 'id'){
          $values[] = CRM_Utils_Type::escape($dao->id, 'Integer');
        }
        elseif(isset($dao->$name)){
          $values[] = "'". CRM_Utils_Type::escape($dao->$name, 'String')."'";
        }
        else{
          $values[] = 'NULL';
        }
      }
      $values = CRM_Utils_Array::implode(', ' , $values);
      $sql = "REPLACE INTO {$this->_tableName} VALUES ($values)";
      CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    }
  }


  function tempFrom() {
    $attendeeStatus = CRM_Event_PseudoConstant::participantStatus(NULL);
    $statuses = array_intersect($attendeeStatus, $this->_participantStatuses);
    
    $attendeeStatusId = array_keys($statuses);
    $from = "civicrm_contact AS contact INNER JOIN civicrm_participant p ON p.contact_id = contact.id AND p.is_test = 0 AND p.status_id IN (".CRM_Utils_Array::implode(',', $attendeeStatusId).") 
    LEFT JOIN (SELECT cc.* FROM civicrm_contribution cc LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = cc.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = cc.id WHERE cc.is_test = 0 AND cc.contribution_status_id = 1 AND pp.id IS NULL AND mp.id IS NULL ORDER BY cc.created_date DESC) c ON c.contact_id = contact.id
    ";
    return $from;
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    $from = !empty($this->_formValues['register_date_from']) ? $this->_formValues['register_date_from'] : NULL;
    $to = !empty($this->_formValues['register_date_to']) ? $this->_formValues['register_date_to'] : NULL;
    $clauses = [];
    $clauses[] = "contact.is_deleted = 0";
    $clauses[] = "c.id IS NULL";
    if ($from) {
      $clauses[] = "p.register_date >= '$from'";
    }
    if ($to) {
      $clauses[] = "p.register_date <= '$to'";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function tempHaving(){
    $attended = $this->_formValues['attended'];
    $clauses = [];
    $clauses[] = 'COUNT(p.id) >= '.$attended;
    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function buildForm(&$form){
    for ($i = 1; $i <= 5; $i++) {
      $option[$i] = $i;
    }
    $attendeeStatus = array_map('ts', $this->_participantStatuses);
    $form->addDateRange('register_date', ts('Register Date').' - '.ts('From'), NULL, FALSE);
    $form->addSelect('attended', CRM_Utils_Array::implode(', ', $attendeeStatus), $option);
  }

  function setDefaultValues() {
    return [
      'attended' => 1,
    ];
  }

  function qill(){
    $qill = [];

    $attendeeStatus = array_map('ts', $this->_participantStatuses);
    $qill[1][] = ts('Participant Statuses').': '.CRM_Utils_Array::implode(', ', $attendeeStatus);

    $from = !empty($this->_formValues['register_date_from']) ? $this->_formValues['register_date_from'] : NULL;
    $to = !empty($this->_formValues['register_date_to']) ? $this->_formValues['register_date_to'] : NULL;
    if ($from || $to) {
      $to = empty($to) ? ts('Today') : $to;
      $from = empty($from) ? ' ... ' : $from;
      $qill[1][] = ts("Register Date").': '. $from . '~' . $to;
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
    $dao = CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
    return $dao->N;
  }


  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE){
    $fields = !$onlyIDs ? "*" : "contact_a.contact_id" ;

    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
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
    return ' (1) ';
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
    // return $summary;
  }

  function alterRow(&$row) {
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/AttendeeNotDonor.tpl';
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
