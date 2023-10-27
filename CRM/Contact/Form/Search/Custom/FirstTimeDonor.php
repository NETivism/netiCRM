<?php

class CRM_Contact_Form_Search_Custom_FirstTimeDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  protected $_recurringStatus = array();
  protected $_contributionPage = NULL;

  function __construct(&$formValues){
    parent::__construct($formValues);

    $this->_filled = FALSE;
    $this->_tableName = 'civicrm_temp_custom_FirstTimeDonor';
    $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->_cstatus = $statuses;
    $this->_recurringStatus = array(
      2 => ts('All'),
      1 => ts("Recurring Contribution"),
      0 => ts("Non-recurring Contribution"),
    );
    $this->_contributionPage = CRM_Contribute_PseudoConstant::contributionPage();
    $this->_instruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $this->_contributionType = CRM_Contribute_PseudoConstant::contributionType();
    $this->_config = CRM_Core_Config::singleton();
    $this->buildColumn();
    if (!empty($formValues)) {
      foreach($formValues as $k => $v) {
        if (preg_match('/^status\[(\d)\]/i', $k, $matches)) {
          $formValues['status'][$matches[1]] = $matches[1];
        }
      }
    }
  }

  function buildColumn(){
    $this->_queryColumns = array(
      'contact.id' => 'id',
      'c.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'c.min_receive_date' => 'receive_date',
      'ROUND(c.total_amount,0)' => 'amount',
      'c.contribution_recur_id' => 'contribution_recur_id',
      'c.contribution_page_id' => 'contribution_page_id',
      'c.payment_instrument_id' => 'instrument_id',
      'c.contribution_type_id' => 'contribution_type_id',
    );
    $this->_columns = array(
      ts('Contact ID') => 'id',
      ts('Name') => 'sort_name',
      ts('First Amount') => 'amount',
      ts('Contribution Page') => 'contribution_page_id',
      ts('Recurring Contribution') => 'contribution_recur_id',
      ts('Payment Instrument') => 'instrument_id',
      ts('Contribution Type') => 'contribution_type_id',
      ts('Created Date') => 'receive_date',
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
    $select = array();
    foreach($this->_queryColumns as $k => $v){
      $select[] = $k.' as '.$v;
    }
    $select = CRM_Utils_Array::implode(", \n" , $select);
    $from = $this->tempFrom();
    $where = $this->tempWhere();

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY contact.id
";
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    while ($dao->fetch()) {
      $values = array();
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
    $sub_where_clauses = array();
    $sub_where_clauses[] = 'co.is_test = 0';
    $sub_where_clauses[] = 'pp.id IS NULL';
    $sub_where_clauses[] = 'mp.id IS NULL';
    $sub_where_clauses[] = 'co.contribution_status_id = 1';
    $sub_where_clause = CRM_Utils_Array::implode(' AND ', $sub_where_clauses);
    $sub_query = "SELECT MIN(IFNULL(co.receive_date, co.created_date)) AS min_receive_date, co.* FROM civicrm_contribution co
      LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = co.id
      LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = co.id
      WHERE $sub_where_clause
      GROUP BY co.contact_id";

    return " civicrm_contact AS contact
      INNER JOIN ($sub_query) c ON contact.id = c.contact_id";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    $clauses = array();
    $clauses[] = "contact.is_deleted = 0";

    if (!empty($this->_formValues['receive_date_from'])) {
      $receive_date_from = CRM_Utils_Date::processDate($this->_formValues['receive_date_from']);
      $clauses[] = "c.min_receive_date >= '$receive_date_from'";
    }
    if (!empty($this->_formValues['receive_date_to'])) {
      $receive_date_to = CRM_Utils_Date::processDate($this->_formValues['receive_date_to']);
      $clauses[] = "c.min_receive_date <= '$receive_date_to'";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function buildForm(&$form){
    // Define the search form fields here

    $form->addDateRange('receive_date', ts('Receive Date').' - '.ts('From'), NULL, FALSE);

    $recurring = $form->addRadio('recurring', ts('Recurring Contribution'), $this->_recurringStatus);
    $form->addSelect('contribution_page_id', ts('Contribution Page'), array('' => ts('- select -')) + $this->_contributionPage);

    $form->assign('elements', array('receive_date', 'recurring', 'contribution_page_id'));
  }

  function setDefaultValues() {
    return array(
      'receive_date_from' => date('Y-m-01', time() - 86400*90),
      'recurring' => 2,
    );
  }

  function qill(){
    $qill = array();
    $from = !empty($this->_formValues['receive_date_from']) ? $this->_formValues['receive_date_from'] : NULL;
    $to = !empty($this->_formValues['receive_date_to']) ? $this->_formValues['receive_date_to'] : NULL;
    if ($from || $to) {
      $to = empty($to) ? ts('no limit') : $to;
      $from = empty($from) ? ' ... ' : $from;
      $qill[1]['receiveDateRange'] = ts("Receive Date").': '. $from . '~' . $to;
    }

    $qill[1]['status'] = ts('Status').': '.$this->_cstatus[1];

    if (!empty($this->_formValues['recurring'])) {
      $qill[1]['recurring'] = ts('Recurring Contribution').': '.$this->_recurringStatus[$this->_formValues['recurring']];
    }

    if (!empty($this->_formValues['contribution_page_id'])) {
      $qill[1]['contributionPage'] = ts('Contribution Page').': '.$this->_contributionPage[$this->_formValues['contribution_page_id']];
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
    $receive_date_from = CRM_Utils_Array::value('receive_date_from', $this->_formValues);
    $receive_date_to = CRM_Utils_Array::value('receive_date_to', $this->_formValues);
    if ($receive_date_from) {
      $receive_date_from = CRM_Utils_Date::processDate($receive_date_from);
      $clauses[] = "receive_date >= '$receive_date_from'";
    }
    if ($receive_date_to) {
      $receive_date_to = CRM_Utils_Date::processDate($receive_date_to, '23:59:59');
      $clauses[] = "receive_date <= '$receive_date_to'";
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
      $sql = '('.CRM_Utils_Array::implode(' AND ', $clauses).')';
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
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $count = $this->count();

    $sql = "SELECT SUM(amount) as amount_sum FROM {$this->_tableName}";
    $whereClause = $this->where();
    if (!empty($whereClause)) {
      $sql .= " WHERE $whereClause";
    }

    $query = CRM_Core_DAO::executeQuery($sql);
    $query->fetch();

    if ($query->amount_sum) {
      $summary['search_results'] = array(
        'label' => ts('Search Results'),
        'value' => '',
      );
      $amount_sum = '$'.CRM_Utils_Money::format($query->amount_sum, ' ');
      $amount_avg = '$'.CRM_Utils_Money::format($query->amount_sum / $count, ' ');
      $summary['search_results']['value'] = ts('Total amount of completed contributions is %1.', array(1 => $amount_sum)).' / '.ts('for')." ".$count." ".ts('People').' / '.ts('Average').": ".$amount_avg;
    }

    return $summary;
  }

  function alterRow(&$row) {
    if (!empty($row['amount']) && empty($this->_isExport)) {
      $row['amount'] = CRM_Utils_Money::format($row['amount']);
    }
    if (!empty($row['instrument_id'])) {
      $row['instrument_id'] = $this->_instruments[$row['instrument_id']];
    }
    if (!empty($row['contribution_type_id'])) {
      $row['contribution_type_id'] = $this->_contributionType[$row['contribution_type_id']];
    }
    if (empty($this->_isExport)) {
      if (!empty($row['contribution_recur_id'])) {
        $contactId = $row['id'];
        $recurId = $row['contribution_recur_id'];
        $row['contribution_recur_id'] = "<a href='".CRM_Utils_System::url('civicrm/contact/view/contributionrecur',"reset=1&id={$recurId}&cid={$contactId}")."' target='_blank'>".ts("Recurring contributions")."</a>";
      }
      else {
        $row['contribution_recur_id'] = ts('One-time Contribution');
      }
    }

    if (!empty($row['contribution_page_id']) && empty($this->_isExport)) {
      $pageId = $row['contribution_page_id'];
      $row['contribution_page_id'] = "<a href='".CRM_Utils_System::url('civicrm/admin/contribute', 'action=update&reset=1&id='.$pageId)."' target='_blank'>". $this->_contributionPage[$pageId]."</a>";
    }
    // for #38751 error, workaround.
    if ($this->_isExport) {
      if (empty($row['contribution_page_id'])) {
        $row['contribution_page_id'] = '';
      }
      if (empty($row['contribution_recur_id'])) {
        $row['contribution_recur_id'] = '';
      }
    }
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
