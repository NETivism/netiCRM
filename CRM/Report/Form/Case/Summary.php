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



class CRM_Report_Form_Case_Summary extends CRM_Report_Form {

  /**
   * @var mixed[]
   */
  public $case_statuses;
  /**
   * @var mixed[]
   */
  public $rel_types;
  /**
   * @var mixed[]
   */
  public $deleted_labels;
  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $_aliases;
  /**
   * @var string
   */
  public $_from;
  /**
   * @var string
   */
  public $_where;
  /**
   * @var string
   */
  public $_groupBy;
  protected $_summary = NULL;
  protected $_relField = FALSE; function __construct() {
    $this->case_statuses = CRM_Case_PseudoConstant::caseStatus();
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->rel_types[$relid] = $v['label_b_a'];
    }

    $this->deleted_labels = ['' => ts('- select -'), 0 => ts('No'), 1 => ts('Yes')];

    $this->_columns = ['civicrm_c2' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name' =>
          ['title' => ts('Client'),
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_case' =>
      ['dao' => 'CRM_Case_DAO_Case',
        'fields' =>
        ['id' =>
          ['title' => ts('Case ID'),
            'required' => TRUE,
          ],
          'start_date' => ['title' => ts('Start Date'), 'default' => TRUE,
          ],
          'end_date' => ['title' => ts('End Date'), 'default' => TRUE,
          ],
          'status_id' => ['title' => ts('Status'), 'default' => TRUE,
          ],
          'duration' => ['title' => ts('Duration (Days)'), 'default' => FALSE,
          ],
          'is_deleted' => ['title' => ts('Deleted?'), 'default' => FALSE, 'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'filters' =>
        ['start_date' => ['title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => ['title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'status_id' => ['title' => ts('Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_statuses,
          ],
          'is_deleted' => ['title' => ts('Deleted?'),
            'type' => CRM_Report_Form::OP_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->deleted_labels,
            'default' => 0,
          ],
        ],
      ],
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['sort_name' =>
          ['title' => ts('Staff Member'),
            'default' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' => ['title' => ts('Staff Member'),
          ],
        ],
      ],
      'civicrm_relationship' =>
      ['dao' => 'CRM_Contact_DAO_Relationship',
        'filters' =>
        ['relationship_type_id' => ['title' => ts('Staff Relationship'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->rel_types,
          ],
        ],
      ],
      'civicrm_relationship_type' =>
      ['dao' => 'CRM_Contact_DAO_RelationshipType',
        'fields' =>
        ['label_b_a' =>
          ['title' => ts('Relationship'), 'default' => TRUE,
          ],
        ],
      ],
      'civicrm_case_contact' =>
      ['dao' => 'CRM_Case_DAO_CaseContact',
      ],
    ];

    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            if ($tableName == 'civicrm_relationship_type') {
              $this->_relField = TRUE;
            }

            if ($fieldName == 'duration') {
              $select[] = "IF({$table['fields']['end_date']['dbAlias']} Is Null, '', DATEDIFF({$table['fields']['end_date']['dbAlias']}, {$table['fields']['start_date']['dbAlias']})) as {$tableName}_{$fieldName}";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    if (empty($fields['relationship_type_id_value']) && (CRM_Utils_Array::arrayKeyExists('sort_name', $fields['fields']) || CRM_Utils_Array::arrayKeyExists('label_b_a', $fields['fields']))) {
      $errors['fields'] = ts('Either filter on at least one relationship type, or de-select Staff Member and Relationship from the list of fields.');
    }
    if ((!empty($fields['relationship_type_id_value']) || !empty($fields['sort_name_value'])) && (!CRM_Utils_Array::arrayKeyExists('sort_name', $fields['fields']) || !CRM_Utils_Array::arrayKeyExists('label_b_a', $fields['fields']))) {
      $errors['fields'] = ts('To filter on Staff Member or Relationship, please also select Staff Member and Relationship from the list of fields.');
    }
    return $errors;
  }

  function from() {

    $cc = $this->_aliases['civicrm_case'];
    $c = $this->_aliases['civicrm_contact'];
    $c2 = $this->_aliases['civicrm_c2'];
    $cr = $this->_aliases['civicrm_relationship'];
    $crt = $this->_aliases['civicrm_relationship_type'];
    $ccc = $this->_aliases['civicrm_case_contact'];

    if ($this->_relField) {
      $this->_from = "
            FROM civicrm_contact $c 
inner join civicrm_relationship $cr on {$c}.id = {$cr}.contact_id_b
inner join civicrm_case $cc on {$cc}.id = {$cr}.case_id
inner join civicrm_relationship_type $crt on {$crt}.id={$cr}.relationship_type_id
inner join civicrm_case_contact $ccc on {$ccc}.case_id = {$cc}.id
inner join civicrm_contact $c2 on {$c2}.id={$ccc}.contact_id
";
    }
    else {
      $this->_from = "
            FROM civicrm_case $cc
inner join civicrm_case_contact $ccc on {$ccc}.case_id = {$cc}.id
inner join civicrm_contact $c2 on {$c2}.id={$ccc}.contact_id
";
    }
  }

  function where() {
    $clauses = [];
    $this->_having = '';
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if ($field['operatorType'] & CRM_Report_Form::OP_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to,
              CRM_Utils_Array::value('type', $field)
            );
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
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
    }
  }

  function groupBy() {
    $this->_groupBy = "";
  }

  function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      if (CRM_Utils_Array::arrayKeyExists('civicrm_case_status_id', $row)) {
        if ($value = $row['civicrm_case_status_id']) {
          $rows[$rowNum]['civicrm_case_status_id'] = $this->case_statuses[$value];
          $entryFound = TRUE;
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_case_is_deleted', $row)) {
        //                if ( $value = $row['civicrm_case_is_deleted'] ) {
        $value = $row['civicrm_case_is_deleted'];
        $rows[$rowNum]['civicrm_case_is_deleted'] = $this->deleted_labels[$value];
        $entryFound = TRUE;
        //                }
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}

