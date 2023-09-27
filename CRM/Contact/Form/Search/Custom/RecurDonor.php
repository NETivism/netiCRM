<?php
class CRM_Contact_Form_Search_Custom_RecurDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_tableName = NULL;

  function __construct(&$formValues){
    parent::__construct($formValues);
    if(empty($this->_tableName)){
      $this->_tableName = "civicrm_temp_custom_recurdonor";
      $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_cpage = CRM_Contribute_PseudoConstant::contributionPage();
      $this->_ctype = CRM_Contribute_PseudoConstant::contributionType();
      $this->_criteria = array(
        'active' => ts('active donors (and no past recurring donation)'),
        'inactive' => ts('inactive donors (past recurring donors)'),
        'intersection' => ts('recurring donors (multiple times, active now and inactive past)'),
        'all' => ts('recurring donors (no matter active or inactive)'),
        'never' => ts('not recurring donors (no matter with/without one-time donation)'),
      );
      $this->buildColumn();
    }
  }

  function buildColumn(){
    $this->_queryColumns = array( 
      'contact.id' => 'id',
      'contact.sort_name' => 'sort_name',
      'r1.id' => 'rid1',
      'r1.start_date' => 'start_date1',
      'r1.end_date' => 'end_date1',
      'ROUND(r1.amount,0)' => 'amount1',
      'r1.contribution_status_id' => 'contribution_status_id1',
      'r1.contribution_id' => 'contribution_id1',
      'r2.id' => 'rid2',
      'r2.start_date' => 'start_date2',
      'r2.end_date' => 'end_date2',
      'ROUND(r2.amount,0)' => 'amount2',
      'r2.contribution_status_id' => 'contribution_status_id2',
      'r2.contribution_id' => 'contribution_id2',
    );
    $this->_columns = array(
      ts('Contact ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Recurring Contributions ID').' ('.ts("In Progress").')' => 'rid1',
      ts('Contribution ID').' ('.ts("In Progress").')' => 'contribution_id1',
      ts('Recurring Status').' ('.ts("In Progress").')' => 'contribution_status_id1',
      ts('Amount').' ('.ts("In Progress").')' => 'amount1',
      ts('Type').' ('.ts("In Progress").')' => 'type_id1',
      ts('Contribution Page').' ('.ts("In Progress").')' => 'page_id1',
      ts('Recurring Contributions ID') => 'rid2',
      ts('Recurring Status') => 'contribution_status_id2',
      ts('Contribution ID') => 'contribution_id2',
      ts('Amount') => 'amount2',
      ts('Type') => 'type_id2',
      ts('Contribution Page') => 'page_id2',
    );
  }

  function buildTempTable() {
    $sql = "
CREATE TEMPORARY TABLE IF NOT EXISTS {$this->_tableName} (
  id INTEGER(11) NOT NULL,
";

    foreach ($this->_queryColumns as $field) {
      if (in_array($field, array('id'))) {
        continue;
      }
      if (strstr($field, 'amount') || strstr($field, '_id')) {
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
  function fillTable($dropTable = FALSE){
    if ($dropTable) {
      $this->dropTempTable();
    }
    $this->buildTempTable();

    $select = array();
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
ORDER BY r1.start_date ASC, r2.start_date ASC
";
    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $values = array();

      foreach($this->_queryColumns as $name){
        if ($name == 'id') {
          $values[] = $dao->$name;
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
      $this->_filled = TRUE;
    }
  }


  function tempFrom() {
    return "civicrm_contact as contact
    LEFT JOIN 
      (SELECT recur.*, MAX(con.id) as contribution_id FROM civicrm_contribution_recur recur INNER JOIN civicrm_contribution con ON con.contribution_recur_id = recur.id AND con.contribution_status_id = 1 AND con.is_test = 0 WHERE recur.is_test = 0 AND recur.contribution_status_id = 5 GROUP BY recur.id) AS r1 
      ON r1.contact_id = contact.id
    LEFT JOIN 
      (SELECT recur.*, MAX(con.id) as contribution_id FROM civicrm_contribution_recur recur INNER JOIN civicrm_contribution con ON con.contribution_recur_id = recur.id AND con.contribution_status_id = 1 AND con.is_test = 0 WHERE recur.is_test = 0 AND recur.contribution_status_id IN (1,3,6,7) GROUP BY recur.id) AS r2
      ON r2.contact_id = contact.id
    ";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function tempWhere(){
    return ' (contact.is_deleted = 0) ';
  }

  function tempHaving(){
    $having = '';
    switch($this->_formValues['search_criteria']) {
      case 'active':
        $having = " COUNT(r1.id) > 0 AND COUNT(r2.id) = 0 ";
        break;
      case 'inactive':
        $having = " COUNT(r1.id) = 0 AND COUNT(r2.id) > 0 ";
        break;
      case 'intersection':
        $having = " COUNT(r1.id) > 0 AND COUNT(r2.id) > 0 ";
        break;
      case 'all':
        $having = " COUNT(r1.id) > 0 OR COUNT(r2.id) > 0 ";
        break;
      case 'never':
        $having = " COUNT(r1.id) = 0 AND COUNT(r2.id) = 0 ";
        break;
    }
    return $having;
  }

  function buildForm(&$form){
    $form->addSelect('search_criteria', ts('Recurring Donors Search'), array('' => ts('-- select --')) + $this->_criteria, NULL, TRUE);

    $form->addNumber('amount_low', ts('giving level filter'), array('size' => 8, 'maxlength' => 8));
    $form->addNumber('amount_high', ts('To'), array('size' => 8, 'maxlength' => 8));

    foreach($this->_cpage as $pid => $title) {
      $pages[$pid] = $title."($pid)"; 
    }
    $form->addSelect('contribution_page_id', ts('Contribution Page'), array('' => ts('-- select --')) + $pages);

    $form->addSelect('contribution_type_id', ts('Contribution Type'), array('' => ts('-- select --')) + $this->_ctype);

    // assgin elements for tpl
    $form->assign('elements', array('search_criteria', 'amount_low', 'contribution_page_id', 'contribution_type_id'));
  }

  function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }


  function setDefaultValues() {
  }

  function setTitle() {
    CRM_Utils_System::setTitle(ts('Recurring Donors Search'));
  }

  function count(){
    if(!$this->_filled){
      $this->fillTable();
    }
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->N;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE) {
    if ($onlyIDs) {
      $fields = "DISTINCT contact_a.id as contact_id";
    }
    else {
      $fields = $this->select();
    }

    if(!$this->_filled){
      $this->fillTable();
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

  function select() {
    $fields = "";
    if ($this->_formValues['search_criteria'] == 'never') {
      $fields = "contact_a.*";
    }
    else {
      $fields = "contact_a.*, c1.contribution_type_id as type_id1, c1.contribution_page_id as page_id1, c2.contribution_type_id as type_id2, c2.contribution_page_id as page_id2";
    }
    return $fields;
  }

  function from() {
    // decrease query loading
    if ($this->_formValues['search_criteria'] == 'never') {
      return "FROM {$this->_tableName} contact_a";
    }
    else {
      return "FROM {$this->_tableName} contact_a
        LEFT JOIN civicrm_contribution c1 ON c1.id = contact_a.contribution_id1
        LEFT JOIN civicrm_contribution c2 ON c2.id = contact_a.contribution_id2
      ";
    }
  }

  function where($includeContactIDs = FALSE) {
    $criteria = $this->_formValues['search_criteria'];
    if ($criteria != 'never' && ($this->_formValues['amount_low'] || $this->_formValues['amount_high'] || $this->_formValues['contribution_page_id'] || $this->_formValues['contribution_type_id'])) {
      $clauses = array();  
      if (strlen($this->_formValues['amount_low']) > 0) {
        $amountLow = CRM_Utils_Type::escape($this->_formValues['amount_low'], 'Integer'); 
        if ($criteria == 'active') {
          $clauses[] = "contact_a.amount1 >= ".$amountLow;
        }
        if ($criteria == 'inactive') {
          $clauses[] = "contact_a.amount2 >= ".$amountLow;
        }
        if ($criteria == 'intersection') {
          $clauses[] = "(contact_a.amount1 >= $amountLow)";
          $clauses[] = "(contact_a.amount2 >= $amountLow)";
        }
        if ($criteria == 'all') {
          $clauses[] = "(contact_a.amount1 >= $amountLow OR contact_a.rid1 IS NULL)";
          $clauses[] = "(contact_a.amount2 >= $amountLow OR contact_a.rid2 IS NULL)";
        }
      }
      if (strlen($this->_formValues['amount_high']) > 0) {
        $amountHigh = CRM_Utils_Type::escape($this->_formValues['amount_high'], 'Integer'); 
        if ($criteria == 'active') {
          $clauses[] = "contact_a.amount1 <= '$amountHigh'";
        }
        if ($criteria == 'inactive') {
          $clauses[] = "contact_a.amount2 <= '$amountHigh'";
        }
        if ($criteria == 'intersection') {
          $clauses[] = "(contact_a.amount1 <= $amountHigh)";
          $clauses[] = "(contact_a.amount2 <= $amountHigh)";
        }
        if ($criteria == 'all') {
          $clauses[] = "(contact_a.amount1 <= $amountHigh OR contact_a.rid1 IS NULL)";
          $clauses[] = "(contact_a.amount2 <= $amountHigh OR contact_a.rid2 IS NULL)";
        }
      }

      if (strlen($this->_formValues['contribution_page_id']) > 0) {
        $pageId = CRM_Utils_Type::escape($this->_formValues['contribution_page_id'], 'Integer'); 
        if ($criteria == 'active') {
          $clauses[] = "c1.contribution_page_id = '$pageId'";
        }
        if ($criteria == 'inactive') {
          $clauses[] = "c2.contribution_page_id = '$pageId'";
        }
        if ($criteria == 'intersection') {
          $clauses[] = "(c1.contribution_page_id = '$pageId')";
          $clauses[] = "(c2.contribution_page_id = '$pageId')";
        }
        if ($criteria == 'all') {
          $clauses[] = "(c1.contribution_page_id = '$pageId' OR contact_a.rid1 IS NULL)";
          $clauses[] = "(c2.contribution_page_id = '$pageId' OR contact_a.rid2 IS NULL)";
        }
      }

      if (strlen($this->_formValues['contribution_type_id']) > 0) {
        $typeId = CRM_Utils_Type::escape($this->_formValues['contribution_type_id'], 'Integer'); 
        if ($criteria == 'active') {
          $clauses[] = "c1.contribution_type_id = '$typeId'";
        }
        if ($criteria == 'inactive') {
          $clauses[] = "c2.contribution_type_id = '$typeId'";
        }
        if ($criteria == 'intersection') {
          $clauses[] = "(c1.contribution_type_id = '$typeId')";
          $clauses[] = "(c2.contribution_type_id = '$typeId')";
        }
        if ($criteria == 'all') {
          $clauses[] = "(c1.contribution_type_id = '$typeId' OR contact_a.rid1 IS NULL)";
          $clauses[] = "(c2.contribution_type_id = '$typeId' OR contact_a.rid2 IS NULL)";
        }
      }

      $sql = CRM_Utils_Array::implode(' AND ', $clauses);
    }
    else {
      $sql = ' ( 1 ) ';
    }
    
    if ($includeContactIDs) {
      self::includeContactIDs($sql, $this->_formValues);
    }
    return $sql;
  }

  public static function includeContactIDs(&$sql, &$formValues, $isExport = FALSE) {
    $contactIDs = array();
    foreach ($formValues as $id => $value) {
      list($contactID, $additionalID) = CRM_Core_Form::cbExtract($id);
      if ($value && !empty($contactID)) {
        $contactIDs[] = $contactID;
      }
    }

    if (!empty($contactIDs)) {
      $contactIDs = CRM_Utils_Array::implode(', ', $contactIDs);
      $sql .= " AND contact_a.id IN ( $contactIDs )";
    }
  }

  function &columns(){
    return $this->_columns;
  }

  function templateFile(){
    return 'CRM/Contact/Form/Search/Custom/RecurDonor.tpl';
  }

  function summary() {
    $summary = array();
    if(!$this->_filled){
      $this->fillTable();
      $this->_filled = TRUE;
    }

    $summary['search_criteria'] = array(
      'label' => ts('Search Criteria'),
    );
    $formCriteria = array(
      'search_criteria' => ts('Recurring Donors Search'),
      'amount_low' => ts('Min Amount'),
      'amount_high' => ts('Max Amount'),
      'contribution_page_id' => ts('Contribution Page'),
      'contribution_type_id' => ts('Contribution Type'),
    );

    $values = array();
    foreach($formCriteria as $key => $label) {
      if (!empty($this->_formValues[$key])) {
        if ($key == 'search_criteria') {
          $values[] = $label.": ". $this->_criteria[$this->_formValues[$key]];
        }
        if ($key == 'amount_low' || $key == 'amount_high') {
          $values[] = $label.": ". $this->_formValues[$key];
        }
        if ($key == 'contribution_page_id') {
          $values[] = $label.": ". $this->_cpage[$this->_formValues[$key]];
        }
        if ($key == 'contribution_type_id') {
          $values[] = $label.": ". $this->_ctype[$this->_formValues[$key]];
        }
      }
    }
    $summary['search_criteria']['value'] = '<ul><li>'.CRM_Utils_Array::implode('</li><li>', $values).'</li></ul>';

    return $summary;
  }
  function alterRow(&$row) {
    if ($row['contribution_status_id1']) {
      $row['contribution_status_id1'] = $this->_cstatus[$row['contribution_status_id1']];
    }
    if ($row['contribution_status_id2']) {
      $row['contribution_status_id2'] = $this->_cstatus[$row['contribution_status_id2']];
    }
    
    if ($row['type_id1']) {
      $row['type_id1'] = $this->_ctype[$row['type_id1']];
    }
    if ($row['type_id2']) {
      $row['type_id2'] = $this->_ctype[$row['type_id2']];
    }

    if ($row['page_id1'] && empty($this->_isExport)) {
      $params = array(
        'p' => 'civicrm/admin/contribute',
        'q' => "action=update&reset=1&id={$row['page_id1']}",
      );
      $row['page_id1'] = '<a href="'.CRM_Utils_System::crmURL($params).'" target="_blank">'.$this->_cpage[$row['page_id1']].'</a>';
    }
    if ($row['page_id2'] && empty($this->_isExport)) {
      $params = array(
        'p' => 'civicrm/admin/contribute',
        'q' => "action=update&reset=1&id={$row['page_id2']}",
      );
      $row['page_id2'] = '<a href="'.CRM_Utils_System::crmURL($params).'" target="_blank">'.$this->_cpage[$row['page_id2']].'</a>';
    }

    if ($row['rid1'] && empty($this->_isExport)) {
      $params = array(
        'p' => 'civicrm/contact/view/contributionrecur',
        'q' => "reset=1&id={$row['rid1']}&cid={$row['id']}",
      );
      $row['rid1'] = '<a href="'.CRM_Utils_System::crmURL($params).'" target="_blank">'.$row['rid1'].'</a>';
    }
    if ($row['rid2'] && empty($this->_isExport)) {
      $params = array(
        'p' => 'civicrm/contact/view/contributionrecur',
        'q' => "reset=1&id={$row['rid2']}&cid={$row['id']}",
      );
      $row['rid2'] = '<a href="'.CRM_Utils_System::crmURL($params).'" target="_blank">'.$row['rid2'].'</a>';
    }

    if ($row['contribution_id1'] && empty($this->_isExport)) {
      $params = array(
        'p' => 'civicrm/contact/view/contribution',
        'q' => "reset=1&id={$row['contribution_id1']}&cid={$row['id']}&action=view",
      );
      $row['contribution_id1'] = '<a href="'.CRM_Utils_System::crmURL($params).'" target="_blank">'.$row['contribution_id1'].'</a>';
    }
    if ($row['contribution_id2'] && empty($this->_isExport)) {
      $params = array(
        'p' => 'civicrm/contact/view/contribution',
        'q' => "reset=1&id={$row['contribution_id2']}&cid={$row['id']}&action=view",
      );
      $row['contribution_id2'] = '<a href="'.CRM_Utils_System::crmURL($params).'" target="_blank">'.$row['contribution_id2'].'</a>';
    }
    // Refs #38855, Workaround for export error when there are NULL field.
    foreach ($row as $key => $value) {
      if ($value == NULL) {
        $row[$key] = '';
      }
    }
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}