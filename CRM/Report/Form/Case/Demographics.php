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


class CRM_Report_Form_Case_Demographics extends CRM_Report_Form {

  /**
   * @var mixed[]
   */
  public $_genders;
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
  /**
   * @var string
   */
  public $_groupBy;
  public $_absoluteUrl;
  protected $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE; function __construct() {
    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'gender_id' =>
          ['title' => ts('Gender'),
            'default' => TRUE,
          ],
          'birth_date' =>
          ['title' => ts('Birthdate'),
            'default' => FALSE,
          ],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Contact Name')],
          'contact_type' =>
          ['title' => ts('Contact Type'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => ['' => ts('-select-'),
              'Individual' => ts('Individual'),
              'Organization' => ts('Organization'),
              'Household' => ts('Household'),
            ],
            'default' => 'Individual',
          ],
          'id' =>
          ['title' => ts('Contact ID'),
            'no_display' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
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
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' =>
        ['street_address' =>
          ['default' => FALSE],
          'city' =>
          ['default' => TRUE],
          'postal_code' => NULL,
          'state_province_id' =>
          ['title' => ts('State/Province'),
          ],
          'country_id' =>
          ['title' => ts('Country'),
            'default' => FALSE,
          ],
        ],
        /*
                          'filters'   =>             
                          array( 'country_id' => 
                                 array( 'title'   => ts( 'Country' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::country( ),
                                        ), 
                                 'state_province_id' =>  
                                 array( 'title'   => ts( 'State / Province' ), 
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options' => CRM_Core_PseudoConstant::stateProvince( ), ), 
                                 ), 
*/
      ],
      'civicrm_phone' =>
      ['dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        ['phone' => NULL],
        'grouping' => 'contact-fields',
      ],
      'civicrm_activity' =>
      ['dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        ['id' => ['title' => ts('Activity ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_case' =>
      ['dao' => 'CRM_Case_DAO_Case',
        'fields' =>
        ['id' => ['title' => ts('Case ID'),
            'required' => TRUE,
          ],
          'start_date' => ['title' => ts('Case Start'),
            'required' => TRUE,
          ],
          'end_date' => ['title' => ts('Case End'),
            'required' => TRUE,
          ],
        ],
        'filters' =>
        ['case_id_filter' => ['name' => 'id',
            'title' => ts('Cases?'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => [1 => ts('Exclude non-case'), 2 => ts('Exclude cases'), 3 => ts('Include Both')],
            'default' => 3,
          ],
          'start_date' => ['title' => ts('Case Start'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'end_date' => ['title' => ts('Case End'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
      ],
      'civicrm_group' =>
      ['dao' => 'CRM_Contact_DAO_Group',
        'alias' => 'cgroup',
        'filters' =>
        ['gid' =>
          ['name' => 'group_id',
            'title' => ts('Group'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'group' => TRUE,
            'options' => CRM_Core_PseudoConstant::group(),
          ],
        ],
      ],
    ];

    $this->_tagFilter = TRUE;

    $open_case_val = CRM_Core_OptionGroup::getValue('activity_type', 'Open Case', 'name');
    $crmDAO = &CRM_Core_DAO::executeQuery("SELECT cg.table_name, cg.extends AS ext, cf.label, cf.column_name FROM civicrm_custom_group cg INNER JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
where (cg.extends='Contact' OR cg.extends='Individual' OR cg.extends_entity_column_value='$open_case_val') AND cg.is_active=1 AND cf.is_active=1 ORDER BY cg.table_name");
    $curTable = '';
    $curExt = '';
    $curFields = [];
    while ($crmDAO->fetch()) {
      if ($curTable == '') {
        $curTable = $crmDAO->table_name;
        $curExt = $crmDAO->ext;
      }
      elseif ($curTable != $crmDAO->table_name) {
        // dummy DAO
        $this->_columns[$curTable] = ['dao' => 'CRM_Contact_DAO_Contact',
          'fields' => $curFields,
          'ext' => $curExt,
        ];
        $curTable = $crmDAO->table_name;
        $curExt = $crmDAO->ext;
        $curFields = [];
      }

      $curFields[$crmDAO->column_name] = ['title' => $crmDAO->label];
    }
    if (!empty($curFields)) {
      // dummy DAO
      $this->_columns[$curTable] = ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' => $curFields,
        'ext' => $curExt,
      ];
    }

    $this->_genders = CRM_Core_PseudoConstant::gender();

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
            if ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            elseif ($tableName == 'civicrm_phone') {
              $this->_phoneField = TRUE;
            }

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
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
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']}
            LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                      {$this->_aliases['civicrm_address']}.is_primary = 1 )
            LEFT JOIN civicrm_case_contact ccc ON ccc.contact_id = {$this->_aliases['civicrm_contact']}.id
            LEFT JOIN civicrm_case {$this->_aliases['civicrm_case']} ON {$this->_aliases['civicrm_case']}.id = ccc.case_id
            LEFT JOIN civicrm_case_activity cca ON cca.case_id = {$this->_aliases['civicrm_case']}.id
            LEFT JOIN civicrm_activity {$this->_aliases['civicrm_activity']} ON {$this->_aliases['civicrm_activity']}.id = cca.activity_id 
        ";

    foreach ($this->_columns as $t => $c) {
      if (substr($t, 0, 13) == 'civicrm_value' || substr($t, 0, 12) == 'custom_value') {
        $this->_from .= " LEFT JOIN $t {$this->_aliases[$t]} ON {$this->_aliases[$t]}.entity_id = ";
        $this->_from .= ($c['ext'] == 'Activity') ? "{$this->_aliases['civicrm_activity']}.id" : "{$this->_aliases['civicrm_contact']}.id";
      }
    }

    if ($this->_emailField) {
      $this->_from .= "
            LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']} 
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                      {$this->_aliases['civicrm_email']}.is_primary = 1) ";
    }

    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']} 
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
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

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              // handle special case
              if ($fieldName == 'case_id_filter') {
                $choice = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                if ($choice == 1) {
                  $clause = "({$this->_aliases['civicrm_case']}.id Is Not Null)";
                }
                elseif ($choice == 2) {
                  $clause = "({$this->_aliases['civicrm_case']}.id Is Null)";
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

    $clauses[] = "(({$this->_aliases['civicrm_case']}.is_deleted = 0) OR ({$this->_aliases['civicrm_case']}.is_deleted Is Null))";
    $clauses[] = "(({$this->_aliases['civicrm_activity']}.is_deleted = 0) OR ({$this->_aliases['civicrm_activity']}.is_deleted Is Null))";
    $clauses[] = "(({$this->_aliases['civicrm_activity']}.is_current_revision = 1) OR ({$this->_aliases['civicrm_activity']}.is_deleted Is Null))";

    $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id, {$this->_aliases['civicrm_case']}.id";
  }

  function postProcess() {

    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);
    //CRM_Core_Error::debug('sql', $sql);
    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      // handle gender
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_gender_id', $row)) {
        if ($value = $row['civicrm_contact_gender_id']) {
          $rows[$rowNum]['civicrm_contact_gender_id'] = $this->_genders[$value];
        }
        $entryFound = TRUE;
      }

      // handle country
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

      // handle custom fields
      foreach ($row as $k => $r) {
        if (substr($k, 0, 13) == 'civicrm_value' || substr($k, 0, 12) == 'custom_value') {
          if ($r || $r == '0') {
            if ($newval = $this->getCustomFieldLabel($k, $r)) {
              $rows[$rowNum][$k] = $newval;
            }
          }
          $entryFound = TRUE;
        }
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  function getCustomFieldLabel($fname, $val) {
    $query = "
SELECT v.label
  FROM civicrm_custom_group cg INNER JOIN civicrm_custom_field cf ON cg.id = cf.custom_group_id
  INNER JOIN civicrm_option_group g ON cf.option_group_id = g.id
  INNER JOIN civicrm_option_value v ON g.id = v.option_group_id
  WHERE CONCAT(cg.table_name, '_', cf.column_name) = %1 AND v.value = %2";
    $params = [1 => [$fname, 'String'],
      2 => [$val, 'String'],
    ];
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }
}

