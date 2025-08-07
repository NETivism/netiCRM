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



class CRM_Report_Form_Contribute_Summary extends CRM_Report_Form {
  /**
   * @var array<string, array<'no_display', bool>>
   */
  public $_columnHeaders;
  public $_interval;
  public $_from;
  public $_aliases;
  /**
   * @var string
   */
  public $_groupBy;
  public $_where;
  public $_absoluteUrl;
  protected $_addressField = FALSE;

  protected $_charts = ['' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  ];
  protected $_customGroupExtends = ['Contribution'];
  protected $_customGroupGroupBy = TRUE;

  function __construct() {
    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['sort_name' =>
          ['title' => ts('Contact Name'),
            'no_repeat' => TRUE,
          ],
          'postal_greeting_display' =>
          ['title' => ts('Postal Greeting')],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'group_bys' =>
        ['id' =>
          ['title' => ts('Contact ID')],
          'sort_name' =>
          ['title' => ts('Contact Name'),
          ],
        ],
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
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          ['title' => ts('State/Province'),
          ],
          'country_id' =>
          ['title' => ts('Country')],
        ],
        'group_bys' =>
        ['street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
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
      'civicrm_contribution_type' =>
      ['dao' => 'CRM_Contribute_DAO_ContributionType',
        'fields' =>
        ['contribution_type' => NULL,
        ],
        'grouping' => 'contri-fields',
        'group_bys' =>
        ['contribution_type' => NULL,
        ],
      ],
      'civicrm_contribution_page' =>
        [
        'dao' => 'CRM_Contribute_DAO_ContributionPage',
        'fields' =>
          ['title' => [
            'title' => ts('Contribution Page'),
          ],
        ],
        'grouping' => 'contri-fields',
        'group_bys' =>
        ['title' => [
            'title' => ts('Contribution Page'),
          ]
        ],
      ],
      'civicrm_contribution' =>
      ['dao' => 'CRM_Contribute_DAO_Contribution',
        //'bao'           => 'CRM_Contribute_BAO_Contribution',
        'fields' =>
        [
          'contribution_source' => NULL,
          'payment_instrument_id' => [
            'title' => ts('Payment Instrument'),
          ],
          'receipt_id' => [
            'title' => ts('Receipt ID'),
          ],
          'total_amount' =>
          ['title' => ts('Amount Statistics'),
            'default' => TRUE,
            'required' => TRUE,
            'statistics' =>
            ['sum' => ts('Aggregate Amount'),
              'count' => ts('Donations'),
              'avg' => ts('Average'),
            ],
          ],
        ],
        'grouping' => 'contri-fields',
        'filters' =>
        ['receive_date' =>
          ['operatorType' => CRM_Report_Form::OP_DATE],
          'contribution_source' =>
          [
            'title' => ts('Contribution Source'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
          'contribution_status_id' =>
          ['title' => ts('Donation Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
            'default' => [1],
          ],
          'contribution_type_id' =>
          ['title' => ts('Contribution Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionType(),
          ],
          'contribution_page_id' =>
          ['title' => ts('Contribution Page'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionPage(),
          ],
          'payment_instrument_id' =>
          ['title' => ts('Payment Instrument'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('payment_instrument'),
          ],
          'total_amount' =>
          ['title' => ts('Donation Amount'),
          ],
          'total_sum' =>
          ['title' => ts('Aggregate Amount'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_sum',
            'having' => TRUE,
          ],
          'total_count' =>
          ['title' => ts('Donation Count'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_count',
            'having' => TRUE,
          ],
          'total_avg' =>
          ['title' => ts('Average'),
            'type' => CRM_Report_Form::OP_INT,
            'dbAlias' => 'civicrm_contribution_total_amount_avg',
            'having' => TRUE,
          ],
        ],
        'group_bys' =>
        ['receive_date' =>
          ['frequency' => TRUE,
            'chart' => TRUE,
          ],
          'contribution_source' => NULL,
          'payment_instrument_id' => [
            'title' => ts('Payment Instrument'),
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

  function setDefaultValues($freeze = TRUE) {
    return parent::setDefaultValues($freeze);
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
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
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
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
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
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
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    //check for searching combination of dispaly columns and
    //grouping criteria
    $ignoreFields = ['total_amount', 'sort_name'];
    $errors = $self->customDataFormRule($fields, $ignoreFields);

    if (CRM_Utils_Array::value('receive_date', $fields['group_bys'])) {
      foreach ($self->_columns as $tableName => $table) {
        if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
          foreach ($table['fields'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($field['name'], $fields['fields']) &&
              $fields['fields'][$field['name']] &&
              in_array($field['name'], ['contribution_source', 'contribution_type', 'contribution_page'])
            ) {
              $grouping[] = $field['title'];
            }
          }
        }
      }
      if (!empty($grouping)) {
        $temp = 'and ' . CRM_Utils_Array::implode(', ', $grouping);
        $errors['fields'] = ts("Please do not use combination of Receive Date %1", [1 => $temp]);
      }
    }

    if (!CRM_Utils_Array::value('total_amount', $fields['fields'])) {
      foreach (['total_count_value', 'total_sum_value', 'total_avg_value'] as $val) {
        if (CRM_Utils_Array::value($val, $fields)) {
          $errors[$val] = ts("Please select the Amount Statistics");
        }
      }
    }

    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_contact  {$this->_aliases['civicrm_contact']}
             INNER JOIN civicrm_contribution   {$this->_aliases['civicrm_contribution']} 
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0
             LEFT  JOIN civicrm_contribution_type  {$this->_aliases['civicrm_contribution_type']} 
                     ON {$this->_aliases['civicrm_contribution']}.contribution_type_id ={$this->_aliases['civicrm_contribution_type']}.id
             LEFT  JOIN civicrm_contribution_page  {$this->_aliases['civicrm_contribution_page']} 
                     ON {$this->_aliases['civicrm_contribution']}.contribution_page_id ={$this->_aliases['civicrm_contribution_page']}.id
             LEFT  JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND 
                        {$this->_aliases['civicrm_email']}.is_primary = 1) 
              
             LEFT  JOIN civicrm_phone {$this->_aliases['civicrm_phone']} 
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                        {$this->_aliases['civicrm_phone']}.is_primary = 1)";

    if ($this->_addressField) {
      $this->_from .= "
                  LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                         ON {$this->_aliases['civicrm_contact']}.id = 
                            {$this->_aliases['civicrm_address']}.contact_id AND 
                            {$this->_aliases['civicrm_address']}.is_primary = 1\n";
    }
  }

  function groupBy() {
    $this->_groupBy = "";
    $append = FALSE;
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
                $groupBy[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                $append = TRUE;
              }
              else {
                $groupBy[] = $field['dbAlias'];
              }
            }
          }
        }
      }

      if (!empty($this->_statFields) &&
        (($append && count($groupBy) <= 1) || (!$append)) && !$this->_having
      ) {
        $this->_rollup = " WITH ROLLUP";
      }
      $this->_groupBy = "GROUP BY " . CRM_Utils_Array::implode(', ', $groupBy) . " {$this->_rollup} ";
    }
    else {
      $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id";
    }
  }

  function statistics(&$rows) {
    $statistics = parent::statistics($rows);

    if (!$this->_having) {
      $select = "
            SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount )       as count,
                   SUM({$this->_aliases['civicrm_contribution']}.total_amount )         as amount,
                   ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg
            ";

      $sql = "{$select} {$this->_from} {$this->_where}";
      $dao = CRM_Core_DAO::executeQuery($sql);

      if ($dao->fetch()) {
        $statistics['counts']['amount'] = ['value' => $dao->amount,
          'title' => 'Total Amount',
          'type' => CRM_Utils_Type::T_MONEY,
        ];
        $statistics['counts']['count '] = ['value' => $dao->count,
          'title' => 'Total Donations',
        ];
        $statistics['counts']['avg   '] = ['value' => $dao->avg,
          'title' => 'Average',
          'type' => CRM_Utils_Type::T_MONEY,
        ];
      }
    }
    return $statistics;
  }

  function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  function buildChart(&$rows) {
    $graphRows = [];
    $count = 0;

    if (CRM_Utils_Array::value('charts', $this->_params)) {
      foreach ($rows as $key => $row) {
        if ($row['civicrm_contribution_receive_date_subtotal']) {
          $graphRows['receive_date'][] = $row['civicrm_contribution_receive_date_start'];
          $graphRows[$this->_interval][] = $row['civicrm_contribution_receive_date_interval'];
          $graphRows['value'][] = $row['civicrm_contribution_total_amount_sum'];
          $count++;
        }
      }

      if (CRM_Utils_Array::value('receive_date', $this->_params['group_bys'])) {

        // build the chart.
        $config = CRM_Core_Config::Singleton();
        $graphRows['xname'] = $this->_interval;
        $graphRows['yname'] = "Amount ({$config->defaultCurrency})";
        $this->assign('chartType', $this->_params['charts']);
      }
    }
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    $payment_instrument = CRM_Core_OptionGroup::values('payment_instrument');

    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      if (CRM_Utils_Array::value('receive_date', $this->_params['group_bys']) &&
        CRM_Utils_Array::value('civicrm_contribution_receive_date_start', $row) &&
        CRM_Utils_Array::value('civicrm_contribution_receive_date_subtotal', $row)
      ) {

        $dateStart = CRM_Utils_Date::customFormat($row['civicrm_contribution_receive_date_start'], '%Y%m%d');
        $endDate = new DateTime($dateStart);
        $dateEnd = [];

        list($dateEnd['Y'], $dateEnd['M'], $dateEnd['d']) = explode(':', $endDate->format('Y:m:d'));

        switch (strtolower($this->_params['group_bys_freq']['receive_date'])) {
          case 'month':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'] + 1,
                $dateEnd['d'] - 1, $dateEnd['Y']
              ));
            break;

          case 'year':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
                $dateEnd['d'] - 1, $dateEnd['Y'] + 1
              ));
            break;

          case 'yearweek':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'],
                $dateEnd['d'] + 6, $dateEnd['Y']
              ));
            break;

          case 'quarter':
            $dateEnd = date("Ymd", mktime(0, 0, 0, $dateEnd['M'] + 3,
                $dateEnd['d'] - 1, $dateEnd['Y']
              ));
            break;
        }
        $query = "reset=1&force=1&receive_date_from={$dateStart}&receive_date_to={$dateEnd}";
        if (!empty($this->_params['contribution_status_id_op']) && !empty($this->_params['contribution_status_id_value'])) {
          if (is_array($this->_params['contribution_status_id_value'])) {
            $status_id_value = CRM_Utils_Array::implode(',', $this->_params['contribution_status_id_value']);
          }
          else {
            $status_id_value = $this->_params['contribution_status_id_value'];
          }
          $query .= "&contribution_status_id_op={$this->_params['contribution_status_id_op']}&contribution_status_id_value={$status_id_value}";
        }
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          $query,
          $this->_absoluteUrl,
          $this->_id
        );
        $rows[$rowNum]['civicrm_contribution_receive_date_start_link'] = $url;
        $rows[$rowNum]['civicrm_contribution_receive_date_start_hover'] = ts('List all contribution(s) for this date unit.');
        $entryFound = TRUE;
      }

      // make subtotals look nicer
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contribution_receive_date_subtotal', $row) &&
        !$row['civicrm_contribution_receive_date_subtotal']
      ) {
        $this->fixSubTotalDisplay($rows[$rowNum], $this->_statFields);
        $entryFound = TRUE;
      }

      // handle state province
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            "reset=1&force=1&state_province_id_op=in&state_province_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_state_province_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_state_province_id_hover'] = ts('List all contribution(s) for this state.');
        }
        $entryFound = TRUE;
      }

      // handle country
      if (CRM_Utils_Array::arrayKeyExists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, FALSE);
          $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
            "reset=1&force=1&" .
            "country_id_op=in&country_id_value={$value}",
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_address_country_id_link'] = $url;
          $rows[$rowNum]['civicrm_address_country_id_hover'] = ts('List all contribution(s) for this country.');
        }

        $entryFound = TRUE;
      }

      // convert display name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contribute/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("Lists detailed contribution(s) for this record.");
        $entryFound = TRUE;
      }

      // convert payment instruments display
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contribution_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_payment_instrument_id'] = $payment_instrument[$rows[$rowNum]['civicrm_contribution_payment_instrument_id']];
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

