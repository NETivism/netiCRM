<?php

class CRM_Contact_Form_Search_Custom_HalfYearDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  
  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_filled = FALSE;
    $this->_tableName = 'civicrm_temp_custom_halfyeardonor';
    $this->_config = CRM_Core_Config::singleton();
    $this->buildColumn();
  }

  function buildColumn(){
    $this->_queryColumns = array( 
      'contact.id' => 'id',
      'c.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'c.receive_date' => 'receive_date',
      'ROUND(SUM(IF(c.contribution_status_id = 1, c.total_amount, 0)),0)' => 'receive_amount',
      'COUNT(IF(c.contribution_status_id = 1, 1, NULL))' => 'completed_count',
      'COUNT(c.id)' => 'total_count',
    );
    $this->_columns = array(
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Total Receive Amount') => 'receive_amount',
      ts('Completed Donation') => 'completed_count',
      0 => 'total_count',
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
      if($field == 'receive_amount' || $field == 'completed_count' || $field == 'total_count'){
        $type = "INTEGER(10) default NULL";
      }
      elseif(strstr($field, '_date')){
        $type = 'DATETIME NULL default NULL';
      }
      else{
        $type = "VARCHAR(32) default ''";
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
    $month = $this->_formValues['month'];
    $halfyear = date('Y-m-01 00:00:00', strtotime('-'.$month.' month'));
    $clauses = array();
    $clauses[] = "contact.is_deleted = 0 AND pp.id IS NULL AND mp.id IS NULL";
    $clauses[] = "c.receive_date > '$halfyear'";

    return implode(' AND ', $clauses);
  }

  function tempHaving(){
    return '';
  }

  function buildForm(&$form){
    for($i = 1; $i <= 12; $i++) {
      $option[$i] = $i;
    } 
    $form->addSelect('month', ts('month'), $option);
  }

  function setDefaultValues() {
    return array(
      'month' => 6,
    );
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
    $this->_count = $dao->N;
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
    return '(1)';
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
  
  function setTitle(){
    $month = $this->_formValues['month'];
    $title = ts('Donor who donate in last %count month', array('count' => $month, 'plural' => 'Donor who donate in last %count months'));
    CRM_Utils_System::setTitle($title);
  }

  function qill(){
    // just add qill
    $month = $this->_formValues['month'];
    $past = date('Y-m-01', strtotime('-'.$month.' month'));
    return array(
      1 => array(
        'monthrange' => ts('Donor who donate in last %count month', array('count' => $month, 'plural' => 'Donor who donate in last %count months')). ' ( '.$past.' ~ '.ts('Today').')'
      ),
    );
  }

  function alterRow(&$row) {
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/HalfYearDonor.tpl';
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
