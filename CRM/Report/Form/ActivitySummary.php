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


class CRM_Report_Form_ActivitySummary extends CRM_Report_Form {

  /**
   * @var array<string, array<'no_display', bool>>
   */
  public $_columnHeaders;
  public $_interval;
  public $_from;
  public $_aliases;
  public $_where;
  /**
   * @var never[]|mixed[]|string[]|string
   */
  public $_groupBy;
  public $_outputMode;
  protected $_emailField = FALSE;
  protected $_phoneField = FALSE; function __construct() {
    $this->_columns = [
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['id' =>
          ['required' => TRUE,
            'no_display' => TRUE,
          ],
          'display_name' =>
          ['title' => ts('Contact Name'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Contact Name'),
          ],
        ],
        'group_bys' =>
        ['display_name' =>
          ['name' => 'id',
            'title' => ts('Contact'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        ['email' =>
          ['title' => 'Email',
            'default' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        ['phone' =>
          ['title' => 'Phone'],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_activity' =>
      ['dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        ['activity_type_id' =>
          ['title' => ts('Activity Type'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'duration' =>
          ['title' => 'Duration',
            'statistics' =>
            [
              'sum' => ts('Total Duration'),
            ],
          ],
          'id' =>
          ['title' => 'Total Activities',
            'required' => TRUE,
            'statistics' =>
            [
              'count' => ts('Activity Count'),
            ],
          ],
        ],
        'filters' =>
        ['activity_date_time' =>
          ['operatorType' => CRM_Report_Form::OP_DATE],
          'activity_type_id' =>
          ['title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityType(),
          ],
          'status_id' =>
          ['title' => ts('Activity Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ],
          'priority_id' =>
          ['title' => ts('Priority'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::priority(),
          ],
        ],
        'group_bys' =>
        [
          'activity_date_time' =>
          ['title' => ts('Activity Date'),
            'frequency' => TRUE,
          ],
          'activity_type_id' =>
          ['title' => ts('Activity Type'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ],
    ];

    parent::__construct();
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {

            switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";

                $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Week';
                break;

              case 'YEAR':
                $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Year';
                break;

              case 'MONTH':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Month';
                break;

              case 'QUARTER':
                $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transfered to rows.
              // since we need that for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = ['no_display' => TRUE];
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = ['no_display' => TRUE];
            }
          }
        }
      }
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            if ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            if (CRM_Utils_Array::value('statistics', $field)) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'count':
                    $select[] = "COUNT(DISTINCT({$field['dbAlias']})) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            elseif ($fieldName == 'activity_type_id') {
              if (!CRM_Utils_Array::value('activity_type_id', $this->_params['group_bys'])) {
                $select[] = "GROUP_CONCAT(DISTINCT {$field['dbAlias']}  ORDER BY {$field['dbAlias']} ) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'];
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'];
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
        
             LEFT JOIN civicrm_activity_target target_activity 
                    ON {$this->_aliases['civicrm_activity']}.id = target_activity.activity_id 
             LEFT JOIN civicrm_activity_assignment assignment_activity
                    ON {$this->_aliases['civicrm_activity']}.id = assignment_activity.activity_id 
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON ({$this->_aliases['civicrm_activity']}.source_contact_id = {$this->_aliases['civicrm_contact']}.id OR
                         target_activity.target_contact_id = {$this->_aliases['civicrm_contact']}.id                      OR
                         assignment_activity.assignee_contact_id = {$this->_aliases['civicrm_contact']}.id )
             {$this->_aclFrom}
             LEFT JOIN civicrm_option_value 
                    ON ( {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value )
             LEFT JOIN civicrm_option_group 
                    ON civicrm_option_group.id = civicrm_option_value.option_group_id
             LEFT JOIN civicrm_case_activity 
                    ON civicrm_case_activity.activity_id = {$this->_aliases['civicrm_activity']}.id
             LEFT JOIN civicrm_case 
                    ON civicrm_case_activity.case_id = civicrm_case.id
             LEFT JOIN civicrm_case_contact 
                    ON civicrm_case_contact.case_id = civicrm_case.id ";

    if ($this->_emailField) {
      $this->_from .= "
            LEFT JOIN civicrm_email  {$this->_aliases['civicrm_email']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                     {$this->_aliases['civicrm_email']}.is_primary = 1 ";
    }

    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone  {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                     {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }
  }

  function where() {
    $this->_where = " WHERE civicrm_option_group.name = 'activity_type' AND 
                                {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($field['type'] & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = [];
    if (is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
              if (CRM_Utils_Array::value('chart', $field)) {
                $this->assign('chartSupported', TRUE);
              }
              if (CRM_Utils_Array::value('frequency', $table['group_bys'][$fieldName]) &&
                CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])
              ) {

                $append = "YEAR({$field['dbAlias']}),";
                if (in_array(strtolower($this->_params['group_bys_freq'][$fieldName]),
                    ['year']
                  )) {
                  $append = '';
                }
                $this->_groupBy[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $this->_groupBy[] = $field['dbAlias'];
              }
            }
          }
        }
      }

      $this->_groupBy = "GROUP BY " . CRM_Utils_Array::implode(', ', $this->_groupBy) . " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id ";
    }
  }

  static function formRule($fields, $files, $self) {
    $errors = [];
    $contactFields = ['display_name', 'email', 'phone'];
    if (CRM_Utils_Array::value('group_bys', $fields)) {

      if (CRM_Utils_Array::value('activity_type_id', $fields['group_bys']) &&
        !CRM_Utils_Array::value('display_name', $fields['group_bys'])
      ) {
        foreach ($fields['fields'] as $fieldName => $val) {
          if (in_array($fieldName, $contactFields)) {
            $errors['fields'] = ts("Please select GroupBy 'Contact' to display Contact Fields");
            break;
          }
        }
      }

      if (CRM_Utils_Array::value('activity_date_time', $fields['group_bys'])) {
        if (CRM_Utils_Array::value('display_name', $fields['group_bys'])) {
          $errors['fields'] = ts("Please do not select GroupBy 'Activity Date' with GroupBy 'Contact'");
        }
        else {
          foreach ($fields['fields'] as $fieldName => $val) {
            if (in_array($fieldName, $contactFields)) {
              $errors['fields'] = ts("Please do not select any Contact Fields with GroupBy 'Activity Date'");
              break;
            }
          }
        }
      }
    }
    return $errors;
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    $flagContact = 0;

    $onHover = ts('View Contact Summary for this Contact');
    foreach ($rows as $rowNum => $row) {

      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) && $this->_outputMode != 'csv') {
        if ($value = $row['civicrm_contact_id']) {

          if ($rowNum == 0) {
            $priviousContact = $value;
          }
          else {
            if ($priviousContact == $value) {
              $flagContact = 1;
              $priviousContact = $value;
            }
            else {
              $flagContact = 0;
              $priviousContact = $value;
            }
          }

          if ($flagContact == 1) {
            $rows[$rowNum]['civicrm_contact_display_name'] = "";

            if (CRM_Utils_Array::arrayKeyExists('civicrm_email_email', $row)) {
              $rows[$rowNum]['civicrm_email_email'] = "";
            }
            if (CRM_Utils_Array::arrayKeyExists('civicrm_phone_phone', $row)) {
              $rows[$rowNum]['civicrm_phone_phone'] = "";
            }
          }
          else {
            $url = CRM_Utils_System::url('civicrm/contact/view',
              'reset=1&cid=' . $value
            );

            $rows[$rowNum]['civicrm_contact_display_name'] = "<a href='$url'>" . $row['civicrm_contact_display_name'] . '</a>';
          }
          $entryFound = TRUE;
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {

          $value = explode(',', $value);
          foreach ($value as $key => $id) {
            $value[$key] = $activityType[$id];
          }

          $rows[$rowNum]['civicrm_activity_activity_type_id'] = CRM_Utils_Array::implode(' , ', $value);
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}

