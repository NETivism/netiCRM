<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.0                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have receive a copy of the GNU Affero General Public    |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

class CRM_Contact_Form_Search_Custom_RecurSearch  extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public $_mode;
  public $_queryColumns;
  public $_isExport;
  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_gender = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  protected $_context = NULL;
  protected $_cpage = NULL;

  public static $_primaryIDName = 'id';
  
  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_filled = FALSE;
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $form);
    if(empty($this->_tableName)){
      $this->_tableName = "civicrm_temp_custom_recursearch";
      $this->_cpage = CRM_Contribute_PseudoConstant::contributionPage();
      $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_cstatus[1] = ts('Recurring ended');
      $this->_gender = CRM_Core_PseudoConstant::gender();
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
    }

    $lowDate = CRM_Utils_Request::retrieve('start', 'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($lowDate) {
      $lowDate = CRM_Utils_Type::escape($lowDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($lowDate);
      $this->_formValues['start_date_from'] = $date[0];
    }

    $highDate = CRM_Utils_Request::retrieve('end', 'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($highDate) {
      $highDate = CRM_Utils_Type::escape($highDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($highDate);
      $this->_formValues['start_date_to'] = $date[0];
    }
    $cstatus_id = CRM_Utils_Request::retrieve('status', 'Int', CRM_Core_DAO::$_nullObject);
    if (!empty($cstatus_id)){
      $this->_formValues['status'] = $cstatus_id;
    }
  }

  function buildColumn(){
    $this->_queryColumns = [ 
      'r.id' => 'id',
      'contact.sort_name' => 'sort_name',
      'r.contact_id' => 'contact_id',
      'contact_email.email' => 'email',
      'ROUND(r.amount,0)' => 'amount',
      'COUNT(IF(c.contribution_status_id = 1, 1, NULL))' => 'completed_count',
      'CAST(r.installments AS SIGNED) - COUNT(IF(c.contribution_status_id = 1, 1, NULL))' => 'remain_installments',
      'r.installments' => 'installments',
      'r.start_date' => 'start_date',
      'r.end_date' => 'end_date',
      'r.cancel_date' => 'cancel_date',
      'COUNT(c.id)' => 'total_count',
      'ROUND(SUM(IF(c.contribution_status_id = 1, c.total_amount, 0)),0)' => 'receive_amount',
      'MAX(c.created_date)' => 'current_created_date',
      'r.contribution_status_id' => 'contribution_status_id',
      'lrd.last_receive_date' => 'last_receive_date',
      'lfd.last_failed_date' => 'last_failed_date',
      'c.contribution_page_id' => 'contribution_page_id',
    ];
    $this->_columns = [
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Amount') => 'amount',
      ts('Remain Installments') => 'remain_installments',
      ts('Processed Installments').' /<br>'.ts('Total Installments') => 'installments',
      ts('Start Date') => 'start_date',
      ts('End Date') => 'end_date',
      ts('Cancel Date') => 'cancel_date',
      ts('Recurring Status') => 'contribution_status_id',
      ts('Completed Donation').'/<br>'.ts('Total Count') => 'completed_count',
      ts('Total Receive Amount') => 'receive_amount',
      ts('Most Recent').' '.ts('Created Date') => 'current_created_date',
      ts('Last Receive Date') => 'last_receive_date',
      ts('Last Failed Date') => 'last_failed_date',
      ts('Contribution Page ID') => 'contribution_page_id',
      0 => 'total_count',
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
      if ($field == 'remain_installments' || strstr($field, 'amount') || strstr($field, '_id')) {
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
    $this->dropTempTable();
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
GROUP BY r.id
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
    return "civicrm_contribution_recur AS r 
    INNER JOIN civicrm_contribution AS c ON c.contribution_recur_id = r.id
    INNER JOIN civicrm_contact AS contact ON contact.id = r.contact_id
    LEFT JOIN (SELECT contact_id, email, is_primary FROM civicrm_email WHERE is_primary = 1 GROUP BY contact_id ) AS contact_email ON contact_email.contact_id = r.contact_id
    LEFT JOIN (SELECT contribution_recur_id AS rid, MAX(receive_date) AS last_receive_date FROM civicrm_contribution WHERE contribution_status_id = 1 AND contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) lrd ON lrd.rid = r.id
    LEFT JOIN (SELECT contribution_recur_id AS rid, MAX(cancel_date) AS last_failed_date FROM civicrm_contribution WHERE contribution_status_id = 4 AND contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) lfd ON lfd.rid = r.id";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    $clauses = [];
    $clauses[] = "(r.contact_id = contact.id)";
    $clauses[] = "(r.is_test = 0)";

    $startDateFrom = CRM_Utils_Date::processDate($this->_formValues['start_date_from']);
    if ($startDateFrom) {
      $clauses[] = "(r.start_date >= '$startDateFrom')";
    }
    $startDateTo = CRM_Utils_Date::processDate($this->_formValues['start_date_to'].' 23:59:59');
    if ($startDateTo) {
      $clauses[] = "(r.start_date <= '$startDateTo')";
    }

    if ($this->_formValues['status'] && is_numeric($this->_formValues['status'])) {
      $clauses[] = "(r.contribution_status_id = {$this->_formValues['status']})";
    }

    $sort_name = $this->_formValues['sort_name'];
    if($sort_name){
      $clauses[] = "(`sort_name` LIKE '%$sort_name%')";
    }

    $email = $this->_formValues['email'];
    if($email){
      $clauses[] = "(`email` LIKE '%$email%')";
    }
    $installments = $this->_formValues['installments'];
    if ($installments === 'none') {
      $clauses[] = "(r.installments IS NULL OR r.installments = 0)";
    }

    $contributionPage = $this->_formValues['contribution_page_id'];
    if (!empty($contributionPage)) {
      $clauses[] = "c.contribution_page_id IN (".CRM_Utils_Array::implode(",", $contributionPage).")";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function tempHaving(){
    $clauses = [];
    $installments = $this->_formValues['installments'];
    if (is_numeric($installments) && $installments != 'none') {
      $installments = (int) $installments;
      if ($installments == 0) {
        $clauses[] = "(remain_installments <= 0)";
      }
      else {
        $clauses[] = "(remain_installments = $installments)";
      }

    }
    if(count($clauses)){
      return CRM_Utils_Array::implode(' AND ', $clauses);
    }
    return '';
  }

  function prepareForm(&$form) {
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $form);
  }

  function buildForm(&$form){
    // Define the search form fields here
    if (!empty($this->_mode)) {
      $form->set('mode', $this->_mode);
      $form->assign('mode', $this->_mode);
    }
    
    if ($this->_mode != 'booster') {
      $form->addDateRange('start_date', ts('First recurring date'), NULL, FALSE);
      $form->addElement('text', 'sort_name', ts('Contact Name'));
      $form->addElement('text', 'email', ts('Email'));
    }
    else {
      $defaults = $this->setDefaultValues();
      $form->setDefaults($defaults);
    }
    
    $status = $this->_cstatus;
    foreach([5,2,3,6,7,1] as $key) {
      $statuses[$key] = $status[$key];
    }
    $form->addRadio('status', ts('Recurring Status'), $statuses, ['allowClear' => TRUE]);

    $installments = [
      '' => ts('- select -'),
      'none' => ts('no installments specified'),
      '0' => ts('Installments is full.'),
    ];
    for ($i = 1; $i <= 6; $i++) {
      $installments[$i] = ts('%1 installments left', [1 => $i]);
    }
    $form->addElement('select', 'installments', ts('Installments Left'), $installments);

    $contributionPage = $this->_cpage;
    $attrs = ['multiple' => 'multiple'];
    $form->addElement('select', 'contribution_page_id', ts('Contribution Page'), $contributionPage, $attrs);

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', ['status', 'installments', 'sort_name', 'email', 'contribution_page_id']);
  }

  function setDefaultValues() {
    if ($this->_mode == 'booster') {
      return [
        'status' => 5,
        'installments' => '1',
      ];
    }
    return [];
  }

  function setTitle() {
    if ($this->_mode == 'booster') {
      CRM_Utils_System::setTitle(ts('End of recurring contribution'));
    }
    else {
      CRM_Utils_System::setTitle(ts('Custom Search').' - '.ts('Recurring Contribution'));
    }
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  function count(){
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $value = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM {$this->_tableName}");
    return $value;
  }


  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }

  /**
   * This will call by search tasks
   * Which not only provide contact id, but also provide additional id
   * Mostly used by custom search support multiple record of one contact
   */
  function contactAdditionalIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    $fields = "contact_a.contact_id, id" ;

    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    return $this->sql($fields, $offset, $rowcount, $sort, FALSE);
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
    $sql = ' ( 1 ) ';
    if ($includeContactIDs) {
      self::includeContactIDs($sql, $this->_formValues, $this->_isExport);
    }
    return $sql;
  }

  function having(){
    return '';
  }

  public static function includeContactIDs(&$sql, &$formValues, $isExport = FALSE) {
    $contactIDs = [];
    foreach ($formValues as $id => $value) {
      list($contactID, $additionalID) = CRM_Core_Form::cbExtract($id);
      if ($isExport) {
        if ($value && !empty($additionalID)) {
          $contactIDs[] = $additionalID;
        }
      }
      elseif ($value && !empty($contactID)) {
        $contactIDs[] = $contactID;
      }
    }

    if (!empty($contactIDs)) {
      $contactIDs = CRM_Utils_Array::implode(', ', $contactIDs);
      if ($isExport) {
        $sql .= " AND contact_a.id IN ( $contactIDs )";
      }
      else {
        $sql .= " AND contact_a.contact_id IN ( $contactIDs )";
      }
    }
  }

  function &columns(){
    return $this->_columns;
  }
  
  function summary(){
    $summary = [];
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $count = $this->count();

    $summary['search_results'] = [
      'label' => ts('Search Results'),
      'value' => ts('There are %1 recurring contributions.', [1 => $count]),
    ];
    $query = CRM_Core_DAO::executeQuery("SELECT SUM(receive_amount) as amount FROM {$this->_tableName} WHERE 1");
    $query->fetch();
    
    if ($query->amount) {
      $amount = CRM_Utils_Money::format($query->amount);
      $summary['search_results']['value'] .= ' '.ts('Total amount of completed contributions is %1.', [1 => $amount]);
    }

    return $summary;
  }

  function alterRow(&$row) {
    $row['contribution_status_id'] = $this->_cstatus[$row['contribution_status_id']];
    $processedInstallments = $row['installments'] - $row['remain_installments'];
    if(empty($row['installments'])){
      $row['remain_installments'] = ts('no limit');
      $row['installments'] = $row['completed_count'].' / '.ts('no limit');
    }
    else {
      $row['installments'] = $processedInstallments.' / '.$row['installments'];
    }
    if($row['remain_installments'] < 0){
       $row['remain_installments'] = ts('Over %1',[ 1 => -$row['remain_installments']]);
    }
    
    if ($row['completed_count']) {
      $row['completed_count'] = $row['completed_count'].' / '.$row['total_count'];
    }
    else {
      $row['completed_count'] = '0 / '.$row['total_count'];
    }
    unset($row['total_count']);

    if ($row['contribution_page_id'] && empty($this->_isExport)) {
      $params = [
        'p' => 'civicrm/admin/contribute',
        'q' => "action=update&reset=1&id={$row['contribution_page_id']}",
      ];
      $row['contribution_page_id'] = '<a href="'.CRM_Utils_System::crmURL($params).'" title="'. $this->_cpage[$row['contribution_page_id']].'">'. $row['contribution_page_id'].'</a>';
    }

    $date = ['start_date', 'end_date', 'cancel_date'];
    foreach($date as $d){
      if(!empty($row[$d])){
        $row[$d] = CRM_Utils_Date::customFormat($row[$d], $this->_config->dateformatFull);
      }
    }

    $action = array_sum(array_keys(CRM_Contribute_Page_Tab::recurLinks()));
    $row['action'] = CRM_Core_Action::formLink(CRM_Contribute_Page_Tab::recurLinks(), $action,
      ['cid' => $row['contact_id'],
        'id' => $row['id'],
        'cxt' => 'contribution',
      ]
    );
    // Refs #38855, Workaround for export error when there are NULL field.
    foreach ($row as $key => $value) {
      if ($value === NULL) {
        $row[$key] = '';
      }
    }
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/RecurSearch.tpl';
  }
}

