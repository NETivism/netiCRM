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



class CRM_Report_Form_Contribute_TopDonor extends CRM_Report_Form {

  public $_columnHeaders;
  public $_from;
  public $_aliases;
  /**
   * @var string
   */
  public $_tempClause;
  public $_outerCluase;
  public $_where;
  public $_groupBy;
  public $_outputMode;
  public $_absoluteUrl;
  protected $_summary = NULL;

  protected $_phoneField = FALSE;

  protected $_charts = ['' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  ];

  protected $_customGroupExtends = ['Contact', 'Individual', 'Household', 'Organization'];

  function __construct() {
    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
          'sort_name' =>
          ['title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
      ],
      'civicrm_contribution' =>
      ['dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        ['total_amount' =>
          ['title' => ts('Amount Statistics'),
            'required' => TRUE,
            'statistics' =>
            ['sum' => ts('Aggregate Amount'),
              'count' => ts('Donations'),
              'avg' => ts('Average'),
            ],
          ],
        ],
        'filters' =>
        ['receive_date' =>
          ['default' => 'this.year',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'total_range' =>
          ['title' => ts('Show no. of Top Donors'),
            'type' => CRM_Utils_Type::T_INT,
            'default_op' => 'lte',
          ],
          'contribution_type_id' =>
          ['name' => 'contribution_type_id',
            'title' => ts('Contribution Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionType(),
          ],
          'contribution_status_id' =>
          ['title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
        ],
      ],
      'civicrm_group' =>
      ['dao' => 'CRM_Contact_DAO_GroupContact',
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
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'grouping' => 'contact-fields',
        'fields' =>
        [
          'country_id' =>
          ['title' => ts('Country'),
            'default' => TRUE,
            ],
          'state_province_id' =>
          ['title' => ts('State/Province'),
            'default' => TRUE
            ],
          'city' =>
          ['default' => TRUE],
          'postal_code' => 
          ['default' => TRUE],
          'street_address' =>
          ['default' => TRUE],
        ],
      ],
      'civicrm_phone' =>
      ['dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        ['phone' => NULL],
        'grouping' => 'contact-fields',
      ],

    ];

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function buildQuickForm() {
    parent::buildQuickForm();

    $this->getElement('total_range_op')->freeze();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];
    //Headers for Rank column
    $this->_columnHeaders["civicrm_donor_rank"]['title'] = ts('Rank');
    $this->_columnHeaders["civicrm_donor_rank"]['type'] = 1;
    //$select[] ="(@rank:=@rank+1)  as civicrm_donor_rank ";

    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            // only include statistics columns if set
            if (CRM_Utils_Array::value('statistics', $field)) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }

          if($tableName == 'civicrm_phone') {
            $this->_phoneField = TRUE;
          }
        }
      }
    }
    $this->_select = " SELECT * FROM ( SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = [];

    $op = CRM_Utils_Array::value('total_range_op', $fields);
    $val = CRM_Utils_Array::value('total_range_value', $fields);

    if (!in_array($op, ['eq', 'lte'])) {
      $errors['total_range_op'] = ts("Please select 'Is equal to' OR 'Is Less than or equal to' operator");
    }

    if ($val && !CRM_Utils_Rule::positiveInteger($val)) {
      $errors['total_range_value'] = ts("Please enter positive number");
    }
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
        	INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']} 
		          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND {$this->_aliases['civicrm_contribution']}.is_test = 0
        ";
    $this->_from .= "
    LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                   ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                      {$this->_aliases['civicrm_address']}.is_primary = 1 ) 
        ";

    if ($this->_phoneField) {
      $this->_from .= "
            LEFT JOIN civicrm_phone {$this->_aliases['civicrm_phone']}
                   ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                      {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
    }
  }

  function where() {
    $clauses = [];
    $this->_tempClause = $this->_outerCluase = '';
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
            if ($fieldName == 'total_range') {
              $value = CRM_Utils_Array::value("total_range_value", $this->_params);
              $this->_outerCluase = " WHERE (( @rows := @rows + 1) <= {$value}) ";
            }
            else {
              $clauses[] = $clause;
            }
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

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id ";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $this->select();

    $this->from();

    $this->customDataFrom();

    $this->where();

    $this->groupBy();

    $this->limit();


    //set the variable value rank, rows = 0
    $setVariable = " SET @rows:=0, @rank=0 ";
    CRM_Core_DAO::singleValueQuery($setVariable);

    $sql = " {$this->_select} {$this->_from}  {$this->_where} {$this->_groupBy} 
                     ORDER BY civicrm_contribution_total_amount_sum DESC
                 ) as abc {$this->_outerCluase} $this->_limit
               ";

    $dao = CRM_Core_DAO::executeQuery($sql);

    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_columnHeaders as $key => $value) {
        $row[$key] = $dao->$key;
      }
      $rows[] = $row;
    }
    $this->formatDisplay($rows);

    $this->doTemplateAssignment($rows);

    $this->endPostProcess($rows);
  }

  function limit($rowCount = CRM_Report_Form::ROW_COUNT_LIMIT) {

    // lets do the pager if in html mode
    $this->_limit = NULL;
    if ($this->_outputMode == 'html' || $this->_outputMode == 'group') {
      //replace only first occurence of SELECT
      $this->_select = preg_replace('/SELECT/', 'SELECT SQL_CALC_FOUND_ROWS ', $this->_select, 1);
      $pageId = CRM_Utils_Request::retrieve('crmPID', 'Integer', CRM_Core_DAO::$_nullObject);

      if (!$pageId && !empty($_POST) && isset($_POST['crmPID_B'])) {
        if (!isset($_POST['PagerBottomButton'])) {
          unset($_POST['crmPID_B']);
        }
        else {
          $pageId = max((int)@$_POST['crmPID_B'], 1);
        }
      }

      $pageId = $pageId ? $pageId : 1;
      $this->set(CRM_Utils_Pager::PAGE_ID, $pageId);
      $offset = ($pageId - 1) * $rowCount;

      $this->_limit = " LIMIT $offset, " . $rowCount;
    }
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $rank = 1;
    if (!empty($rows)) {
      foreach ($rows as $rowNum => $row) {

        $rows[$rowNum]['civicrm_donor_rank'] = $rank++;
        // convert display name to links
        if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_sort_name', $row) &&
          CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
        ) {
          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
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

        // skip looking further in rows, if first row itself doesn't
        // have the column we need
        if (!$entryFound) {
          break;
        }
      }
    }
  }
}

