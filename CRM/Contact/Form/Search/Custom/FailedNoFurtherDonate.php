<?php

class CRM_Contact_Form_Search_Custom_FailedNoFurtherDonate extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public $_queryColumns;
  protected $_formValues;
  protected $_cstatus = NULL;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;
  protected $_failedDateFrom = NULL;
  protected $_failedDateTo = NULL;

  /**
   * The constructor gets the submitted form values
   *
   * @param array $formValues
   *
   * @access public
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->_filled = FALSE;
    if (empty($this->_tableName)) {
      $randomNum = CRM_Utils_String::createRandom(8, CRM_Utils_String::ALPHANUMERIC);
      $this->_tableName = 'civicrm_custom_search_failednofurtherdonate';
      $this->_cstatus = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
      if (!empty($this->_formValues['failed_date_from'])) {
        $this->_failedDateFrom = CRM_Utils_Date::processDate($this->_formValues['failed_date_from']);
      }
      if (!empty($this->_formValues['failed_date_to'])) {
        $this->_failedDateTo = CRM_Utils_Date::processDate($this->_formValues['failed_date_to'], '23:59:59');
      }
    }
  }

  /**
   * Build columns
   *
   * @return void
   * @access public
   */
  public function buildColumn() {
    $this->_queryColumns = [
      'contact.id' => 'id',
      'failed.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'failed.created_date' => 'created_date_failed',
      'success.created_date' => 'created_date_success',
      'failed.total_amount' => 'total_amount_failed',
      'success.total_amount' => 'total_amount_success',
    ];
    $this->_columns = [
      ts('ID') => 'contact_id',
      ts('Name') => 'sort_name',
      ts('Created Date') => 'created_date_failed',
      ts('Amount') . ' - (' . ts("Failed") . ')' => 'total_amount_failed',
    ];
  }
  /**
   * Build temp table
   *
   * @return void
   * @access public
   */
  public function buildTempTable() {
    $sql = "
CREATE TEMPORARY TABLE IF NOT EXISTS {$this->_tableName} (
  id int unsigned NOT NULL,
";

    foreach ($this->_queryColumns as $field) {
      if (in_array($field, ['id'])) {
        continue;
      }
      if (strstr($field, 'amount')) {
        $type = "INTEGER(10) default NULL";
      }
      else {
        $type = "VARCHAR(32) default ''";
      }
      if (strstr($field, '_date')) {
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
  /**
   * Drop temp table
   *
   * @return void
   * @access public
   */
  public function dropTempTable() {
    $sql = "DROP TEMPORARY TABLE IF EXISTS `{$this->_tableName}`" ;
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * fill temp table for further use
   *
   * @return void
   * @access public
   */
  public function fillTable() {
    $this->buildTempTable();
    $select = [];
    foreach ($this->_queryColumns as $k => $v) {
      $select[] = $k.' as '.$v;
    }
    $select = CRM_Utils_Array::implode(", \n", $select);
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
      foreach ($this->_queryColumns as $name) {
        if ($name == 'id') {
          $values[] = CRM_Utils_Type::escape($dao->id, 'Integer');
        }
        elseif (isset($dao->$name)) {
          $values[] = "'". CRM_Utils_Type::escape($dao->$name, 'String')."'";
        }
        else {
          $values[] = 'NULL';
        }
      }
      $values = CRM_Utils_Array::implode(', ', $values);
      $sql = "REPLACE INTO {$this->_tableName} VALUES ($values)";
      CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    }
  }

  /**
   * Get temp from clause
   *
   * @return string
   * @access public
   */
  public function tempFrom() {
    return "civicrm_contact AS contact INNER JOIN
 (SELECT ca.* FROM civicrm_contribution ca LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = ca.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = ca.id WHERE ca.is_test = 0 AND ca.contribution_status_id = 4 AND pp.id IS NULL AND mp.id IS NULL ORDER BY ca.created_date DESC) failed ON failed.contact_id = contact.id
   LEFT JOIN
(SELECT MIN(cb.created_date) created_date, cb.total_amount, cb.contact_id, cc.id FROM civicrm_contribution cb LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = cb.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = cb.id LEFT JOIN civicrm_contribution cc ON cb.contact_id = cc.contact_id WHERE cb.is_test = 0 AND cb.contribution_status_id = 1 AND pp.id IS NULL AND mp.id IS NULL AND cc.is_test = 0 AND cc.contribution_status_id = 4 AND cc.created_date < cb.created_date GROUP BY cc.id ORDER BY cb.created_date DESC) success ON success.id = failed.id
";
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   *
   * @return string
   * @access public
   */
  public function tempWhere() {
    $days = $this->_formValues['days'] ? $this->_formValues['days'] : 7;
    $clauses = [];
    $clauses[] = "contact.is_deleted = 0";
    $clauses[] = "(success.created_date IS NULL OR success.created_date > date_add(failed.created_date, INTERVAL $days DAY) OR success.created_date <= failed.created_date)";

    if (!empty($this->_failedDateFrom)) {
      $clauses[] = "failed.created_date >= {$this->_failedDateFrom}";
    }
    if (!empty($this->_failedDateTo)) {
      $clauses[] = "failed.created_date <= {$this->_failedDateTo}";
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  /**
   * Get temp having clause
   *
   * @return string
   * @access public
   */
  public function tempHaving() {
    return '';
  }

  /**
   * Builds the quickform for this search
   *
   * @param CRM_Core_Form $form
   *
   * @return void
   * @access public
   */
  public function buildForm(&$form) {
    for ($i = 2; $i <= 15; $i++) {
      $option[$i] = $i;
    }
    $form->addSelect('days', ts('days'), $option);
    $form->addDate('failed_date_from', ts('Failed Donation Date (From)'), FALSE);
    $form->addDate('failed_date_to', ts('Failed Donation Date (To)'), FALSE);
    $form->addFormRule(['CRM_Contact_Form_Search_Custom_FailedNoFurtherDonate', 'formRule']);
  }

  /**
   * Validate form values
   *
   * @param array $fields
   *
   * @return bool|array TRUE or array of errors
   * @access public
   */
  public static function formRule($fields) {
    $errors = [];
    $from = CRM_Utils_Array::value('failed_date_from', $fields);
    $to = CRM_Utils_Array::value('failed_date_to', $fields);

    $tsFrom = NULL;
    $tsTo = NULL;

    if ($from) {
      $tsFrom = strtotime($from);
      if ($tsFrom === FALSE) {
        $errors['failed_date_from'] = ts('Please enter a valid date for Failed Donation Date (From).');
      }
    }
    if ($to) {
      $tsTo = strtotime($to);
      if ($tsTo === FALSE) {
        $errors['failed_date_to'] = ts('Please enter a valid date for Failed Donation Date (To).');
      }
    }

    if (empty($errors) && $tsFrom && $tsTo) {
      if ($tsFrom > $tsTo) {
        $errors['failed_date_from'] = ts('Failed Donation Date (From) must be earlier than or equal to Failed Donation Date (To).');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set default values
   *
   * @return array
   * @access public
   */
  public function setDefaultValues() {
    return [
      'days' => 7,
    ];
  }

  /**
   * Set breadcrumb
   *
   * @return void
   * @access public
   */
  public function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  public function setTitle() {
    $days = $this->_formValues['days'];
    CRM_Utils_System::setTitle(ts('After payment failed but not retry in %1 days', [1 => $days]));
  }

  public function count() {
    if (!$this->_filled) {
      $this->fillTable();
      $this->_filled = TRUE;
    }
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery(
      $sql,
      CRM_Core_DAO::$_nullArray
    );
    return $dao->N;
  }

  /**
   * Construct the search query
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE) {
    $fields = !$onlyIDs ? "*" : "contact_a.contact_id" ;

    if (!$this->_filled) {
      $this->fillTable();
      $this->_filled = TRUE;
    }
    return $this->sql($fields, $offset, $rowcount, $sort, $includeContactIDs);
  }

  public function sql($selectClause, $offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $groupBy = NULL) {
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
  public function from() {
    return "FROM {$this->_tableName} contact_a";
  }

  public function where($includeContactIDs = FALSE) {
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
        $clauses[] = "contribution_status_id IN (".CRM_Utils_Array::implode(',', $status).")";
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
      */
    return ' (1) ';
  }

  public function having() {
    return '';
  }

  public static function includeContactIDs(&$sql, &$formValues, $isExport = FALSE) {
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

  public function &columns() {
    return $this->_columns;
  }

  public function summary() {
    // return $summary;
  }

  public function alterRow(&$row) {
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/FailedNoFurtherDonate.tpl';
  }

  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
