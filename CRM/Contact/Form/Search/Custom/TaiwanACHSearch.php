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

class CRM_Contact_Form_Search_Custom_TaiwanACHSearch extends CRM_Contact_Form_Search_Custom_RecurSearch implements CRM_Contact_Form_Search_Interface {

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
  
  function __construct(&$formValues){
    parent::__construct($formValues);
    $this->_tableName = 'civicrm_temp_custom_achsearch';
    $this->buildColumn();
  }

  function buildColumn(){
    $this->_queryColumns = [ 
      'r.id' => 'id',
      'r.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'ROUND(r.amount,0)' => 'amount',
      'r.contribution_status_id' => 'contribution_status_id',
      'r.create_date' => 'create_date',
      'r.start_date' => 'start_date',
      'r.end_date' => 'end_date',
      'r.cancel_date' => 'cancel_date',
      'ROUND(SUM(IF(c.contribution_status_id = 1, c.total_amount, 0)),0)' => 'receive_amount',
      'MAX(c.created_date)' => 'current_created_date',
      'lrd.last_receive_date' => 'last_receive_date',
      'lfd.last_failed_date' => 'last_failed_date',
      'ach.contribution_page_id' => 'contribution_page_id',
      'COUNT(IF(c.contribution_status_id = 1, 1, NULL))' => 'completed_count',
      'COUNT(c.id)' => 'total_count',
      'ach.id' => 'ach_id',
      'ach.stamp_verification' => 'stamp_verification',
      'ach.payment_type' => 'payment_type',
      'ach.bank_account' => 'bank_account',
      'ach.bank_code' => 'bank_code',
      'ach.identifier_number' => 'identifier_number',
    ];
    $this->_columns = [
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Amount') => 'amount',
      ts('Start Date') => 'start_date',
      ts('End Date') => 'end_date',
      ts('Cancel Date') => 'cancel_date',
      ts('Recurring Status') => 'contribution_status_id',
      ts('Stamp Verification') => 'stamp_verification',
      ts('Total Receive Amount') => 'receive_amount',
      ts('Most Recent').' '.ts('Created Date') => 'current_created_date',
      ts('Last Receive Date') => 'last_receive_date',
      ts('Last Failed Date') => 'last_failed_date',
      ts('Contribution Page ID') => 'contribution_page_id',
      ts('Completed Donation').'/<br>'.ts('Total Count') => 'completed_count',
      ts('Bank Account') => 'bank_account',
      0 => 'ach_data',
      //ts('Stamp Verification'). ' - '.ts('Cancelled or Failed Date') => 'ach_data',
      //ts('Stamp Verification'). ' - '.ts('Cancelled or Failed Reason') => 'ach_data',
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
      if (strstr($field, 'amount') || preg_match('/.*id$/', $field)) {
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
    INNER JOIN civicrm_contact AS contact ON contact.id = r.contact_id
    INNER JOIN civicrm_contribution_taiwanach ach ON r.id = ach.contribution_recur_id
    LEFT JOIN civicrm_contribution AS c ON c.contribution_recur_id = r.id
    LEFT JOIN (SELECT contribution_recur_id AS rid, MAX(receive_date) AS last_receive_date FROM civicrm_contribution WHERE contribution_status_id = 1 AND contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) lrd ON lrd.rid = r.id
    LEFT JOIN (SELECT contribution_recur_id AS rid, MAX(cancel_date) AS last_failed_date FROM civicrm_contribution WHERE contribution_status_id = 4 AND contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) lfd ON lfd.rid = r.id";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    $clauses = [];
    $clauses[] = "(r.is_test = 0)";
    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function tempHaving(){
    return '';
  }

  function buildForm(&$form){
    // parent include start_date, status, installments, sort_name, email, contribution_page_id
    parent::buildForm($form);

    // rest is ach specify form
    $form->addDateRange('create_date', ts('Recurring Contribution').' - '.ts('Create Date'), NULL, FALSE);
    $form->addSelect('stamp_verification', ts('Stamp Verification Status'), [
      '' => ts('-- select --'),
      0 => ts('Pending'),
      1 => ts('Completed'),
      2 => ts('Failed'),
    ]);
    $form->addDateRange('end_date', ts('Recurring Contribution').' - '.ts('End Date'), NULL, FALSE);
    $form->addSelect('payment_type', ts('Payment Instrument'), [
      '' => ts('-- select --'),
      'ACH Bank' => ts('Bank'),
      'ACH Post' => ts('Post Office'),
    ]);
    $bankCode = CRM_Contribute_PseudoConstant::taiwanACH();
    $form->addSelect('bank_code', ts('Bank Identification Number'), ['' => ts('-- select --')] + $bankCode);
    $form->add('text', 'bank_account', ts('Account Number'));
    $form->add('text', 'identifier_number', ts('Legal Identifier').'/'.ts('SIC Code'));

    // stamp veriction complete date === recurring start date == import date
    // so using exists start_date element
    $ele = $form->getElement('start_date_from');
    $ele->_label = ts('Stamp Verication Date') .'/'.ts('Recurring Contribution').' - '.ts('Start Date');
    
    $form->assign('elements', [
      'create_date',
      'status',
      'stamp_verification',
      'start_date',
      'end_date',
      'payment_type',
      'bank_code',
      'bank_account',
      'identifier_number',
      'sort_name',
      'contribution_page',
    ]);
  }

  function setDefaultValues() {
    return [];
  }

  function setTitle() {
    CRM_utils_System::setTitle(ts('ACH Search'));
  }

  function setBreadcrumb() {
  }

  function count(){
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $sql = "SELECT COUNT(*)" . $this->from() . " WHERE ". $this->where();
    $count = CRM_Core_DAO::singleValueQuery($sql);
    if ($count) {
      return $count;
    }
    return 0;
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
    $clauses = [];

    $dateFields = [
      'create_date',
      'start_date',
      'end_date',
    ];
    foreach($dateFields as $fieldName) {
      if (!empty($this->_formValues[$fieldName.'_from'])) {
        $dateFrom = CRM_Utils_Date::processDate($this->_formValues[$fieldName.'_from']);
        $clauses[] = "($fieldName >= '$dateFrom')";
      }
      if (!empty($this->_formValues[$fieldName.'_to'])) {
        $dateTo = CRM_Utils_Date::processDate($this->_formValues[$fieldName.'_to'].' 23:59:59');
        $clauses[] = "($fieldName <= '$dateTo')";
      }
    }

    if ($this->_formValues['status'] && is_numeric($this->_formValues['status'])) {
      $clauses[] = "(contribution_status_id = '{$this->_formValues['status']}')";
    }

    if (isset($this->_formValues['stamp_verification']) && is_numeric($this->_formValues['stamp_verification'])) {
      $clauses[] = "(stamp_verification = '{$this->_formValues['stamp_verification']}')";
    }

    $achFields = [
      'payment_type',
      'bank_code',
      'bank_account',
      'identifier_number',
    ];

    foreach($achFields as $fieldName) {
      if (isset($this->_formValues[$fieldName]) && !empty($this->_formValues[$fieldName])) {
        $clauses[] = "($fieldName LIKE '{$this->_formValues[$fieldName]}')";
      }
    }

    $sort_name = $this->_formValues['sort_name'];
    if($sort_name){
      $clauses[] = "(sort_name LIKE '%$sort_name%')";
    }

    $contributionPage = $this->_formValues['contribution_page'];
    if (!empty($contributionPage)) {
      $clauses[] = "contribution_page_id IN (".CRM_Utils_Array::implode(",", $contributionPage).")";
    }

    if (!empty($clauses)) {
      $sql = CRM_Utils_Array::implode(' AND ', $clauses);
    }
    else {
      $sql = ' ( 1 ) ';
    }

    if ($includeContactIDs) {
      self::includeContactIDs($sql, $this->_formValues,$this->_isExport);
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
    $query = CRM_Core_DAO::executeQuery("SELECT SUM(receive_amount) as amount FROM {$this->_tableName} WHERE ".$this->where());
    $query->fetch();
    
    if ($query->amount) {
      $amount = CRM_Utils_Money::format($query->amount);
      $summary['search_results']['value'] .= ' '.ts('Total amount of completed contributions is %1.', [1 => $amount]);
    }

    return $summary;
  }

  function alterRow(&$row) {
    $row['contribution_status_id'] = $this->_cstatus[$row['contribution_status_id']];
    
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

    if (isset($row['stamp_verification'])) {
      if ($row['stamp_verification'] == 0) {
        $row['stamp_verification'] = ts('Pending');
      }
      if ($row['stamp_verification'] == 1) {
        $row['stamp_verification'] = ts('Completed');
      }
      if ($row['stamp_verification'] == 2 && empty($this->_isExport)) {
        $row['stamp_verification'] = '<strong class="disabled">'.ts('Failed').'</strong>';
      }
    }

    $date = ['start_date', 'end_date', 'cancel_date'];
    foreach($date as $d){
      if(!empty($row[$d])){
        $row[$d] = CRM_Utils_Date::customFormat($row[$d], $this->_config->dateformatFull);
      }
    }

    $links = CRM_Contribute_Page_Tab::recurLinks();
    unset($links[CRM_Core_Action::DISABLE]);
    // add ach link
    $links[CRM_Core_Action::ADD] = $links[CRM_Core_Action::UPDATE];
    $links[CRM_Core_Action::ADD]['name'] .= 'ACH';
    $links[CRM_Core_Action::ADD]['url'] = 'civicrm/contribute/taiwanach';
    $links[CRM_Core_Action::ADD]['qs'] = 'reset=1&action=update&id=%%ach_id%%&cid=%%cid%%';
    $action = array_sum(array_keys($links));
    $row['action'] = CRM_Core_Action::formLink($links, $action,
      [
        'cid' => $row['contact_id'],
        'id' => $row['id'],
        'ach_id' => $row['#dao']['ach_id'],
        'cxt' => 'contribution',
      ]
    );
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/TaiwanACHSearch.tpl';
  }

  public static function tasks() {
    return [
      1001 => [
        'title' => ts('Export ACH Stamp Verification File'),
        'class' => ['CRM_Contact_Form_Task_TaiwanACHExportVerification'],
        'result' => TRUE,
      ],
      1002 => [
        'title' => ts('Export ACH Transaction File'),
        'class' => ['CRM_Contact_Form_Task_TaiwanACHExportTransaction'],
        'result' => TRUE,
      ],
    ];
  }
}

