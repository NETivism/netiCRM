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



class CRM_Report_Form_Contribute_Lybunt extends CRM_Report_Form {


  public $_columnHeaders;
  public $_from;
  public $_where;
  public $_statusClause;
  public $_groupBy;
  public $_aliases;
  public $_absoluteUrl;
  protected $_charts = ['' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  ];

  protected $lifeTime_from = NULL;
  protected $lifeTime_where = NULL; function __construct() {
    $yearsInPast = 8;
    $yearsInFuture = 2;
    $date = CRM_Core_SelectValues::date('custom', NULL, $yearsInPast, $yearsInFuture);
    $count = $date['maxYear'];
    while ($date['minYear'] <= $count) {
      $optionYear[$date['minYear']] = $date['minYear'];
      $date['minYear']++;
    }

    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'grouping' => 'contact-field',
        'fields' =>
        ['sort_name' =>
          ['title' => ts('Donor Name'),
            'default' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Donor Name'),
            'operator' => 'like',
          ],
        ],
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'grouping' => 'contact-field',
        'fields' =>
        ['email' =>
          ['title' => ts('Email'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_phone' =>
      ['dao' => 'CRM_Core_DAO_Phone',
        'grouping' => 'contact-field',
        'fields' =>
        ['phone' =>
          ['title' => ts('Phone No'),
            'default' => TRUE,
          ],
        ],
      ],
      'civicrm_contribution' =>
      ['dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        ['contact_id' =>
          ['title' => ts('contactId'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'total_amount' =>
          ['title' => ts('Total Amount'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'receive_date' =>
          ['title' => ts('Year'),
            'no_display' => TRUE,
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'filters' =>
        ['yid' =>
          ['name' => 'receive_date',
            'title' => ts('This Year'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            // 'type'    => CRM_Utils_Type::T_INT + CRM_Utils_Type::T_BOOLEAN,
            'options' => $optionYear,
            'default' => date('Y'),
            'clause' => "contribution_civireport.contact_id NOT IN
(SELECT distinct contri.contact_id FROM civicrm_contribution contri 
 WHERE   YEAR(contri.receive_date) =  \$value AND contri.is_test = 0) AND contribution_civireport.contact_id IN (SELECT distinct contri.contact_id FROM civicrm_contribution contri 
 WHERE   YEAR(contri.receive_date) =  (\$value-1) AND contri.is_test = 0) ",
          ],
          'contribution_status_id' =>
          ['operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => ['1'],
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
    ];

    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {

    $this->_columnHeaders = $select = [];
    $current_year = $this->_params['yid_value'];
    $previous_year = $current_year - 1;


    foreach ($this->_columns as $tableName => $table) {

      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {

          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($fieldName == 'total_amount') {
              $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}";

              $this->_columnHeaders["{$previous_year}"]['type'] = $field['type'];
              $this->_columnHeaders["{$previous_year}"]['title'] = $previous_year;

              $this->_columnHeaders["civicrm_life_time_total"]['type'] = $field['type'];
              $this->_columnHeaders["civicrm_life_time_total"]['title'] = 'LifeTime';;
            }
            elseif ($fieldName == 'receive_date') {
              $select[] = " Year ( {$field['dbAlias']} ) as {$tableName}_{$fieldName} ";
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName} ";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }

            if (CRM_Utils_Array::value('no_display', $field)) {
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = TRUE;
            }
          }
        }
      }
    }

    $this->_select = "SELECT  " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = "
        FROM  civicrm_contribution  {$this->_aliases['civicrm_contribution']}
              INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id
              {$this->_aclFrom}
              LEFT  JOIN civicrm_email  {$this->_aliases['civicrm_email']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                         {$this->_aliases['civicrm_email']}.is_primary = 1 
              LEFT  JOIN civicrm_phone  {$this->_aliases['civicrm_phone']} 
                      ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                         {$this->_aliases['civicrm_phone']}.is_primary = 1 ";
  }

  function where() {
    $this->_where = "";
    $this->_statusClause = "";
    $clauses = [];
    $current_year = $this->_params['yid_value'];
    $previous_year = $current_year - 1;

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
            if ($fieldName == 'yid' && $tableName == 'civicrm_contribution') {
              $field['clause'] = str_replace('$value', CRM_Utils_Array::value("{$fieldName}_value", $this->_params), $field['clause']);
            }
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
              if ($fieldName == 'contribution_status_id' && !empty($clause)) {
                $this->_statusClause = " AND " . $clause;
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
      $this->_where = "WHERE {$this->_aliases['civicrm_contribution']}.is_test = 0 ";
    }
    else {
      $this->_where = "WHERE {$this->_aliases['civicrm_contribution']}.is_test = 0  AND " . CRM_Utils_Array::implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = "Group BY  {$this->_aliases['civicrm_contribution']}.contact_id, Year({$this->_aliases['civicrm_contribution']}.receive_date) WITH ROLLUP";
    $this->assign('chartSupported', TRUE);
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    if (!empty($rows)) {
      $select = "
                      SELECT 
                            SUM({$this->_aliases['civicrm_contribution']}.total_amount ) as amount ";

      $sql = "{$select} {$this->_from} {$this->_where}";
      $dao = CRM_Core_DAO::executeQuery($sql);
      if ($dao->fetch()) {
        $statistics['counts']['amount'] = ['value' => $dao->amount,
          'title' => 'Total LifeTime',
          'type' => CRM_Utils_Type::T_MONEY,
        ];
      }
    }

    return $statistics;
  }

  function postProcess() {

    // get ready with post process params
    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $this->select();
    $this->from();
    $this->where();
    $this->groupBy();

    $rows = $contactIds = [];
    if (!CRM_Utils_Array::value('charts', $this->_params)) {
      $this->limit();
      $getContacts = "SELECT SQL_CALC_FOUND_ROWS {$this->_aliases['civicrm_contact']}.id as cid {$this->_from} {$this->_where}  GROUP BY {$this->_aliases['civicrm_contact']}.id {$this->_limit}";

      $dao = CRM_Core_DAO::executeQuery($getContacts);

      while ($dao->fetch()) {
        $contactIds[] = $dao->cid;
      }
      $dao->free();
      $this->setPager();
    }

    if (!empty($contactIds) || CRM_Utils_Array::value('charts', $this->_params)) {
      if (CRM_Utils_Array::value('charts', $this->_params)) {
        $sql = "{$this->_select} {$this->_from} {$this->_where} {$this->_groupBy}";
      }
      else {
        $sql = "{$this->_select} {$this->_from} WHERE {$this->_aliases['civicrm_contact']}.id IN (" . CRM_Utils_Array::implode(',', $contactIds) . ") AND {$this->_aliases['civicrm_contribution']}.is_test = 0 {$this->_statusClause} {$this->_groupBy} ";
      }

      $dao = CRM_Core_DAO::executeQuery($sql);
      $current_year = $this->_params['yid_value'];
      $previous_year = $current_year - 1;

      while ($dao->fetch()) {

        if (!$dao->civicrm_contribution_contact_id) {
          continue;
        }

        $row = [];
        foreach ($this->_columnHeaders as $key => $value) {
          if (property_exists($dao, $key)) {
            $rows[$dao->civicrm_contribution_contact_id][$key] = $dao->$key;
          }
        }

        if ($dao->civicrm_contribution_receive_date) {
          if ($dao->civicrm_contribution_receive_date == $previous_year) {
            $rows[$dao->civicrm_contribution_contact_id][$dao->civicrm_contribution_receive_date] = $dao->civicrm_contribution_total_amount;
          }
        }
        else {
          $rows[$dao->civicrm_contribution_contact_id]['civicrm_life_time_total'] = $dao->civicrm_contribution_total_amount;
        }
      }
      $dao->free();
    }

    $this->formatDisplay($rows, FALSE);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  function buildChart(&$rows) {

    $graphRows = [];
    $count = 0;
    $display = [];

    $current_year = $this->_params['yid_value'];
    $previous_year = $current_year - 1;
    $interval[$previous_year] = $previous_year;
    $interval['life_time'] = 'Life Time';

    foreach ($rows as $key => $row) {
      $display['life_time'] = CRM_Utils_Array::value('life_time', $display) + $row['civicrm_life_time_total'];
      $display[$previous_year] = CRM_Utils_Array::value($previous_year, $display) + $row[$previous_year];
    }

    $config = CRM_Core_Config::Singleton();
    $graphRows['value'] = $display;
    $chartInfo = ['legend' => ts('Lybunt Report'),
      'xname' => ts('Year'),
      'yname' => ts('Amount (%1)', [1 => $config->defaultCurrency]),
    ];
    if ($this->_params['charts']) {
      // build chart.
      $this->assign('chartType', $this->_params['charts']);
    }
  }

  function alterDisplay(&$rows) {
    foreach ($rows as $rowNum => $row) {
      //Convert Display name into link
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_display_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contribution_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contribution_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contribution Details for this Contact.");
      }
    }
  }
}

