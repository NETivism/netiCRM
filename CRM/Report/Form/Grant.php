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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */



class CRM_Report_Form_Grant extends CRM_Report_Form {

  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $_from;
  public $_aliases;
  /**
   * @var string
   */
  public $_where;
  public $_groupBy;
  protected $_addressField = FALSE;

  protected $_customGroupExtends = ['Grant']; function __construct() {
    $this->_columns = [
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'filters' =>
        ['display_name' =>
          ['title' => ts('Contact Name'),
            'operator' => 'like',
          ],
          'gender_id' =>
          ['title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::gender(),
          ],
        ],
      ],
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'filters' =>
        ['country_id' =>
          ['title' => ts('Country'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ],
          'state_province_id' =>
          ['title' => ts('State/Province'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ],
        ],
      ],
      'civicrm_grant' =>
      ['dao' => 'CRM_Grant_DAO_Grant',
        'fields' =>
        [
          'grant_type_id' =>
          [
            'name' => 'grant_type_id',
            'title' => ts('Grant Type'),
          ],
          'status_id' =>
          [
            'name' => 'status_id',
            'title' => ts('Grant Status'),
          ],
          'amount_requested' =>
          [
            'name' => 'amount_requested',
            'title' => ts('Amount Requested'),
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'amount_granted' =>
          [
            'name' => 'amount_granted',
            'title' => ts('Amount Granted'),
          ],
          'application_received_date' =>
          [
            'name' => 'application_received_date',
            'title' => ts('Application Received Date'),
            'default' => TRUE,
          ],
          'money_transfer_date' =>
          [
            'name' => 'money_transfer_date',
            'title' => ts('Money Transfer Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'grant_due_date' =>
          [
            'name' => 'grant_due_date',
            'title' => ts('Grant Due Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'rationale' =>
          [
            'name' => 'rationale',
            'title' => ts('Rationale'),
          ],
          'grant_report_received' =>
          [
            'name' => 'grant_report_received',
            'title' => ts('Grant Report Received'),
          ],
        ],
        'filters' =>
        ['grant_type' =>
          [
            'name' => 'grant_type_id',
            'title' => ts('Grant Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Grant_PseudoConstant::grantType(),
          ],
          'status_id' =>
          [
            'name' => 'status_id',
            'title' => ts('Grant Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Grant_PseudoConstant::grantStatus(),
          ],
          'amount_granted' =>
          [
            'title' => ts('Amount Granted'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'amount_requested' =>
          [
            'title' => ts('Amount Requested'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'application_received_date' =>
          [
            'title' => ts('Application Received Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'money_transfer_date' =>
          [
            'title' => ts('Money Transfer Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'grant_due_date' =>
          [
            'title' => ts('Grant Due Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'group_bys' =>
        [
          'grant_type_id' =>
          [
            'title' => ts('Grant Type'),
          ],
          'status_id' =>
          [
            'title' => ts('Grant Status'),
          ],
          'amount_requested' =>
          [
            'title' => ts('Amount Requested'),
          ],
          'amount_granted' =>
          [
            'title' => ts('Amount Granted'),
          ],
          'application_received_date' =>
          [
            'title' => ts('Application Received Date'),
          ],
          'money_transfer_date' =>
          [
            'title' => ts('Money Transfer Date'),
          ],
        ],
      ],
    ];

    parent::__construct();
  }

  function select() {
    $select = [];

    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if ($tableName == 'civicrm_address') {
        $this->_addressField = TRUE;
      }
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function from() {
    $this->_from = "
        FROM civicrm_grant {$this->_aliases['civicrm_grant']}
                        LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
                    ON ({$this->_aliases['civicrm_grant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  ) ";
    if ($this->_addressField) {
      $this->_from .= "
                  LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                         ON {$this->_aliases['civicrm_contact']}.id = 
                            {$this->_aliases['civicrm_address']}.contact_id AND 
                            {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }

  function where() {
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {

          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
            $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
          }
        }
      }
    }
  }

  function groupBy() {
    $this->_groupBy = "";
    if (CRM_Utils_Array::value('group_bys', $this->_params) &&
      is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
              $this->_groupBy[] = $field['dbAlias'];
            }
          }
        }
      }
    }
    if (!empty($this->_groupBy)) {
      $this->_groupBy = "ORDER BY " . CRM_Utils_Array::implode(', ', $this->_groupBy) . ", {$this->_aliases['civicrm_contact']}.sort_name";
    }
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if (CRM_Utils_Array::arrayKeyExists('civicrm_grant_grant_type_id', $row)) {
        if ($value = $row['civicrm_grant_grant_type_id']) {
          $rows[$rowNum]['civicrm_grant_grant_type_id'] = CRM_Grant_PseudoConstant::grantType($value);
        }
        $entryFound = TRUE;
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_grant_status_id', $row)) {
        if ($value = $row['civicrm_grant_status_id']) {
          $rows[$rowNum]['civicrm_grant_status_id'] = CRM_Grant_PseudoConstant::grantStatus($value);
        }
        $entryFound = TRUE;
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_grant_grant_report_received', $row)) {
        if ($value = $row['civicrm_grant_grant_report_received']) {
          if ($value == 1) {
            $value = 'Yes';
          }
          else {
            $value = 'No';
          }
          $rows[$rowNum]['civicrm_grant_grant_report_received'] = $value;
        }
        $entryFound = TRUE;
      }
      if (!$entryFound) {
        break;
      }
    }
  }
}

