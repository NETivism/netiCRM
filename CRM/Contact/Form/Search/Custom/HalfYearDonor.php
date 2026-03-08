<?php

class CRM_Contact_Form_Search_Custom_HalfYearDonor extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  public $_queryColumns;
  /**
   * @var mixed
   */
  public $_count;
  protected $_formValues;
  protected $_cstatus = NULL;
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
    $this->_filled = FALSE;
    $this->_tableName = 'civicrm_temp_custom_halfyeardonor';
    $this->_config = CRM_Core_Config::singleton();
    $this->buildColumn();
  }

  /**
   * Build the columns for the search results.
   */
  public function buildColumn() {
    $this->_queryColumns = [
      'contact.id' => 'id',
      'c.contact_id' => 'contact_id',
      'contact.sort_name' => 'sort_name',
      'c.receive_date' => 'receive_date',
      'ROUND(SUM(IF(c.contribution_status_id = 1, c.total_amount, 0)),0)' => 'receive_amount',
      'COUNT(IF(c.contribution_status_id = 1, 1, NULL))' => 'completed_count',
      'COUNT(c.id)' => 'total_count',
    ];
    $this->_columns = [
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Total Receive Amount') => 'receive_amount',
      ts('Completed Donation') => 'completed_count',
      0 => 'total_count',
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
      if ($field == 'receive_amount' || $field == 'completed_count' || $field == 'total_count') {
        $type = "INTEGER(10) default NULL";
      }
      elseif (strstr($field, '_date')) {
        $type = 'DATETIME NULL default NULL';
      }
      else {
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

  /**
   * Drop the temporary table.
   */
  public function dropTempTable() {
    $sql = "DROP TEMPORARY TABLE IF EXISTS `{$this->_tableName}`" ;
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Fill the temp table for further use.
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
    return "civicrm_contact AS contact INNER JOIN civicrm_contribution c ON c.contact_id = contact.id AND c.is_test = 0 LEFT JOIN civicrm_membership_payment mp ON mp.contribution_id = c.id LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = c.id";
  }

  /**
   * Get the WHERE clause for the temporary table query.
   *
   * This is an array built from any required JOINS plus conditional
   * filters based on search criteria field values.
   *
   * @return string
   */
  public function tempWhere() {
    $month = $this->_formValues['month'];
    $halfyear = date('Y-m-01 00:00:00', strtotime('-'.$month.' month'));
    $clauses = [];
    $clauses[] = "contact.is_deleted = 0 AND pp.id IS NULL AND mp.id IS NULL";
    $clauses[] = "c.receive_date > '$halfyear'";
    $clauses[] = "c.contribution_status_id = 1";

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  /**
   * Get the HAVING clause for the temporary table query.
   *
   * @return string
   */
  public function tempHaving() {
    return '';
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
    for ($i = 1; $i <= 12; $i++) {
      $option[$i] = $i;
    }
    $form->addSelect('month', ts('month'), $option);
  }

  /**
   * Set the default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    return [
      'month' => 6,
    ];
  }

  /**
   * Set the breadcrumb for the search page.
   */
  public function setBreadcrumb() {
    CRM_Contribute_Page_Booster::setBreadcrumb();
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
    $this->_count = $dao->N;
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
   * Get the FROM clause for the main query.
   *
   * @return string
   */
  public function from() {
    return "FROM {$this->_tableName} contact_a";
  }

  /**
   * Get the WHERE clause for the main query.
   *
   * @param bool $includeContactIDs
   *
   * @return string
   */
  public function where($includeContactIDs = FALSE) {
    return '(1)';
  }

  /**
   * Get the HAVING clause for the main query.
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
   * Set the page title.
   */
  public function setTitle() {
    $month = $this->_formValues['month'];
    $title = ts('Donor who donate in last %count month', ['count' => $month, 'plural' => 'Donor who donate in last %count months']);
    CRM_Utils_System::setTitle($title);
  }

  /**
   * Get data for the 'qill' display.
   *
   * @return array
   */
  public function qill() {
    // just add qill
    $month = $this->_formValues['month'];
    $past = date('Y-m-01', strtotime('-'.$month.' month'));
    return [
      1 => [
        'monthrange' => ts('Donor who donate in last %count month', ['count' => $month, 'plural' => 'Donor who donate in last %count months']). ' ( '.$past.' ~ '.ts('Today').')'
      ],
    ];
  }

  /**
   * Alter a single result row.
   *
   * @param array $row
   */
  public function alterRow(&$row) {
  }

  /**
   * Get the path to the template file.
   *
   * Define the smarty template used to layout the search form and results listings.
   *
   * @return string
   */
  public function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/HalfYearDonor.tpl';
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
