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
 | You should have receive a copy of the GNU Affero General Public   |
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

  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_gender = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  
  function __construct(&$formValues){
    parent::__construct($formValues);
    if(empty($this->_tableName)){
      $randomNum = substr(md5($this->_formValues['qfKey']), 0, 8);
      $this->_tableName = "civicrm_temp_custom_{$randomNum}";
      $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_gender = CRM_Core_PseudoConstant::gender();
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
      $this->buildTempTable();
    }
  }

  function buildColumn(){
    $filter_month = !empty($this->_formValues['contribution_created_date']) ? CRM_Utils_Date::customFormat($this->_formValues['contribution_created_date'], $this->_config->dateformatPartial).' ' : ts('Most Recent').' ';
    $this->_queryColumns = array( 
      'r.id' => 'id',
      'contact_a.sort_name' => 'sort_name',
      'contact_a.birth_date' => 'birth_date',
      'contact_a.gender_id' => 'gender_id',
      'r.contact_id' => 'contact_id',
      'r.amount' => 'amount',
      'r.frequency_unit' => 'frequency_unit',
      'r.installments' => 'installments',
      'r.start_date' => 'start_date',
      'r.end_date' => 'end_date',
      'r.cancel_date' => 'cancel_date',
      'c.contribution_status_id' => 'last_status_id',
      'MAX(c.receive_date)' => 'current_receive_date',
      'COUNT(IF(c.contribution_status_id = 1, 1, NULL))' => 'donation_count',
      'COUNT(c.id)' => 'total_count',
      'SUM(c.total_amount)' => 'total_amount', 
      'r.contribution_status_id' => 'contribution_status_id',
    );
    $this->_columns = array(
      ts('ID') => 'id',
      ts('Amount') => 'amount',
      ts('Name') => 'sort_name',
      ts('Gender') => 'gender_id',
      ts('Birth Date') => 'birth_date',
      ts('Frequency Unit') => 'frequency_unit',
      ts('Installments') => 'installments',
      ts('Start Date') => 'start_date',
      ts('End Date') => 'end_date',
      ts('Cancel Date') => 'cancel_date',
      ts('Completed Donation') => 'donation_count',
      ts('Total Count') => 'total_count',
      ts('Total') => 'total_amount',
      $filter_month. ts('Contribution Status') => 'last_status_id',
      $filter_month. ts('Created Date') => 'current_receive_date',
      ts('Last receive Date') => 'last_receive_date',
      ts('Status') => 'contribution_status_id',
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
      $type = "VARCHAR(32) default ''";
      if(strstr($field, '_date')){
        $type = 'DATETIME NULL default NULL';
      }
      $sql .= "{$field} {$type},\n";
    }

    $sql .= "
PRIMARY KEY (id)
) ENGINE=HEAP DEFAULT CHARSET=utf8
";
    CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
  }

  function fillTable(){
    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    $select = array();
    foreach($this->_queryColumns as $k => $v){
      $select[] = $k.' as '.$v;
    }
    $select = implode(", \n" , $select);
    $from = $this->_from();
    $where = $this->_where();

    $having = $this->_having();
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
    // for only contact ids ignore order.
    $sql .= " ORDER BY r.id ASC";
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
      $values = implode(',' , $values);
      $sql = "REPLACE INTO {$this->_tableName} VALUES ($values)";
      CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    }
  }


  function _from() {
    return "civicrm_contribution_recur AS r 
    INNER JOIN civicrm_contribution AS c ON c.contribution_recur_id = r.id
    INNER JOIN civicrm_contact AS contact_a ON contact_a.id = r.contact_id";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function _where(){
    $clauses = array();

    $clauses[] = "r.contact_id = contact_a.id";
    $clauses[] = "r.is_test = 0";

    $startDate = CRM_Utils_Date::processDate($this->_formValues['start_date']);
    if ($startDate) {
      $clauses[] = "r.start_date >= $startDate";
    }
    $createDate = CRM_Utils_Date::processDate($this->_formValues['contribution_created_date']);
    if(!empty($createDate)){
      $clauses[] = "c.created_date >= ".$createDate;
      $next_month = strtotime('+1 month', strtotime($createDate));
      $next_str = date('Y-m-d', $next_month);
      $next_str = CRM_Utils_Date::processDate($next_str);
      $clauses[] = "c.created_date < ".$next_str;
    }

    

    if ($includeRecurIds) {
      $recurIds = array();
      foreach ($this->_formValues as $id => $value) {
        if ($value && substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $recurIds[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($recurIds)) {
        $recurIds = implode(', ', $recurIds);
        $clauses[] = "r.id IN ($recurIds)";
      }
    }
    

    return implode(' AND ', $clauses);
  }

  function _having(){
    $clauses = array();
    if(count($clauses)){
      return implode(' AND ', $clauses);
    }
    return '';
  }

  function buildForm(&$form){
    CRM_Core_OptionValue::getValues(array('name' => 'custom_search'), $custom_search);
    $csid = !empty($form->_formValues['customSearchID']) ? $form->_formValues['customSearchID'] : (!empty($_GET['csid']) ? $_GET['csid'] : NULL);
    if($csid){
      foreach ($custom_search as $c) {
        if ($c['value'] == $csid) {
          $this->setTitle($c['description']);
          break;
        }
      }
    }

    // Define the search form fields here
    
    $form->addDate('start_date', ts('First recurring date'), FALSE, array('formatType' => 'custom'));
    $form->addDate('contribution_created_date', ts('Filter by month'), FALSE, array('formatType' => 'custom', 'format' => 'yy-mm'));
    $options = array(
      'second_times' => '兩期以上有效',
      'last_time' => '餘一期有效',
      'is_expired' => '已過期',
      'is_failed' => '已失敗',
      );
    $form->addRadio('other_options',NULL,$options,NULL,"<br/>" );

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', array('start_date', 'contribution_created_date','other_options'));
  }

  function count(){
    if(!empty($this->_formValues['qfKey']) && !$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $sql = $this->sql('count(*)');
    $value = CRM_Core_DAO::singleValueQuery($sql, CRM_Core_DAO::$_nullArray);
    return $value;
  }


  /**
   * Construct the search query
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeRecurIds= FALSE, $onlyIDs = FALSE){
    $task = $this->_formValues['task'] ? $this->_formValues['task'] : FALSE;
    if($task && !empty($this->_formValues['qfKey']) && !$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $sql = ($this->sql('*',
      $offset, $rowcount, $sort,
      FALSE, NULL
    ));
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $this->sql('*',
      $offset, $rowcount, $sort,
      FALSE, NULL
    );
  }

  /**
   * Functions below generally don't need to be modified
   */
  function from() {
    return "FROM {$this->_tableName} contact_a";
  }

  function where($includeRecurIDs = FALSE) {
    $clauses = array();

    if ($includeRecurIDs) {
      $recurIds = array();
      foreach ($this->_formValues as $id => $value) {
        if ($value && substr($id, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX
        ) {
          $recurIds[] = substr($id, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }

      if (!empty($recurIds)) {
        $recurIds = implode(', ', $recurIds);
        $clauses[] = "id IN ($recurIds)";
      }
    }

    $other_options = $this->_formValues['other_options'];
    $or_clauses = array();

    $year = date('Y');
    $month = date('m');
    $day = date('d');
    $today = $year."-".$month."-".$day;
    if($month == 12){
      $year++;
      $month = 1;
    }else{
      $month++;
    }
    $month_later = $year."-".$month."-".$day;
    switch($other_options){
      case 'second_times':
        $clauses[] = "(`end_date` > '$month_later' AND contribution_status_id = 5)";
        break;
      case 'last_time':
        $clauses[] = "(`end_date` > '$today' AND `end_date` < '$month_later'  AND contribution_status_id = 5)";
        break;
      case 'is_expired':
        $clauses[] = " ( contribution_status_id = 1 ) ";
        break;
      case 'is_failed':
        $clauses[] = "  contribution_status_id = 4 OR  contribution_status_id = 3";
        break;
    }


    if(!empty($clauses)){
      return implode(' AND ', $clauses);
    }
    else{
      return ' ( 1 ) ';
    }
  }

  function having(){
    $clauses = array();



    if(count($clauses)){
      return implode(' AND ', $clauses);
    }
    return '';
  }

  function &columns(){
    return $this->_columns;
  }
  
  function setTitle($title){
    if ($title) {
      CRM_Utils_System::setTitle($title);
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  function summary(){
  /*
    $select = "
r.id,
COUNT(DISTINCT(id)) as recurring_count,
SUM(total_count) as total_count,
SUM(total_amount) as total_amount 
    ";
    $from = $this->from();
    $where = $this->where($includeRecurIds);
    $having = $this->having();

    if ($having) {
      $having = " HAVING $having ";
    }

    $sql = "SELECT $select FROM $from WHERE $where GROUP BY r.id WITH ROLLUP $having";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if($dao->fetch()){
      $summary = array(
        $dao->recurring_count . ' '.ts('Recurring Count'),
        $dao->donation_count . ' '. ts('Contribution Count'),
        round($dao->total_amount) .' '. ts('Total Amount'),
      );
      if(!empty($this->_formValues['start_date'])){
        $start_date = CRM_Utils_Date::customFormat($this->_formValues['start_date'], $this->_config->dateformatFull);
        $title = ts('Start Date') .' '. ts('>') .' '. $start_date;
      }
      else{
        $title = ts('Search Results');
      }
      return array(
        'summary' => $title,
        'total' => '<ul><li>'.implode('</li><li>', $summary).'</li></ul>',
      );
    }
    else{
      return NULL;
    }
    */
  }

  function alterRow(&$row) {
    $dao = $row['#dao'];
    $row['contribution_status_id'] = $this->_cstatus[$row['contribution_status_id']];
    $date = array('start_date', 'end_date', 'cancel_date', 'birth_date');
    foreach($date as $d){
      if(!empty($row[$d])){
        $row[$d] = CRM_Utils_Date::customFormat($row[$d], $this->_config->dateformatFull);
      }
    }

    if($this->_formValues['contribution_created_date'] || $this->_formValues['start_date']){
      $sql = "SELECT count(*) FROM civicrm_contribution WHERE contribution_status_id = 1 AND contribution_recur_id = {$row['id']}";
      $row['donation_count'] = CRM_Core_DAO::singleValueQuery($sql, CRM_Core_DAO::$_nullArray);
    }
    if(empty($row['last_receive_date'])){
      $sql = "SELECT receive_date FROM civicrm_contribution WHERE contribution_status_id = 1 AND contribution_recur_id = {$row['id']} ORDER BY receive_date DESC";
      $row['last_receive_date'] = CRM_Core_DAO::singleValueQuery($sql, CRM_Core_DAO::$_nullArray);
    }
    if(!empty($row['frequency_unit'])){
      $row['frequency_unit'] = ts($row['frequency_unit']);
    }
    if(!empty($row['gender_id'])){
      $row['gender_id'] = $this->_gender[$row['gender_id']];
    }
    $row['last_status_id'] = $this->_cstatus[$row['last_status_id']];
    $row['action'] = '<a href="'.CRM_Utils_System::url('civicrm/contact/view/contributionrecur', "reset=1&id={$row['id']}&cid={$row['contact_id']}").'" target="_blank">'.ts('View').'</a>';
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }


  function validateUserSQL(&$sql, $onlyWhere = FALSE){
    $includeStrings = array('contact_a');
    $excludeStrings = array('insert', 'delete', 'update');

    if (!$onlyWhere) {
      $includeStrings += array('select', 'from', 'where');
    }

    foreach ($includeStrings as $string) {
      if (stripos($sql, $string) === FALSE) {
        CRM_Core_Error::fatal(ts('Could not find \'%1\' string in SQL clause.',
            array(1 => $string)
          ));
      }
    }

    foreach ($excludeStrings as $string) {
      if (preg_match('/(\s' . $string . ')|(' . $string . '\s)/i', $sql)) {
        CRM_Core_Error::fatal(ts('Found illegal \'%1\' string in SQL clause.',
            array(1 => $string)
          ));
      }
    }
  }

}

