<?php

class CRM_Contact_Form_Search_Custom_FirstTimeDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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
      $this->_tableName = !empty($formValues['temp_table']) ? $formValues['temp_table'] : "civicrm_temp_custom_{$randomNum}";
      $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
    }
  }

  function buildColumn(){
    $this->_queryColumns = array( 
      'contact.id' => 'id',
      'c.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'c.receive_date' => 'receive_date',
      'ROUND(c.total_amount,0)' => 'amount',
      'c.contribution_recur_id' => 'contribution_recur_id',
      'c.contribution_status_id' => 'contribution_status_id',
      'c.contribution_page_id' => 'contribution_page_id',
    );
    $this->_columns = array(
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Amount') => 'amount',
      ts('Receive Date') => 'receive_date',
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
    return "civicrm_contact AS contact INNER JOIN civicrm_contribution c ON c.contact_id = contact.id AND c.is_test = 0 LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = c.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = c.id";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    $clauses = array();
    $clauses[] = "contact.is_deleted = 0 AND pp.id IS NULL AND mp.id IS NULL";

    return implode(' AND ', $clauses);
  }

  function tempHaving(){
    $clauses = array();
    $clauses[] = "COUNT(c.id) = 1";
    return implode(' AND ', $clauses);
  }

  function buildForm(&$form){
    // Define the search form fields here
    
    $form->addDateRange('receive_date', ts('First time donation donors').' - '.ts('From'), NULL, FALSE);
    $statuses = $this->_cstatus;
    unset($statuses[6]);
    unset($statuses[4]);
    ksort($statuses);
    $statuses = array_flip($statuses);
    $form->addCheckBox('status', ts('Contribution Status'), $statuses, NULL, NULL, NULL, NULL, '&nbsp;');

    $recurring = array(
      2 => ts('All'),
      1 => ts("Recurring Contribution"),
      0 => ts("Non-recurring Contribution"),
    );
    $form->addRadio('recurring', ts('Recurring Contribution'), $recurring);
    $form->addSelect('contribution_page_id', ts('Contribution Page'), array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::contributionPage());
    $form->add('hidden', 'temp_table', $this->_tableName);
    $defaults = array(
      'receive_date_from' => date('Y-m-d', time() - 86400*90),
      'recurring' => 2,
      'status[1]' => 1,
    );
    $form->setDefaults($defaults);

    $form->assign('elements', array('receive_date', 'status', 'recurring', 'contribution_page_id'));
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
    return 'CRM/Contact/Form/Search/Custom/FirstTimeDonor.tpl';
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
