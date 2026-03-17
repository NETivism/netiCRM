<?php

/**
 * Custom search for first-time donors.
 *
 * Finds contacts whose first completed contribution falls within
 * the specified date range, excluding membership and participant payments.
 */
class CRM_Contact_Form_Search_Custom_FirstTimeDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  /**
   * @var mixed[]
   */
  public $_instruments;
  /**
   * @var mixed[]
   */
  public $_contributionType;

  /**
   * Query column mapping (SQL expression => alias).
   *
   * @var string[]
   */
  public $_queryColumns;

  /**
   * Whether this is an export context.
   *
   * @var bool
   */
  public $_isExport;

  /**
   * Submitted form values.
   *
   * @var array
   */
  protected $_formValues;

  /**
   * Contribution status labels.
   *
   * @var array|null
   */
  protected $_cstatus = NULL;

  /**
   * CiviCRM config singleton.
   *
   * @var CRM_Core_Config
   */
  protected $_config;

  /**
   * Temporary table name.
   *
   * @var string|null
   */
  protected $_tableName = NULL;

  /**
   * Whether the temp table has been filled.
   *
   * @var bool|null
   */
  protected $_filled = NULL;

  /**
   * Recurring status options.
   *
   * @var string[]
   */
  protected $_recurringStatus = [];

  /**
   * Contribution page options.
   *
   * @var array|null
   */
  protected $_contributionPage = NULL;

  /**
   * Constructor.
   *
   * @param array $formValues
   *   Submitted form values.
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->_filled = FALSE;
    $this->_tableName = 'civicrm_temp_custom_FirstTimeDonor';
    $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
    $this->_cstatus = $statuses;
    $this->_recurringStatus = [
      2 => ts('All'),
      1 => ts("Recurring Contribution"),
      0 => ts("Non-recurring Contribution"),
    ];
    $this->_contributionPage = CRM_Contribute_PseudoConstant::contributionPage();
    $this->_instruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $this->_contributionType = CRM_Contribute_PseudoConstant::contributionType();
    $this->_config = CRM_Core_Config::singleton();
    $this->buildColumn();
    if (!empty($formValues)) {
      foreach ($formValues as $k => $v) {
        if (preg_match('/^status\[(\d)\]/i', $k, $matches)) {
          $formValues['status'][$matches[1]] = $matches[1];
        }
      }
    }
  }

  /**
   * Build query columns and display columns.
   *
   * @return void
   */
  public function buildColumn() {
    $this->_queryColumns = [
      'contact.id' => 'id',
      'c.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'c.min_receive_date' => 'receive_date',
      'ROUND(c.total_amount,0)' => 'amount',
      'c.contribution_recur_id' => 'contribution_recur_id',
      'c.contribution_page_id' => 'contribution_page_id',
      'c.payment_instrument_id' => 'instrument_id',
      'c.contribution_type_id' => 'contribution_type_id',
    ];
    $this->_columns = [
      ts('Contact ID') => 'id',
      ts('Name') => 'sort_name',
      ts('First Amount') => 'amount',
      ts('Contribution Page') => 'contribution_page_id',
      ts('Recurring Contribution') => 'contribution_recur_id',
      ts('Payment Instrument') => 'instrument_id',
      ts('Contribution Type') => 'contribution_type_id',
      ts('Created Date') => 'receive_date',
    ];
  }

  /**
   * Create the temporary table for storing search results.
   *
   * @return void
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
      if ($field == 'amount') {
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
   *
   * @return void
   */
  public function dropTempTable() {
    $sql = "DROP TEMPORARY TABLE IF EXISTS `{$this->_tableName}`" ;
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Fill temp table for further use.
   *
   * Builds the temp table, runs the query and inserts matching rows.
   *
   * @return void
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

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY contact.id
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
   * Build the FROM clause for the temp table query.
   *
   * @return string
   *   SQL FROM clause.
   */
  public function tempFrom() {
    $sub_where_clauses = [];
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
   * Build the WHERE clause for the temp table query.
   *
   * Filters by receive date range based on form values.
   *
   * @return string
   *   SQL WHERE clause.
   */
  public function tempWhere() {
    $clauses = [];
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

  /**
   * Build the search form.
   *
   * @param CRM_Core_Form $form
   *   The form object.
   *
   * @return void
   */
  public function buildForm(&$form) {
    // Define the search form fields here

    $form->addDateRange('receive_date', ts('Receive Date').' - '.ts('From'), NULL, FALSE);

    $recurring = $form->addRadio('recurring', ts('Recurring Contribution'), $this->_recurringStatus);
    $form->addSelect('contribution_page_id', ts('Contribution Page'), ['' => ts('- select -')] + $this->_contributionPage);

    $form->assign('elements', ['receive_date', 'recurring', 'contribution_page_id']);
  }

  /**
   * Set default form values.
   *
   * @return array
   *   Default values keyed by form element name.
   */
  public function setDefaultValues() {
    return [
      'receive_date_from' => date('Y-m-01', time() - 86400 * 90),
      'recurring' => 2,
    ];
  }

  /**
   * Get the qill (query detail) for display.
   *
   * @return array<int, array<string, string>>
   *   Qill array describing the active search criteria.
   */
  public function qill() {
    $qill = [];
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

  /**
   * Set the breadcrumb for this search.
   *
   * @return void
   */
  public function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
  }

  /**
   * Get the count of matching records.
   *
   * @return int
   *   Number of matching records.
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
   *   Starting row offset.
   * @param int $rowcount
   *   Number of rows to return.
   * @param string|CRM_Utils_Sort|null $sort
   *   Sort clause or object.
   * @param bool $includeContactIDs
   *   Whether to include selected contact IDs in the WHERE clause.
   * @param bool $onlyIDs
   *   If TRUE, return only contact IDs.
   *
   * @return string
   *   SQL query string.
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
   * Build the full SQL query.
   *
   * @param string $selectClause
   *   The SELECT clause.
   * @param int $offset
   *   Starting row offset.
   * @param int $rowcount
   *   Number of rows to return.
   * @param string|CRM_Utils_Sort|null $sort
   *   Sort clause or object.
   * @param bool $includeContactIDs
   *   Whether to include selected contact IDs.
   * @param string|null $groupBy
   *   Optional GROUP BY clause.
   *
   * @return string
   *   SQL query string.
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
   * Build the FROM clause.
   *
   * @return string
   *   SQL FROM clause.
   */
  public function from() {
    return "FROM {$this->_tableName} contact_a";
  }

  /**
   * Build the WHERE clause.
   *
   * Applies date range, recurring and contribution page filters.
   *
   * @param bool $includeContactIDs
   *   Whether to include selected contact IDs.
   *
   * @return string
   *   SQL WHERE clause.
   */
  public function where($includeContactIDs = FALSE) {
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

  /**
   * Get the HAVING clause.
   *
   * @return string
   *   Empty string (no HAVING clause).
   */
  public function having() {
    return '';
  }

  /**
   * Append selected contact IDs to the SQL WHERE clause.
   *
   * @param string $sql
   *   SQL string to modify (passed by reference).
   * @param array $formValues
   *   Submitted form values (passed by reference).
   * @param bool $isExport
   *   Whether this is an export context.
   *
   * @return void
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
   * Get the column headers for search results.
   *
   * @return array
   *   Column headers keyed by label => field name.
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * Get summary information for the search results.
   *
   * @return array|null
   *   Summary data including total amount and average.
   */
  public function summary() {
    if (!$this->_filled) {
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
      $summary['search_results'] = [
        'label' => ts('Search Results'),
        'value' => '',
      ];
      $amount_sum = '$'.CRM_Utils_Money::format($query->amount_sum, ' ');
      $amount_avg = '$'.CRM_Utils_Money::format($query->amount_sum / $count, ' ');
      $summary['search_results']['value'] = ts('Total amount of completed contributions is %1.', [1 => $amount_sum]).' / '.ts('for')." ".$count." ".ts('People').' / '.ts('Average').": ".$amount_avg;
    }

    return $summary;
  }

  /**
   * Modify a result row before display.
   *
   * Formats amounts, resolves IDs to labels, and adds links.
   *
   * @param array $row
   *   The result row (passed by reference).
   *
   * @return void
   */
  public function alterRow(&$row) {
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
        $row['contribution_recur_id'] = "<a href='".CRM_Utils_System::url('civicrm/contact/view/contributionrecur', "reset=1&id={$recurId}&cid={$contactId}")."' target='_blank'>".ts("Recurring contributions")."</a>";
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
   *
   * @return string
   *   Template file path.
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/FirstTimeDonor.tpl';
  }

  /**
   * Get contact IDs matching the search criteria.
   *
   * @param int $offset
   *   Starting row offset.
   * @param int $rowcount
   *   Number of rows to return.
   * @param string|CRM_Utils_Sort|null $sort
   *   Sort clause or object.
   *
   * @return string
   *   SQL query returning contact IDs.
   */
  public function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return $this->all($offset, $rowcount, $sort, FALSE, TRUE);
  }
}
