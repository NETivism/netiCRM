<?php

class CRM_Contact_Form_Search_Custom_SingleNotRecurring extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public $_instruments;
  public $_queryColumns;
  protected $_formValues;
  protected $_config;
  protected $_tableName = NULL;
  protected $_filled = NULL;

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->_instruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $this->_filled = FALSE;
    if (empty($this->_tableName)) {
      $this->_tableName = 'civicrm_custom_search_singlenotrecurring';
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
    }
  }

  /**
   * Build the columns for the search results.
   */
  public function buildColumn() {
    $this->_queryColumns = [
      'contact.id' => 'id',
      'c.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'c.payment_instrument_id' => 'payment_instrument_id',
      'ROUND(SUM(c.total_amount))' => 'receive_amount',
      'COUNT(c.id)' => 'completed_count',
      'MAX(c.contribution_recur_id)' => 'last_success_contribution_recur_id'
    ];
    $this->_columns = [
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Payment Instrument') => 'payment_instrument_id',
      ts('Total Receive Amount') => 'receive_amount',
      ts('Completed Donation') => 'completed_count',
    ];
  }

  /**
   * Build the temporary table.
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
      if (strstr($field, 'amount') || strstr($field, 'count') || strstr($field, '_id')) {
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
   * Drop the temporary table.
   */
  public function dropTempTable() {
    $sql = "DROP TEMPORARY TABLE IF EXISTS `{$this->_tableName}`" ;
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Fill temp table for further use.
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
   * Get the FROM clause for the temporary table query.
   *
   * @return string
   */
  public function tempFrom() {
    return "civicrm_contact AS contact INNER JOIN civicrm_contribution c ON c.contact_id = contact.id AND c.is_test = 0 AND c.contribution_status_id = 1 LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = c.id";
  }

  /**
   * Get the WHERE clause for the temporary table query.
   *
   * WHERE clause is an array built from any required JOINS plus conditional
   * filters based on search criteria field values.
   *
   * @return string
   */
  public function tempWhere() {
    $clauses = [];
    $clauses[] = "contact.is_deleted = 0";

    $from = !empty($this->_formValues['receive_date_from']) ? $this->_formValues['receive_date_from'] : NULL;
    $to = !empty($this->_formValues['receive_date_to']) ? $this->_formValues['receive_date_to'] : NULL;
    if ($from) {
      $clauses[] = "c.receive_date >= '$from'";
    }
    if ($to) {
      $clauses[] = "c.receive_date <= '$to'";
    }

    $instrumentId = !empty($this->_formValues['payment_instrument_id']) ? $this->_formValues['payment_instrument_id'] : NULL;
    if ($instrumentId && is_numeric($instrumentId)) {
      $clauses[] = "c.payment_instrument_id = $instrumentId";
    }

    $clauses[] = "pp.id IS NULL";

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  /**
   * Get the HAVING clause for the temporary table query.
   *
   * @return string
   */
  public function tempHaving() {
    $count = $this->_formValues['contribution_count'];
    $clauses = [];
    $clauses[] = "COUNT(c.id) >= $count";
    $clauses[] = "last_success_contribution_recur_id IS NULL";
    return CRM_Utils_Array::implode(' AND ', $clauses);
    return '';
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    for ($i = 1; $i <= 10; $i++) {
      $option[$i] = $i;
    }
    $form->addSelect('contribution_count', ts('month'), $option);
    $form->addSelect('payment_instrument_id', ts('Payment Instrument'), ['' => ts('- select -')] + $this->_instruments);
    $form->addDateRange('receive_date', ts('Received Date').' - '.ts('From'), NULL, FALSE);
  }

  /**
   * Set the default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    return [
      'contribution_count' => 3,
    ];
  }

  /**
   * Get data for the 'qill' display.
   *
   * @return array<int, array<string, mixed>>
   */
  public function qill() {
    $qill = [];

    $count = $this->_formValues['contribution_count'];
    $qill[1]['count'] = ts('Single donation over %1 times', [1 => $count]);

    $from = !empty($this->_formValues['receive_date_from']) ? $this->_formValues['receive_date_from'] : NULL;
    $to = !empty($this->_formValues['receive_date_to']) ? $this->_formValues['receive_date_to'] : NULL;
    if ($from || $to) {
      $to = empty($to) ? ts('Today') : $to;
      $from = empty($from) ? ' ... ' : $from;
      $qill[1]['receiveDateRange'] = ts("Receive Date").': '. $from . '~' . $to;
    }

    $instrument = $this->_formValues['payment_instrument_id'];
    $qill[1]['paymentInstrument'] = ts("Payment Instrument").' = '. $this->_instruments[$instrument];
    return $qill;
  }

  /**
   * Set the breadcrumb for the search page.
   */
  public function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  /**
   * Set the page title.
   */
  public function setTitle() {
    $count = $this->_formValues['contribution_count'];
    $title = ts('Single donation over %1 times', [1 => $count]);
    CRM_Utils_System::setTitle($title);
  }

  /**
   * Get the count of contacts found.
   *
   * @return int
   */
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
   * Construct the search query.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null|string|object $sort
   * @param bool $includeContactIDs
   * @param bool $onlyIDs
   *
   * @return string
   */
  public function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = FALSE) {
    $fields = !$onlyIDs ? "*" : "contact_a.contact_id" ;

    if (!$this->_filled) {
      $this->fillTable();
      $this->_filled = TRUE;
    }
    return $this->sql($fields, $offset, $rowcount, $sort, $includeContactIDs);
  }

  /**
   * Generic SQL builder.
   *
   * @param string $selectClause
   * @param int $offset
   * @param int $rowcount
   * @param null|string|object $sort
   * @param bool $includeContactIDs
   * @param null|string $groupBy
   *
   * @return string
   */
  public function sql($selectClause, $offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $groupBy = NULL) {
    $sql = "SELECT $selectClause " . $this->from() . " WHERE ". $this->where($includeContactIDs);

    if ($groupBy) {
      $sql .= " $groupBy ";
    }
    $this->addSortOffset($sql, $offset, $rowcount, $sort);
    return $sql;
  }

  /**
   * Build the FROM clause for the main query.
   *
   * @return string
   */
  public function from() {
    return "FROM {$this->_tableName} contact_a";
  }

  /**
   * Build the WHERE clause for the main query.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    return ' (1) ';
  }

  /**
   * Build the HAVING clause for the main query.
   *
   * @return string
   */
  public function having() {
    return '';
  }

  /**
   * Append a list of contact IDs to the WHERE clause of a query.
   *
   * @param string $sql
   * @param array $formValues
   * @param bool $isExport
   */
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

  /**
   * Getter for the display columns.
   *
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * Get a summary of the search criteria.
   *
   * @return void
   */
  public function summary() {
    // return $summary;
  }

  /**
   * Alter a single result row for display.
   *
   * @param array $row
   */
  public function alterRow(&$row) {
    if (!empty($row['payment_instrument_id'])) {
      $row['payment_instrument_id'] = $this->_instruments[$row['payment_instrument_id']];
    }
  }

  /**
   * Get the path to the template file.
   *
   * Define the smarty template used to layout the search form and results listings.
   *
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/SingleNotRecurring.tpl';
  }

  /**
   * Get the SQL for retrieving contact IDs.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null|string|object $sort
   *
   * @return string
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
