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


class CRM_Report_Form_Case_TimeSpent extends CRM_Report_Form {
  public $activityTypes;
  /**
   * @var mixed[]
   */
  public $activityStatuses;
  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $has_grouping;
  public $has_activity_type;
  /**
   * @var string
   */
  public $_from;
  public $_where;
  public $_aliases;
  public $_groupBy;
  function __construct() {

    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    asort($this->activityTypes);
    $this->activityStatuses = CRM_Core_PseudoConstant::activityStatus();

    $this->_columns = [
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        [
          'id' =>
          ['title' => ts('Contact ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'display_name' =>
          ['title' => ts('Display Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
        ],
      ],
      'civicrm_activity' =>
      ['dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        ['source_contact_id' =>
          ['title' => ts('Contact ID'),
            'default' => TRUE,
            'no_display' => TRUE,
          ],
          'activity_type_id' =>
          ['title' => ts('Activity Type'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'activity_date_time' =>
          ['title' => ts('Activity Date'),
            'default' => TRUE,
          ],
          'status_id' =>
          ['title' => ts('Activity Status'),
            'default' => FALSE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'id' =>
          ['title' => ts('Activity ID'),
            'default' => TRUE,
          ],
          'duration' =>
          ['title' => ts('Duration'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_INT,
          ],
          'subject' =>
          ['title' => ts('Activity Subject'),
            'default' => FALSE,
          ],
        ],
        'filters' =>
        ['activity_date_time' =>
            //'default'      => 'this.month',
          [
            'operatorType' => CRM_Report_Form::OP_DATE],
          'subject' =>
          ['title' => ts('Activity Subject'),
            'operator' => 'like',
          ],
          'activity_type_id' =>
          ['title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityTypes,
          ],
          'status_id' =>
          ['title' => ts('Activity Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->activityStatuses,
          ],
        ],
        'group_bys' =>
        ['source_contact_id' =>
          ['title' => ts('Totals Only'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_case_activity' =>
      ['dao' => 'CRM_Case_DAO_CaseActivity',
        'fields' =>
        [
          'case_id' =>
          ['title' => ts('Case ID'),
            'default' => FALSE,
          ],
        ],
        'filters' =>
        ['case_id_filter' =>
          ['name' => 'case_id',
            'title' => ts('Cases?'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [1 => ts('Exclude non-case'), 2 => ts('Exclude cases'), 3 => ts('Include Both')],
            'default' => 3,
          ],
        ],
      ],
    ];

    parent::__construct();
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];

    $this->has_grouping = !empty($this->_params['group_bys']);
    $this->has_activity_type = FALSE;

    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            (CRM_Utils_Array::value($fieldName, $this->_params['fields'])
              && ((!$this->has_grouping) || !in_array($fieldName, ['case_id', 'subject', 'status_id']))
            )
          ) {

            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'];

            if ($fieldName == 'activity_type_id') {
              $this->has_activity_type = TRUE;
            }

            if ($fieldName == 'duration' && $this->has_grouping) {
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";
            }
            elseif ($fieldName == 'activity_date_time' && $this->has_grouping) {
              $select[] = "EXTRACT(YEAR_MONTH FROM {$field['dbAlias']}) AS {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = ts('Month/Year');
            }
            elseif ($tableName == 'civicrm_activity' && $fieldName == 'id' && $this->has_grouping) {
              $select[] = "COUNT({$field['dbAlias']}) AS {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = ts('# Activities');
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}
        
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON {$this->_aliases['civicrm_activity']}.source_contact_id = {$this->_aliases['civicrm_contact']}.id 
             LEFT JOIN civicrm_case_activity {$this->_aliases['civicrm_case_activity']}
                    ON {$this->_aliases['civicrm_case_activity']}.activity_id = {$this->_aliases['civicrm_activity']}.id
";
  }

  function where() {
    $this->_where = " WHERE {$this->_aliases['civicrm_activity']}.is_current_revision = 1 AND 
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_test = 0";
    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($field['type'] & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              // handle special case
              if ($fieldName == 'case_id_filter') {
                $choice = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                if ($choice == 1) {
                  $clause = "({$this->_aliases['civicrm_case_activity']}.id Is Not Null)";
                }
                elseif ($choice == 2) {
                  $clause = "({$this->_aliases['civicrm_case_activity']}.id Is Null)";
                }
              }
              else {
                $clause = $this->whereClause($field,
                  $op,
                  CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                );
              }
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where .= " ";
    }
    else {
      $this->_where .= " AND " . CRM_Utils_Array::implode(' AND ', $clauses);
    }
  }

  function groupBy() {
    $this->_groupBy = '';
    if ($this->has_grouping) {
      $this->_groupBy = "
GROUP BY {$this->_aliases['civicrm_contact']}.id,
";
      $this->_groupBy .= ($this->has_activity_type) ? "{$this->_aliases['civicrm_activity']}.activity_type_id, " : "";
      $this->_groupBy .= "civicrm_activity_activity_date_time
";
    }
  }

  function postProcess() {
    parent::postProcess();
  }

  static function formRule($fields, $files, $self) {
    $errors = [];
    if (!empty($fields['group_bys']) &&
      (!CRM_Utils_Array::arrayKeyExists('id', $fields['fields']) || !CRM_Utils_Array::arrayKeyExists('activity_date_time', $fields['fields']) || !CRM_Utils_Array::arrayKeyExists('duration', $fields['fields']))
    ) {
      $errors['fields'] = ts('To view totals please select all of activity id, date and duration.');
    }
    //        CRM_Core_Error::debug('xx', print_r($fields, true));
    return $errors;
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_activity_type_id', $row)) {
        $entryFound = TRUE;
        if ($value = $row['civicrm_activity_activity_type_id']) {
          $rows[$rowNum]['civicrm_activity_activity_type_id'] = $this->activityTypes[$value];
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_status_id', $row)) {
        $entryFound = TRUE;
        if ($value = $row['civicrm_activity_status_id']) {
          $rows[$rowNum]['civicrm_activity_status_id'] = $this->activityStatuses[$value];
        }
      }

      // The next two make it easier to make pivot tables after exporting to Excel
      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_duration', $row)) {
        $entryFound = TRUE;
        if ($row['civicrm_activity_duration'] == '') {
          $rows[$rowNum]['civicrm_activity_duration'] = '0';
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_case_activity_case_id', $row)) {
        $entryFound = TRUE;
        if ($row['civicrm_case_activity_case_id'] == '') {
          $rows[$rowNum]['civicrm_case_activity_case_id'] = '0';
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}

