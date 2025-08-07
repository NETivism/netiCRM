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




class CRM_Report_Form_Case_Detail extends CRM_Report_Form {

  /**
   * @var mixed[]
   */
  public $case_statuses;
  /**
   * @var mixed[]
   */
  public $case_types;
  /**
   * @var mixed[]
   */
  public $rel_types;
  /**
   * @var never[]|\non-empty-array<\mixed, \mixed>
   */
  public $_columnHeaders;
  public $_aliases;
  public $_from;
  public $_where;
  /**
   * @var string
   */
  public $_groupBy;
  protected $_relField = FALSE;

  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_worldRegionField = FALSE;

  protected $_activityField = FALSE; function __construct() {
    $this->case_statuses = CRM_Case_PseudoConstant::caseStatus();
    $this->case_types = CRM_Case_PseudoConstant::caseType();
    $rels = CRM_Core_PseudoConstant::relationshipType();
    foreach ($rels as $relid => $v) {
      $this->rel_types[$relid] = $v['label_b_a'];
    }

    $this->_columns = [
      'civicrm_case' =>
      ['dao' => 'CRM_Case_DAO_Case',
        'fields' =>
        [
          'id' =>
          ['title' => ts('Case ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'subject' =>
          ['title' => ts('Subject'),
            'required' => TRUE,
          ],
          'start_date' =>
          ['title' => ts('Start Date'),
          ],
          'end_date' =>
          ['title' => ts('End Date'),
          ],
          'status_id' =>
          ['title' => ts('Case Status'),
          ],
          'case_type_id' =>
          ['title' => ts('Case Type'),
          ],
        ],
        'filters' =>
        [
          'start_date' =>
          ['title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' =>
          ['title' => ts('End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'status_id' =>
          ['title' => ts('Case Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_statuses,
          ],
          'case_type_id' =>
          ['title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_types,
          ],
        ],
      ],
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        [
          'display_name' =>
          ['title' => ts('Client Name'),
            'required' => TRUE,
          ],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' =>
        [
          'display_name' =>
          ['title' => ts('Client Name'),
          ],
        ],
      ],
      'civicrm_relationship' =>
      ['dao' => 'CRM_Contact_DAO_Relationship',
        'fields' =>
        ['relationship_type_id' =>
          ['title' => ts('Case Role'),
          ],
        ],
        'filters' =>
        ['relationship_type_id' =>
          ['title' => ts('Case Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->rel_types,
          ],
        ],
      ],
      'civicrm_relationship_type' =>
      ['dao' => 'CRM_Contact_DAO_RelationshipType',
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        ['email' =>
          ['title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_phone' =>
      ['dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        ['phone' =>
          ['title' => ts('Phone'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        ['street_address' => NULL,
          'state_province_id' =>
          ['title' => ts('State/Province'),
          ],
          'country_id' =>
          ['title' => ts('Country'),
          ],
        ],
        'grouping' => 'contact-fields',
        'filters' =>
        ['country_id' =>
          ['title' => ts('Country'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::country(),
          ],
          'state_province_id' =>
          ['title' => ts('State/Province'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::stateProvince(),
          ],
        ],
      ],
      'civicrm_worldregion' =>
      ['dao' => 'CRM_Core_DAO_Worldregion',
        'filters' =>
        [
          'worldregion_id' =>
          [
            'name' => 'id',
            'title' => ts('WorldRegion'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::worldRegion(),
          ],
        ],
      ],
      'civicrm_country' =>
      ['dao' => 'CRM_Core_DAO_Country',
      ],
      'civicrm_activity' =>
      ['dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        [
          'activity_subject' =>
          [
            'name' => 'subject',
            'title' => ts('Activity Subject'),
            'no_display' => TRUE,
          ],
        ],
        'filters' =>
        ['activity_date_time' =>
          [
            'title' => ts('Last Action Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
      'civicrm_case_contact' =>
      ['dao' => 'CRM_Case_DAO_CaseContact',
      ],
    ];
    $this->_options = ['my_cases' =>
      ['title' => ts('My Cases'),
        'type' => 'checkbox',
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
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }
            elseif ($tableName == 'civicrm_relationship') {
              $this->_relField = TRUE;
            }
            if ($fieldName == 'display_name') {
              $select[] = "GROUP_CONCAT({$field['dbAlias']}  ORDER BY {$field['dbAlias']} ) 
                                         as {$tableName}_{$fieldName}";
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

  function from() {

    $cc = $this->_aliases['civicrm_case'];
    $c = $this->_aliases['civicrm_contact'];
    $cr = $this->_aliases['civicrm_relationship'];
    $crt = $this->_aliases['civicrm_relationship_type'];
    $ccc = $this->_aliases['civicrm_case_contact'];

    $this->_from = "
             FROM civicrm_case $cc
 LEFT join civicrm_case_contact $ccc on {$ccc}.case_id = {$cc}.id
 LEFT join civicrm_contact $c on {$c}.id={$ccc}.contact_id
 ";
    if ($this->_relField) {
      $this->_from = "
             FROM civicrm_contact $c 
 inner join civicrm_relationship $cr on {$c}.id = {$cr}.contact_id_a
 inner join civicrm_case $cc on {$cc}.id = {$cr}.case_id
 inner join civicrm_relationship_type $crt on {$crt}.id={$cr}.relationship_type_id
 inner join civicrm_case_contact $ccc on {$ccc}.case_id = {$cc}.id
 ";
    }

    if ($this->_addressField) {
      $this->_from .= "
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                    ON $c.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                       {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
    if ($this->_emailField) {
      $this->_from .= " 
             LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                   ON $c.id = {$this->_aliases['civicrm_email']}.contact_id AND 
                       {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
    if ($this->_phoneField) {
      $this->_from .= "
             LEFT JOIN  civicrm_phone {$this->_aliases['civicrm_phone']} 
                       ON ($c.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                          {$this->_aliases['civicrm_phone']}.is_primary = 1)";
    }
    if ($this->_worldRegionField) {
      $this->_from .= "
             LEFT JOIN civicrm_country {$this->_aliases['civicrm_country']}
                   ON {$this->_aliases['civicrm_country']}.id ={$this->_aliases['civicrm_address']}.country_id
             LEFT JOIN civicrm_worldregion {$this->_aliases['civicrm_worldregion']}
                   ON {$this->_aliases['civicrm_country']}.region_id = {$this->_aliases['civicrm_worldregion']}.id ";
    }
    if ($this->_activityField) {
      $this->_from .= "
             LEFT JOIN civicrm_activity {$this->_aliases['civicrm_activity']}
                       ON {$this->_aliases['civicrm_activity']}.source_contact_id = $c.id ";
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
            if ($fieldName == 'activity_date_time' && $this->_params['activity_date_time_relative']) {
              $select = "SELECT LAST_INSERT_ID ({$this->_aliases['civicrm_activity']}.activity_date_time )";
              $orderBy = "ORDER BY {$this->_aliases['civicrm_activity']}.id DESC limit 0,1 ";
              $sql = "{$select} {$this->_from} {$this->_where} {$orderBy}";
              $field['dbAlias'] = date('YmdHis', strtotime(CRM_Core_DAO::singleValueQuery($sql)));
            }

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to,
              CRM_Utils_Array::value('type', $field)
            );
          }
          else {

            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($fieldName == "case_type_id") {
              foreach ($this->_params['case_type_id_value'] as $key => $value) {
                $value = CRM_Case_BAO_Case::VALUE_SEPERATOR . $value . CRM_Case_BAO_Case::VALUE_SEPERATOR;
                $this->_params['case_type_id_value'][$key] = "'{$value}'";
              }
            }
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
      if ($tableName == 'civicrm_activity' && $this->_params['activity_date_time_relative']) {
        $clauses[] = "{$this->_aliases['civicrm_activity']}.id = ( SELECT MAX( civicrm_activity.id) FROM civicrm_activity )";
      }
    }
    if (isset($this->_params['options']['my_cases'])) {
      $session = CRM_Core_Session::singleton();
      $clauses[] = "{$this->_aliases['civicrm_contact']}.id = {$session->get('userID')}";
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
    }
  }

  function groupBy() {
    $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_case']}.id";
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    $select = "select COUNT( DISTINCT( {$this->_aliases['civicrm_address']}.country_id))";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $countryCount = CRM_Core_DAO::singleValueQuery($sql);

    //CaseType statistics
    if (CRM_Utils_Array::arrayKeyExists('filters', $statistics)) {
      foreach ($statistics['filters'] as $id => $value) {
        if ($value['title'] == 'Case Type') {
          $statistics['filters'][$id]['value'] = 'Is ' . $this->case_types[substr($statistics['filters'][$id]
            ['value'], -3, -2
          )];
        }
      }
    }
    $statistics['counts']['case'] = [
      'title' => ts('Total Number of Cases '),
      'value' => isset($statistics['counts']['rowsFound']) ? $statistics['counts']['rowsFound']['value'] : count($rows),
    ];
    $statistics['counts']['country'] = [
      'title' => ts('Total Number of Countries '),
      'value' => $countryCount,
    ];

    return $statistics;
  }

  function postProcess() {

    $this->beginPostProcess();
    if (isset($this->_params['worldregion_id_value']) && !empty($this->_params['worldregion_id_value'])) {
      $this->_addressField = TRUE;
      $this->_worldRegionField = TRUE;
    }
    if ($this->_params['activity_date_time_relative']) {
      $this->_activityField = TRUE;
      $this->_params['fields']['activity_subject'] = 1;
    }
    if (isset($this->_params['relationship_type_id_value'])
      && !empty($this->_params['relationship_type_id_value'])
    ) {
      $this->_relField = TRUE;
    }
    $sql = $this->buildQuery(TRUE);


    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    if ($this->_params['activity_date_time_relative']) {
      $this->_columnHeaders = array_merge($this->_columnHeaders,
        ['civicrm_activity_activity_subject' =>
          ['type' => '2', 'title' => 'Last Action Activity Subject'],
        ]
      );
    }

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
      if (CRM_Utils_Array::arrayKeyExists('civicrm_case_case_type_id', $row)) {
        if ($value = str_replace(CRM_Case_BAO_Case::VALUE_SEPERATOR, "", $row['civicrm_case_case_type_id'])) {
          $rows[$rowNum]['civicrm_case_case_type_id'] = $this->case_types[$value];

          $entryFound = TRUE;
        }
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_case_subject', $row)) {
        if ($value = $row['civicrm_case_subject']) {
          $caseId = $row['civicrm_case_id'];
          $contactId = $row['civicrm_contact_id'];
          $rows[$rowNum]['civicrm_case_subject'] = "<a href= 'javascript:viewCase( $caseId,$contactId );'>$value</a>";
          $rows[$rowNum]['civicrm_case_subject_hover'] = ts("View Details of Case.");

          $entryFound = TRUE;
        }
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_relationship_relationship_type_id', $row)) {
        if ($value = $row['civicrm_relationship_relationship_type_id']) {
          $rows[$rowNum]['civicrm_relationship_relationship_type_id'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_RelationshipType', $value, 'label_b_a');

          $entryFound = TRUE;
        }
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
        }
        $entryFound = TRUE;
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);
        }
        $entryFound = TRUE;
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_activity_subject', $row)) {
        if (!($value = $row['civicrm_activity_activity_subject'])) {
          $rows[$rowNum]['civicrm_activity_activity_subject'] = "No Subject";
        }
        $entryFound = TRUE;
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}

