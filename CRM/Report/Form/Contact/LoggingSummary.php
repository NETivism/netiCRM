<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
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
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Report_Form_Contact_LoggingSummary extends CRM_Report_Form {
  /**
   * Column header definitions keyed by column alias.
   *
   * @var array<string, array<string, mixed>>
   */
  public $_columnHeaders;

  /**
   * The SQL FROM clause built by from().
   *
   * @var string
   */
  public $_from;

  /**
   * The SQL GROUP BY clause built by groupBy().
   *
   * @var string
   */
  public $_groupBy;

  /**
   * The SQL ORDER BY clause built by orderBy().
   *
   * @var string
   */
  public $_orderBy;

  /**
   * The SQL WHERE clause built by where().
   *
   * @var string
   */
  public $_where;

  /**
   * The logging database name (may differ from the main CiviCRM database).
   *
   * @var string
   */
  private $loggingDB;

  /**
   * Resolves the logging database name and initialises column definitions for
   * the log_civicrm_contact table and the civicrm_contact table (for 'altered by').
   * Disables 'Add to Group' support.
   */
  public function __construct() {
    // don’t display the ‘Add these Contacts to Group’ button
    $this->_add2groupSupported = FALSE;

    $dsn = defined('CIVICRM_LOGGING_DSN') ? DB::parseDSN(CIVICRM_LOGGING_DSN) : DB::parseDSN(CIVICRM_DSN);
    $this->loggingDB = $dsn['database'];

    $this->_columns = [
      'log_civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'log_user_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'log_date' => [
            'default' => TRUE,
            'required' => TRUE,
            'title' => ts('When'),
          ],
          'altered_contact' => [
            'default' => TRUE,
            'name' => 'display_name',
            'title' => ts('Altered Contact'),
          ],
          'log_conn_id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'log_action' => [
            'default' => TRUE,
            'title' => ts('Action'),
          ],
        ],
        'filters' => [
          'log_action' => [
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => ['Insert' => ts('Insert'), 'Update' => ts('Update'), 'Delete' => ts('Delete')],
            'title' => ts('Action'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'altered_contact' => [
            'name' => 'display_name',
            'title' => ts('Altered Contact'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'altered_by' => [
            'default' => TRUE,
            'name' => 'display_name',
            'title' => ts('Altered By'),
          ],
        ],
        'filters' => [
          'altered_by' => [
            'name' => 'display_name',
            'title' => ts('Altered By'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ],
    ];
    parent::__construct();
  }

  /**
   * Post-processes result rows to linkify altered contact names (when not deleted) and
   * altered-by contact names. For Update actions, adds a link to the logging detail report.
   * Removes internal log_user_id and log_conn_id columns from each row.
   *
   * @param array &$rows Report result rows passed by reference.
   *
   * @return void
   */
  public function alterDisplay(&$rows) {
    foreach ($rows as &$row) {
      if ($row['log_civicrm_contact_log_action'] != 'Delete') {
        $row['log_civicrm_contact_altered_contact_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $row['log_civicrm_contact_id']);
        $row['log_civicrm_contact_altered_contact_hover'] = ts("Go to contact summary.");
      }
      $row['civicrm_contact_altered_by_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $row['log_civicrm_contact_log_user_id']);
      $row['civicrm_contact_altered_by_hover'] = ts("Go to contact summary.");

      if ($row['log_civicrm_contact_log_action'] == 'Update') {
        $q = "reset=1&log_conn_id={$row['log_civicrm_contact_log_conn_id']}&log_date={$row['log_civicrm_contact_log_date']}";
        $url = CRM_Report_Utils_Report::getNextUrl('logging/contact/detail', $q, FALSE, TRUE);
        $row['log_civicrm_contact_log_action_link'] = $url;
        $row['log_civicrm_contact_log_action_hover'] = ts("View details for this update.");
      }

      unset($row['log_civicrm_contact_log_user_id']);
      unset($row['log_civicrm_contact_log_conn_id']);
    }
  }

  /**
   * Builds the SELECT clause from selected and required fields.
   * Populates $_select and $_columnHeaders.
   *
   * @return void
   */
  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  /**
   * Builds the FROM clause joining the logging database's log_civicrm_contact table
   * to the main civicrm_contact table via log_user_id. Populates $_from.
   *
   * @return void
   */
  public function from() {
    $this->_from = "
            FROM {$this->loggingDB}.log_civicrm_contact {$this->_aliases['log_civicrm_contact']}
            JOIN civicrm_contact     {$this->_aliases['civicrm_contact']}
            ON ({$this->_aliases['log_civicrm_contact']}.log_user_id = {$this->_aliases['civicrm_contact']}.id)
        ";
  }

  /**
   * Builds the GROUP BY clause grouping by connection ID, user ID, and log_date
   * truncated to the minute (DAY_MINUTE) to cluster related changes. Populates $_groupBy.
   *
   * @return void
   */
  public function groupBy() {
    $this->_groupBy = 'GROUP BY log_conn_id, log_user_id, EXTRACT(DAY_MINUTE FROM log_date)';
  }

  /**
   * Builds the ORDER BY clause sorting by log_date descending. Populates $_orderBy.
   *
   * @return void
   */
  public function orderBy() {
    $this->_orderBy = 'ORDER BY log_date DESC';
  }

  /**
   * Delegates to the parent where() then appends a condition to exclude
   * 'Initialization' log actions. Populates $_where.
   *
   * @return void
   */
  public function where() {
    parent::where();
    $this->_where .= " AND (log_action != 'Initialization')";
  }
}
