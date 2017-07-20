<?php

class CRM_Contact_Form_Search_Custom_FailedNoFurtherDonate extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  
  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_filled = FALSE;
    if(empty($this->_tableName)){
      $randomNum = CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
      $this->_tableName = 'civicrm_custom_search_failednofurtherdonate';
      $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
    }
  }

  function buildColumn(){
    $this->_queryColumns = array( 
      'contact.id' => 'id',
      'contact.sort_name' => 'sort_name',
      'failed.created_date' => 'created_date_failed',
      'success.created_date' => 'created_date_success',
      'failed.total_amount' => 'total_amount_failed',
      'success.total_amount' => 'total_amount_success',
    );
    $this->_columns = array(
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Created Date') => 'created_date_failed',
      ts('Amount') . ' - (' . ts("Failed") . ')' => 'total_amount_failed',
    );
  }
  function buildTempTable() {
    $sql = "
CREATE TEMPORARY TABLE IF NOT EXISTS {$this->_tableName} (
  id int unsigned NOT NULL,
";

    foreach ($this->_queryColumns as $field) {
      if (in_array($field, array('id'))) {
        continue;
      }
      if($field == 'amount'){
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
) ENGINE=HEAP DEFAULT CHARSET=utf8
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
    $select = array();
    foreach($this->_queryColumns as $k => $v){
      $select[] = $k.' as '.$v;
    }
    $select = implode(", \n" , $select);
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
      $values = array();
      foreach($this->_queryColumns as $name){
        if($name == 'id'){
          $values[] = $dao->id;
        }
        elseif(isset($dao->$name)){
          $values[] = "'". $dao->$name."'";
        }
        else{
          $values[] = 'NULL';
        }
      }
      $values = implode(', ' , $values);
      $sql = "REPLACE INTO {$this->_tableName} VALUES ($values)";
      CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    }
  }


  function tempFrom() {
    return "civicrm_contact AS contact INNER JOIN 
 (SELECT ca.* FROM civicrm_contribution ca LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = ca.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ca.id WHERE ca.is_test = 0 AND ca.contribution_status_id = 4 AND pp.id IS NULL AND mp.id IS NULL ORDER BY ca.created_date DESC) failed ON failed.contact_id = contact.id
   LEFT JOIN 
 (SELECT cb.* FROM civicrm_contribution cb LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = cb.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = cb.id WHERE cb.is_test = 0 AND cb.contribution_status_id = 1 AND pp.id IS NULL AND mp.id IS NULL ORDER BY cb.created_date DESC) success ON success.contact_id = contact.id
";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    $days = $this->_formValues['days'] ? $this->_formValues['days'] : 7;
    $clauses = array();
    $clauses[] = "contact.is_deleted = 0";
    $clauses[] = "(success.created_date IS NULL OR success.created_date > date_add(failed.created_date, INTERVAL $days DAY) OR success.created_date <= failed.created_date)";

    return implode(' AND ', $clauses);
  }

  function tempHaving(){
    return '';
  }

  function buildForm(&$form){
    for($i = 2; $i <= 15; $i++) {
      $option[$i] = $i;
    } 
    $form->addSelect('days', ts('days'), $option);
  }

  function setDefaultValues() {
    return array(
      'days' => 7,
    );
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  function setTitle() {
    $days = $this->_formValues['days']; 
    CRM_Utils_System::setTitle(ts('After payment failed but not retry in %1 days', array(1 => $days)));
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
  /*
    $receive_date_from = CRM_Utils_Array::value('receive_date_from', $this->_formValues);
    $receive_date_to = CRM_Utils_Array::value('receive_date_to', $this->_formValues);
    if ($receive_date_from) {
      $clauses[] = "receive_date >= $receive_date_from";
    }
    if ($receive_date_to) {
      $clauses[] = "receive_date <= $receive_date_to";
    }

    $status = CRM_Utils_Array::value('status', $this->_formValues);
    if (is_array($status)) {
      $status = array_keys($status);
      $clauses[] = "contribution_status_id IN (".implode(',', $status).")";
    }

    $recurring = CRM_Utils_Array::value('recurring', $this->_formValues);
    if ($recurring != 2) {
      if ($recurring) {
        $clauses[] = "contribution_recur_id > 0";
      }
      else {
        $clauses[] = "NULLIF(contribution_recur_id, 0) IS NULL";
      }
    }

    $page_id = CRM_Utils_Array::value('contribution_page_id', $this->_formValues);
    if ($page_id) {
      $clauses[] = "contribution_page_id = $page_id";
    }
    if (count($clauses)) {
      $sql = '('.implode(' AND ', $clauses).')';
    }
    else {
      $sql = '(1)';
    }
    if ($includeContactIDs) {
      $this->includeContactIDs($sql, $this->_formValues);
    }
    return $sql;
    */
    return ' (1) ';
  }

  function having(){
    return '';
  }

  static function includeContactIDs(&$sql, &$formValues) {
    $contactIDs = array();
    foreach ($formValues as $id => $value) {
      if ($value &&
        substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
      ) {
        $contactIDs[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
      }
    }

    if (!empty($contactIDs)) {
      $contactIDs = implode(', ', $contactIDs);
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
    return 'CRM/Contact/Form/Search/Custom/FailedNoFurtherDonate.tpl';
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
