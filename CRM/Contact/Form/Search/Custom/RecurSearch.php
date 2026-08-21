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
 * Custom search form for searching recurring contributions
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Contact_Form_Search_Custom_RecurSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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
  protected $_contributionStatuses = [];
  protected $_queryParams = [];

  public static $_primaryIDName = 'id';

  /**
   * Class constructor.
   *
   * @param array $formValues
   */
  public function __construct(&$formValues) {
    parent::__construct($formValues);
    $this->_filled = FALSE;
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $form);
    if (empty($this->_tableName)) {
      $this->_tableName = "civicrm_temp_custom_recursearch";
      $this->_cpage = CRM_Contribute_PseudoConstant::contributionPage();
      $this->_contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus();
      $this->_cstatus = $this->_contributionStatuses;
      $this->_cstatus[1] = ts('Recurring ended');
      $this->_gender = CRM_Core_PseudoConstant::gender();
      $this->_config = CRM_Core_Config::singleton();
      $this->buildColumn();
    }

    $lowDate = CRM_Utils_Request::retrieve(
      'start',
      'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($lowDate) {
      $lowDate = CRM_Utils_Type::escape($lowDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($lowDate);
      $this->_formValues['start_date_from'] = $date[0];
    }

    $highDate = CRM_Utils_Request::retrieve(
      'end',
      'Timestamp',
      CRM_Core_DAO::$_nullObject
    );
    if ($highDate) {
      $highDate = CRM_Utils_Type::escape($highDate, 'Timestamp');
      $date = CRM_Utils_Date::setDateDefaults($highDate);
      $this->_formValues['start_date_to'] = $date[0];
    }
    $requestStatus = CRM_Utils_Array::value('status', $this->_formValues);
    $cstatus_id = NULL;
    if (!is_array($requestStatus)) {
      $cstatus_id = CRM_Utils_Request::retrieve('status', 'Int', CRM_Core_DAO::$_nullObject);
    }
    if (is_array($requestStatus)) {
      $this->_formValues['status'] = $requestStatus;
    }
    elseif (!empty($cstatus_id)) {
      $this->_formValues['status'] = [$cstatus_id => $cstatus_id];
    }
    elseif (is_numeric(CRM_Utils_Array::value('status', $this->_formValues))) {
      $selectedStatus = (int) $this->_formValues['status'];
      $this->_formValues['status'] = [$selectedStatus => $selectedStatus];
    }
  }

  /**
   * Build the columns for the search results.
   */
  public function buildColumn() {
    $this->_queryColumns = [
      'r.id' => 'id',
      'contact.sort_name' => 'sort_name',
      'r.contact_id' => 'contact_id',
      'contact_email.email' => 'email',
      'ROUND(r.amount,0)' => 'amount',
      'payment_processor.name' => 'payment_processor',
      'r.cycle_day' => 'cycle_day',
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
    if ($this->hasAuditDateRange()) {
      $this->_queryColumns += [
        'r.frequency_unit' => 'frequency_unit',
        'r.last_execute_date' => 'last_execute_date',
        'audit.audit_status_ids' => 'audit_status_ids',
        'audit.audit_count' => 'audit_count',
        '0' => 'audit_not_executed',
      ];
    }
    $this->_columns = [
      ts('ID') => 'id',
      ts('Name') => 'sort_name',
      ts('Agreed Amount') => 'amount',
      ts('Payment Processor') => 'payment_processor',
      ts('Recurring Donation Day') => 'cycle_day',
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
      1 => 'total_count',
    ];
    if ($this->hasAuditDateRange()) {
      $this->_columns[ts('Audit Period Debit Status')] = 'audit_status_ids';
    }
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
      if (in_array($field, ['payment_processor', 'audit_status_ids'])) {
        $type = $field === 'payment_processor'
          ? "VARCHAR(255) default ''"
          : "VARCHAR(32) default ''";
      }
      elseif (
        in_array($field, ['remain_installments', 'cycle_day', 'audit_count', 'audit_not_executed']) ||
        strstr($field, 'amount') ||
        strstr($field, '_id')
      ) {
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
    $this->dropTempTable();
    $this->buildTempTable();
    $this->_queryParams = [];

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
GROUP BY r.id
$having
";
    $loggedSql = CRM_Core_DAO::composeQuery($sql, $this->_queryParams, TRUE);
    CRM_Core_Error::debug_log_message("[RecurSearch] Search query:\n$loggedSql");
    $dao = CRM_Core_DAO::executeQuery($sql, $this->_queryParams);

    while ($dao->fetch()) {
      if ($this->hasAuditDateRange()) {
        $dao->audit_not_executed = empty($dao->audit_count) && $this->isScheduledInAuditRange($dao) ? 1 : 0;
      }
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
    $from = "civicrm_contribution_recur AS r
    INNER JOIN civicrm_contribution AS c ON c.contribution_recur_id = r.id
    INNER JOIN civicrm_contact AS contact ON contact.id = r.contact_id
    LEFT JOIN civicrm_payment_processor AS payment_processor ON payment_processor.id = r.processor_id
    LEFT JOIN (SELECT contact_id, email, is_primary FROM civicrm_email WHERE is_primary = 1 GROUP BY contact_id ) AS contact_email ON contact_email.contact_id = r.contact_id
    LEFT JOIN (SELECT contribution_recur_id AS rid, MAX(receive_date) AS last_receive_date FROM civicrm_contribution WHERE contribution_status_id = 1 AND contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) lrd ON lrd.rid = r.id
    LEFT JOIN (SELECT contribution_recur_id AS rid, MAX(cancel_date) AS last_failed_date FROM civicrm_contribution WHERE contribution_status_id = 4 AND contribution_recur_id IS NOT NULL GROUP BY contribution_recur_id) lfd ON lfd.rid = r.id";

    $auditDateRange = $this->getAuditDateRange();
    if ($auditDateRange) {
      $dateFrom = $this->addQueryParam($auditDateRange['from'], 'Timestamp');
      $dateTo = $this->addQueryParam($auditDateRange['to'], 'Timestamp');
      $from .= "
    LEFT JOIN (
      SELECT
        contribution_recur_id AS rid,
        GROUP_CONCAT(DISTINCT contribution_status_id ORDER BY contribution_status_id SEPARATOR ',') AS audit_status_ids,
        COUNT(id) AS audit_count
      FROM civicrm_contribution
      WHERE created_date >= $dateFrom AND created_date <= $dateTo
      GROUP BY contribution_recur_id
    ) audit ON audit.rid = r.id";
    }

    return $from;
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
    $clauses[] = "(r.contact_id = contact.id)";
    $clauses[] = "(r.is_test = 0)";

    $startDateFrom = CRM_Utils_Date::processDate($this->_formValues['start_date_from']);
    if ($startDateFrom) {
      $clauses[] = "(r.start_date >= '$startDateFrom')";
    }
    $startDateTo = $this->_formValues['start_date_to'];
    if ($startDateTo) {
      $startDateTo = CRM_Utils_Date::processDate($startDateTo . ' 23:59:59');
      if ($startDateTo) {
        $clauses[] = "(r.start_date <= '$startDateTo')";
      }
    }

    $auditDateRange = $this->getAuditDateRange();
    if ($auditDateRange) {
      $auditDateTo = $this->addQueryParam($auditDateRange['to'], 'Timestamp');
      $clauses[] = "(r.start_date <= $auditDateTo)";
    }

    $recurringStatuses = $this->getSelectedRecurringStatuses();
    if ($recurringStatuses) {
      $statusParams = [];
      foreach ($recurringStatuses as $statusId) {
        $statusParams[] = $this->addQueryParam($statusId, 'Integer');
      }
      $clauses[] = '(r.contribution_status_id IN (' . CRM_Utils_Array::implode(', ', $statusParams) . '))';
    }

    $sort_name = $this->_formValues['sort_name'];
    if ($sort_name) {
      $clauses[] = "(`sort_name` LIKE '%$sort_name%')";
    }

    $email = $this->_formValues['email'];
    if ($email) {
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

    $processorIds = $this->getSelectedProcessorIds();
    if ($processorIds) {
      $processorParams = [];
      foreach ($processorIds as $processorId) {
        $processorParams[] = $this->addQueryParam($processorId, 'Integer');
      }
      $clauses[] = '(r.processor_id IN (' . CRM_Utils_Array::implode(', ', $processorParams) . '))';
    }

    return CRM_Utils_Array::implode(' AND ', $clauses);
  }

  /**
   * Get the HAVING clause for the temporary table query.
   *
   * @return string
   */
  public function tempHaving() {
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
    if ($this->hasAuditDateRange()) {
      $auditClauses = [];
      $selectedAuditStatuses = $this->getSelectedAuditStatuses();
      if ($selectedAuditStatuses) {
        foreach ($selectedAuditStatuses as $statusId) {
          $auditClauses[] = "FIND_IN_SET($statusId, audit.audit_status_ids)";
        }
      }
      else {
        $auditClauses[] = 'COALESCE(audit.audit_count, 0) > 0';
      }
      if (!empty($this->_formValues['audit_not_executed'])) {
        $auditClauses[] = '(COALESCE(audit.audit_count, 0) = 0 AND ' . $this->getAuditScheduleClause() . ')';
      }
      $clauses[] = '(' . CRM_Utils_Array::implode(' OR ', $auditClauses) . ')';
    }
    if (count($clauses)) {
      return CRM_Utils_Array::implode(' AND ', $clauses);
    }
    return '';
  }

  /**
   * Prepare the form.
   *
   * @param CRM_Core_Form $form
   */
  public function prepareForm(&$form) {
    $this->_mode = CRM_Utils_Request::retrieve('mode', 'String', $form);
  }

  /**
   * Build the form object.
   *
   * @param CRM_Core_Form $form
   */
  public function buildForm(&$form) {
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

    $statuses = [];
    foreach ([5, 2, 3, 4, 6, 7, 1] as $key) {
      $statuses[] = $form->createElement(
        'advcheckbox',
        $key,
        NULL,
        $this->_cstatus[$key],
        NULL,
        $key
      );
    }
    $form->addGroup($statuses, 'status', ts('Recurring Status'));

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
    $multipleSelectAttributes = ['multiple' => 'multiple'];
    $form->addElement(
      'select',
      'contribution_page_id',
      ts('Contribution Page'),
      $contributionPage,
      $multipleSelectAttributes
    );

    $paymentProcessors = [];
    foreach (CRM_Core_PseudoConstant::paymentProcessor(TRUE) as $processorId => $processorName) {
      $paymentProcessors[$processorId] = $processorName . ' (' . $processorId . ')';
    }
    $form->addElement(
      'select',
      'processor_id',
      ts('Payment Processor'),
      $paymentProcessors,
      $multipleSelectAttributes
    );

    if ($this->supportsContributionAudit()) {
      $form->addDateRange('audit_date', ts('Audit Date Range'), NULL, FALSE);
      $auditStatuses = [];
      foreach ([1, 2, 3, 4] as $statusId) {
        $auditStatuses[] = $form->createElement(
          'advcheckbox',
          $statusId,
          NULL,
          $this->_contributionStatuses[$statusId]
        );
      }
      $form->addGroup($auditStatuses, 'audit_status_id', ts('Audit Period Debit Status'));
      $form->addElement(
        'checkbox',
        'audit_not_executed',
        ts('Not Executed'),
        ts('No contribution record exists in the audit period')
      );
      $notExecutedInitialized = CRM_Utils_Array::value(
        'audit_not_executed_initialized',
        $this->_formValues,
        0
      );
      $form->addElement(
        'hidden',
        'audit_not_executed_initialized',
        $notExecutedInitialized ? 1 : 0,
        ['id' => 'audit_not_executed_initialized']
      );
      $form->addFormRule(['CRM_Contact_Form_Search_Custom_RecurSearch', 'formRule']);
      $form->assign('recurAuditEnabled', TRUE);
      $form->assign('auditRangeComplete', $this->hasAuditDateRange());
    }

    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign('elements', ['status', 'installments', 'sort_name', 'email', 'contribution_page_id', 'processor_id']);
  }

  /**
   * Validate the contribution audit criteria.
   *
   * @param array $fields
   *
   * @return array|bool
   */
  public static function formRule($fields) {
    $errors = [];
    $dateFrom = CRM_Utils_Array::value('audit_date_from', $fields);
    $dateTo = CRM_Utils_Array::value('audit_date_to', $fields);
    $hasAuditFilter = !empty($fields['audit_not_executed']);
    foreach (CRM_Utils_Array::value('audit_status_id', $fields, []) as $selected) {
      if ($selected) {
        $hasAuditFilter = TRUE;
        break;
      }
    }

    if (($dateFrom && !$dateTo) || (!$dateFrom && $dateTo) || ($hasAuditFilter && (!$dateFrom || !$dateTo))) {
      $errors['audit_date_from'] = ts('Enter both dates for the audit date range.');
    }
    elseif ($dateFrom && $dateTo) {
      $processedFrom = CRM_Utils_Date::processDate($dateFrom);
      $processedTo = CRM_Utils_Date::processDate($dateTo.' 23:59:59');
      $earliestAuditDate = date('Ym01', strtotime('first day of previous month'));
      if (substr($processedFrom, 0, 8) < $earliestAuditDate) {
        $errors['audit_date_from'] = ts('The audit start date cannot be earlier than the first day of the previous month.');
      }
      elseif ($processedFrom > $processedTo) {
        $errors['audit_date_to'] = ts('The audit end date must not be earlier than the start date.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Set the default values for the form.
   *
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if ($this->_mode == 'booster') {
      $defaults += [
        'status' => [5 => 5],
        'installments' => '1',
      ];
    }
    return $defaults;
  }

  /**
   * Set the page title.
   */
  public function setTitle() {
    if ($this->_mode == 'booster') {
      CRM_Utils_System::setTitle(ts('End of recurring contribution'));
    }
    else {
      CRM_Utils_System::setTitle(ts('Custom Search').' - '.ts('Recurring Contribution'));
    }
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
    $value = CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM {$this->_tableName}");
    return $value;
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

  /**
   * Get the SQL for retrieving contact IDs and additional IDs.
   *
   * This will be called by search tasks which not only need contact id,
   * but also provide additional id. Mostly used by custom search
   * supporting multiple records of one contact.
   *
   * @param int $offset
   * @param int $rowcount
   * @param null|string|object $sort
   *
   * @return string
   */
  public function contactAdditionalIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    $fields = "contact_a.contact_id, id" ;

    if (!$this->_filled) {
      $this->fillTable();
      $this->_filled = TRUE;
    }
    return $this->sql($fields, $offset, $rowcount, $sort, FALSE);
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
   * Functions below generally don't need to be modified.
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
    $sql = ' ( 1 ) ';
    if ($includeContactIDs) {
      self::includeContactIDs($sql, $this->_formValues, $this->_isExport);
    }
    return $sql;
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

  /**
   * Getter for the display columns.
   *
   * @return array
   */
  public function &columns() {
    return $this->_columns;
  }

  /**
   * Get a summary of the search results.
   *
   * @return array<string, array<string, mixed>>
   */
  public function summary() {
    $summary = [];
    if (!$this->_filled) {
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

    $searchCriteria = $this->getSearchCriteria();
    if ($searchCriteria) {
      $summary['search_criteria'] = [
        'label' => ts('Search Criteria'),
        'items' => $searchCriteria,
      ];
    }

    if ($this->hasAuditDateRange()) {
      $recurringStatuses = $this->getSelectedRecurringStatuses();
      if (!$recurringStatuses || in_array(5, $recurringStatuses, TRUE)) {
        $inProgressCount = CRM_Core_DAO::singleValueQuery(
          "SELECT COUNT(*) FROM {$this->_tableName} WHERE contribution_status_id = 5"
        );
        $summary['audit_recurring_status'] = [
          'label' => ts('Recurring Status'),
          'items' => [[
            'label' => $this->_cstatus[5],
            'value' => (int) $inProgressCount,
            'status_class' => 'recurring',
          ]],
        ];
      }

      $auditDateRange = $this->getAuditDateRange();
      $auditSummaryParams = [
        1 => [$auditDateRange['from'], 'Timestamp'],
        2 => [$auditDateRange['to'], 'Timestamp'],
      ];
      $auditStatusParams = [];
      foreach ($this->getSelectedAuditStatuses() as $statusId) {
        $paramIndex = count($auditSummaryParams) + 1;
        $auditSummaryParams[$paramIndex] = [$statusId, 'Integer'];
        $auditStatusParams[] = '%' . $paramIndex;
      }
      $auditStatusClause = '';
      if ($auditStatusParams) {
        $auditStatusClause = "
  AND c.contribution_status_id IN (" . CRM_Utils_Array::implode(', ', $auditStatusParams) . ')';
      }
      $auditSummarySql = "
SELECT
  COUNT(IF(c.contribution_status_id = 1, c.id, NULL)) AS completed_count,
  COUNT(IF(c.contribution_status_id = 2, c.id, NULL)) AS pending_count,
  COUNT(IF(c.contribution_status_id = 3, c.id, NULL)) AS cancelled_count,
  COUNT(IF(c.contribution_status_id = 4, c.id, NULL)) AS failed_count,
  COUNT(DISTINCT IF(result.audit_not_executed = 1, result.id, NULL)) AS not_executed_count
FROM {$this->_tableName} AS result
LEFT JOIN civicrm_contribution AS c
  ON c.contribution_recur_id = result.id
  AND c.created_date >= %1
  AND c.created_date <= %2
$auditStatusClause
";
      $loggedAuditSummarySql = CRM_Core_DAO::composeQuery(
        $auditSummarySql,
        $auditSummaryParams,
        TRUE
      );
      CRM_Core_Error::debug_log_message("[RecurSearch] Audit summary query:
$loggedAuditSummarySql");
      $auditSummary = CRM_Core_DAO::executeQuery($auditSummarySql, $auditSummaryParams);
      $auditSummary->fetch();

      $summary['audit_criteria'] = [
        'label' => ts('Applied Recurring Debit Audit Criteria'),
        'items' => $this->getAuditCriteria(),
      ];
      $summary['audit_contribution_status'] = [
        'label' => ts('Audit Period Debit Status'),
        'items' => [
          [
            'label' => $this->_contributionStatuses[1],
            'value' => (int) $auditSummary->completed_count,
            'status_class' => 'completed',
          ],
          [
            'label' => $this->_contributionStatuses[2],
            'value' => (int) $auditSummary->pending_count,
            'status_class' => 'pending',
          ],
          [
            'label' => $this->_contributionStatuses[3],
            'value' => (int) $auditSummary->cancelled_count,
            'status_class' => 'cancelled',
          ],
          [
            'label' => $this->_contributionStatuses[4],
            'value' => (int) $auditSummary->failed_count,
            'status_class' => 'failed',
          ],
        ],
      ];
      if (!empty($this->_formValues['audit_not_executed'])) {
        $summary['audit_not_executed'] = [
          'label' => ts('Not Executed'),
          'items' => [[
            'label' => ts('Recurring Contributions'),
            'value' => (int) $auditSummary->not_executed_count,
            'status_class' => 'not-executed',
          ]],
        ];
      }
    }

    return $summary;
  }

  /**
   * Alter a single result row for display.
   *
   * @param array $row
   */
  public function alterRow(&$row) {
    $row['contribution_status_id'] = $this->_cstatus[$row['contribution_status_id']];
    $processedInstallments = $row['installments'] - $row['remain_installments'];
    if (empty($row['installments'])) {
      $row['remain_installments'] = ts('no limit');
      $row['installments'] = $row['completed_count'].' / '.ts('no limit');
    }
    else {
      $row['installments'] = $processedInstallments.' / '.$row['installments'];
    }
    if ($row['remain_installments'] < 0) {
      $row['remain_installments'] = ts('Over %1', [ 1 => -$row['remain_installments']]);
    }

    if ($row['completed_count']) {
      $row['completed_count'] = $row['completed_count'].' / '.$row['total_count'];
    }
    else {
      $row['completed_count'] = '0 / '.$row['total_count'];
    }
    unset($row['total_count']);

    if (array_key_exists('audit_status_ids', $row)) {
      $auditStatuses = [];
      if (!empty($row['audit_status_ids'])) {
        foreach (explode(',', $row['audit_status_ids']) as $statusId) {
          if (isset($this->_contributionStatuses[$statusId])) {
            $auditStatuses[] = $this->_contributionStatuses[$statusId];
          }
        }
      }
      if (!empty($row['audit_not_executed'])) {
        $auditStatuses[] = ts('Not Executed');
      }
      $row['audit_status_ids'] = CRM_Utils_Array::implode(', ', $auditStatuses);
      unset($row['frequency_unit'], $row['last_execute_date'], $row['audit_count'], $row['audit_not_executed']);
    }

    if (!empty($row['payment_processor']) && empty($this->_isExport)) {
      $row['payment_processor'] = htmlspecialchars($row['payment_processor'], ENT_QUOTES, 'UTF-8');
    }

    if ($row['contribution_page_id'] && empty($this->_isExport)) {
      $params = [
        'p' => 'civicrm/admin/contribute',
        'q' => "action=update&reset=1&id={$row['contribution_page_id']}",
      ];
      $row['contribution_page_id'] = '<a href="'.CRM_Utils_System::crmURL($params).'" title="'. $this->_cpage[$row['contribution_page_id']].'">'. $row['contribution_page_id'].'</a>';
    }

    $date = ['start_date', 'end_date', 'cancel_date'];
    foreach ($date as $d) {
      if (!empty($row[$d])) {
        $row[$d] = CRM_Utils_Date::customFormat($row[$d], $this->_config->dateformatFull);
      }
    }

    $recurLinks = CRM_Contribute_Page_Tab::recurLinks();
    if (!empty($recurLinks) && is_array($recurLinks)) {
      $action = array_sum(array_keys($recurLinks));
      $row['action'] = CRM_Core_Action::formLink(
        $recurLinks,
        $action,
        ['cid' => $row['contact_id'],
          'id' => $row['id'],
          'cxt' => 'contribution',
        ]
      );
    }
    else {
      $row['action'] = '';
    }
    // Refs #38855, Workaround for export error when there are NULL field.
    foreach ($row as $key => $value) {
      if ($value === NULL) {
        $row[$key] = '';
      }
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
    return 'CRM/Contact/Form/Search/Custom/RecurSearch.tpl';
  }

  /**
   * Check whether this search supports contribution auditing.
   *
   * @return bool
   */
  protected function supportsContributionAudit() {
    return get_class($this) === __CLASS__;
  }

  /**
   * Get the complete audit date range in database format.
   *
   * @return array|null
   */
  protected function getAuditDateRange() {
    if (!$this->supportsContributionAudit()) {
      return NULL;
    }
    $dateFrom = CRM_Utils_Array::value('audit_date_from', $this->_formValues);
    $dateTo = CRM_Utils_Array::value('audit_date_to', $this->_formValues);
    if (!$dateFrom || !$dateTo) {
      return NULL;
    }

    return [
      'from' => CRM_Utils_Date::processDate($dateFrom),
      'to' => CRM_Utils_Date::processDate($dateTo.' 23:59:59'),
    ];
  }

  /**
   * Check whether a complete audit date range was submitted.
   *
   * @return bool
   */
  protected function hasAuditDateRange() {
    return (bool) $this->getAuditDateRange();
  }

  /**
   * Get the selected recurring contribution statuses.
   *
   * @return array
   */
  protected function getSelectedRecurringStatuses() {
    $selectedStatuses = CRM_Utils_Array::value('status', $this->_formValues, []);
    if (!is_array($selectedStatuses)) {
      if (!is_numeric($selectedStatuses)) {
        return [];
      }
      $statusId = (int) $selectedStatuses;
      $selectedStatuses = [$statusId => $statusId];
    }

    $statusIds = [];
    foreach ([5, 2, 3, 4, 6, 7, 1] as $statusId) {
      if (!empty($selectedStatuses[$statusId])) {
        $statusIds[] = $statusId;
      }
    }
    return $statusIds;
  }

  /**
   * Get the selected payment processor IDs.
   *
   * @return array
   */
  protected function getSelectedProcessorIds() {
    $selectedProcessors = CRM_Utils_Array::value('processor_id', $this->_formValues, []);
    if (!is_array($selectedProcessors)) {
      $selectedProcessors = [$selectedProcessors];
    }

    $processorIds = [];
    foreach ($selectedProcessors as $processorId) {
      if (is_numeric($processorId) && (int) $processorId > 0) {
        $processorId = (int) $processorId;
        $processorIds[$processorId] = $processorId;
      }
    }
    return array_values($processorIds);
  }

  /**
   * Get the selected contribution statuses supported by the audit.
   *
   * @return array
   */
  protected function getSelectedAuditStatuses() {
    $selectedStatuses = CRM_Utils_Array::value('audit_status_id', $this->_formValues, []);
    $statusIds = [];
    foreach ([1, 2, 3, 4] as $statusId) {
      if (!empty($selectedStatuses[$statusId])) {
        $statusIds[] = $statusId;
      }
    }
    return $statusIds;
  }

  /**
   * Get the applied standard search criteria for display.
   *
   * @return array
   */
  protected function getSearchCriteria() {
    $criteria = [];

    if ($this->_mode !== 'booster') {
      $dateLabels = [
        'start_date_from' => ts('From'),
        'start_date_to' => ts('To'),
      ];
      $dates = [];
      foreach ($dateLabels as $field => $label) {
        $date = CRM_Utils_Array::value($field, $this->_formValues);
        $date = $date ? CRM_Utils_Date::processDate($date) : NULL;
        if ($date) {
          $dates[] = $label . ': ' . $this->normalizeDate($date);
        }
      }
      if ($dates) {
        $criteria[] = [
          'label' => ts('First recurring date'),
          'value' => CRM_Utils_Array::implode(', ', $dates),
        ];
      }
    }

    $statusLabels = [];
    foreach ($this->getSelectedRecurringStatuses() as $statusId) {
      if (isset($this->_cstatus[$statusId])) {
        $statusLabels[] = $this->_cstatus[$statusId];
      }
    }
    if ($statusLabels) {
      $criteria[] = [
        'label' => ts('Recurring Status'),
        'value' => CRM_Utils_Array::implode(', ', $statusLabels),
      ];
    }

    $installments = CRM_Utils_Array::value('installments', $this->_formValues, '');
    if ($installments === 'none' || ($installments !== '' && is_numeric($installments))) {
      if ($installments === 'none') {
        $installments = ts('no installments specified');
      }
      elseif ((int) $installments === 0) {
        $installments = ts('Installments is full.');
      }
      else {
        $installments = ts('%1 installments left', [1 => (int) $installments]);
      }
      $criteria[] = [
        'label' => ts('Installments Left'),
        'value' => $installments,
      ];
    }

    if ($this->_mode !== 'booster') {
      $textFields = [
        'sort_name' => ts('Contact Name'),
        'email' => ts('Email'),
      ];
      foreach ($textFields as $field => $label) {
        $value = CRM_Utils_Array::value($field, $this->_formValues);
        if ($value !== NULL && $value !== '') {
          $criteria[] = ['label' => $label, 'value' => $value];
        }
      }
    }

    $pageLabels = [];
    $pageIds = (array) CRM_Utils_Array::value('contribution_page_id', $this->_formValues, []);
    foreach ($pageIds as $pageId) {
      if ($pageId) {
        $pageLabels[] = isset($this->_cpage[$pageId]) ? $this->_cpage[$pageId] : $pageId;
      }
    }
    if ($pageLabels) {
      $criteria[] = [
        'label' => ts('Contribution Page'),
        'value' => CRM_Utils_Array::implode(', ', $pageLabels),
      ];
    }

    $processorLabels = [];
    $paymentProcessors = CRM_Core_PseudoConstant::paymentProcessor(TRUE);
    foreach ($this->getSelectedProcessorIds() as $processorId) {
      $processorName = isset($paymentProcessors[$processorId])
        ? $paymentProcessors[$processorId] . ' '
        : '';
      $processorLabels[] = $processorName . '(' . $processorId . ')';
    }
    if ($processorLabels) {
      $criteria[] = [
        'label' => ts('Payment Processor'),
        'value' => CRM_Utils_Array::implode(', ', $processorLabels),
      ];
    }

    return $criteria;
  }

  /**
   * Get the applied recurring debit audit criteria for display.
   *
   * @return array
   */
  protected function getAuditCriteria() {
    $auditDateRange = $this->getAuditDateRange();
    if (!$auditDateRange) {
      return [];
    }

    $selectedAuditStatusLabels = [];
    foreach ($this->getSelectedAuditStatuses() as $statusId) {
      $selectedAuditStatusLabels[] = $this->_contributionStatuses[$statusId];
    }
    if (!$selectedAuditStatusLabels) {
      $selectedAuditStatusLabels[] = ts('All');
    }

    return [
      [
        'label' => ts('Audit Date Range'),
        'value' => ts('%1 to %2', [
          1 => $this->normalizeDate($auditDateRange['from']),
          2 => $this->normalizeDate($auditDateRange['to']),
        ]),
      ],
      [
        'label' => ts('Audit Period Debit Status'),
        'value' => CRM_Utils_Array::implode(', ', $selectedAuditStatusLabels),
      ],
      [
        'label' => ts('Not Executed'),
        'value' => !empty($this->_formValues['audit_not_executed'])
          ? ts('Included')
          : ts('Excluded'),
      ],
    ];
  }

  /**
   * Add a safely typed value to the temporary table query parameters.
   *
   * @param mixed $value
   * @param string $type
   *
   * @return string
   */
  protected function addQueryParam($value, $type) {
    $index = count($this->_queryParams) + 1;
    $this->_queryParams[$index] = [$value, $type];
    return '%'.$index;
  }

  /**
   * Build the SQL condition for a monthly debit scheduled in the audit range.
   *
   * Cycle days beyond the end of a month are treated as the final day of that
   * month, matching recurring payment execution behavior.
   *
   * @return string
   */
  protected function getAuditScheduleClause() {
    $auditDateRange = $this->getAuditDateRange();
    if (!$auditDateRange) {
      return '0';
    }

    $dateFrom = $this->addQueryParam($auditDateRange['from'], 'Timestamp');
    $dateTo = $this->addQueryParam($auditDateRange['to'], 'Timestamp');
    $dayAfterLastExecute = "COALESCE(DATE_ADD(DATE(r.last_execute_date), INTERVAL 1 DAY), '1000-01-01')";
    $effectiveStart = "GREATEST(DATE($dateFrom), DATE(r.start_date), $dayAfterLastExecute)";
    $effectiveEnd = "LEAST(DATE($dateTo), COALESCE(DATE(r.end_date), '9999-12-31'), COALESCE(DATE(r.cancel_date), '9999-12-31'))";
    $startMonth = "DATE_FORMAT($effectiveStart, '%Y-%m-01')";
    $scheduledInStartMonth = "DATE_ADD($startMonth, INTERVAL (LEAST(r.cycle_day, DAY(LAST_DAY($effectiveStart))) - 1) DAY)";
    $nextMonth = "DATE_ADD($startMonth, INTERVAL 1 MONTH)";
    $scheduledInNextMonth = "DATE_ADD($nextMonth, INTERVAL (LEAST(r.cycle_day, DAY(LAST_DAY($nextMonth))) - 1) DAY)";

    return "(
      r.frequency_unit = 'month'
      AND r.cycle_day BETWEEN 1 AND 31
      AND $effectiveStart <= $effectiveEnd
      AND (
        $scheduledInStartMonth BETWEEN $effectiveStart AND $effectiveEnd
        OR $scheduledInNextMonth BETWEEN $effectiveStart AND $effectiveEnd
      )
    )";
  }

  /**
   * Normalize a database date to ISO date format.
   *
   * @param string $date
   *
   * @return string
   */
  protected function normalizeDate($date) {
    if (preg_match('/^\d{8}/', $date)) {
      return substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
    }
    return substr($date, 0, 10);
  }

  /**
   * Check whether a result row had a monthly debit scheduled in the audit range.
   *
   * @param object $row
   *
   * @return bool
   */
  protected function isScheduledInAuditRange($row) {
    $auditDateRange = $this->getAuditDateRange();
    if (!$auditDateRange || $row->frequency_unit !== 'month') {
      return FALSE;
    }
    $cycleDay = (int) $row->cycle_day;
    if ($cycleDay < 1 || $cycleDay > 31) {
      return FALSE;
    }

    $effectiveStartDates = [
      $this->normalizeDate($auditDateRange['from']),
      $this->normalizeDate($row->start_date),
    ];
    if (!empty($row->last_execute_date)) {
      $dayAfterLastExecute = new DateTime($this->normalizeDate($row->last_execute_date));
      $dayAfterLastExecute->modify('+1 day');
      $effectiveStartDates[] = $dayAfterLastExecute->format('Y-m-d');
    }
    $effectiveStart = new DateTime(max($effectiveStartDates));
    $effectiveEndDates = [$this->normalizeDate($auditDateRange['to'])];
    foreach (['end_date', 'cancel_date'] as $fieldName) {
      if (!empty($row->$fieldName)) {
        $effectiveEndDates[] = $this->normalizeDate($row->$fieldName);
      }
    }
    $effectiveEnd = new DateTime(min($effectiveEndDates));
    if ($effectiveStart > $effectiveEnd) {
      return FALSE;
    }

    $month = clone $effectiveStart;
    $month->modify('first day of this month');
    for ($i = 0; $i < 2; $i++) {
      $scheduledDate = clone $month;
      $scheduledDate->setDate(
        (int) $month->format('Y'),
        (int) $month->format('m'),
        min($cycleDay, (int) $month->format('t'))
      );
      if ($scheduledDate >= $effectiveStart && $scheduledDate <= $effectiveEnd) {
        return TRUE;
      }
      $month->modify('first day of next month');
    }
    return FALSE;
  }
}
