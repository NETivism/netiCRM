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


class CRM_Report_Form_Mailing_Summary extends CRM_Report_Form {

  /**
   * @var never[]
   */
  public $_columnHeaders;
  /**
   * @var string
   */
  public $_from;
  public $_aliases;
  public $relationshipId;
  /**
   * @var string
   */
  public $_where;
  /**
   * @var string
   */
  public $_groupBy;
  /**
   * @var string
   */
  public $_orderBy;
  public $_absoluteUrl;
  protected $_summary = NULL;

  # just a toggle we use to build the from
  protected $_mailingidField = FALSE;

  protected $_customGroupExtends = [];


  protected $_charts = ['' => 'Tabular',
    'bar_3dChart' => 'Bar Chart',
  ]; function __construct() {
    $this->_columns = [];

    $this->_columns['civicrm_mailing'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'name' => [
          'title' => ts('Mailing Name'),
          'required' => TRUE,
        ],
        'created_date' => [
          'title' => ts('Date Created'),
        ],
      ],
      'filters' => [
        'is_completed' => [
          'title' => ts('Mailing Status'),
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'type' => CRM_Utils_Type::T_INT,
          'options' => [
            0 => ts('Incomplete'),
            1 => ts('Complete'),
          ],
          //'operator' => 'like',
          'default' => 1,
        ],
        'mailing_name' => [
          'name' => 'name',
          'title' => ts('Mailing'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'type' => CRM_Utils_Type::T_STRING,
          'options' => self::mailing_select(),
          'operator' => 'like',
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_job'] = [
      'dao' => 'CRM_Mailing_DAO_Job',
      'fields' => [
        'start_date' => [
          'title' => ts('Start Date'),
        ],
        'end_date' => [
          'title' => ts('End Date'),
        ],
      ],
      'filters' => [
        'status' => [
          'type' => CRM_Utils_Type::T_STRING,
          'default' => 'Complete',
          'no_display' => TRUE,
        ],
        'is_test' => [
          'type' => CRM_Utils_Type::T_INT,
          'default' => 0,
          'no_display' => TRUE,
        ],
        'start_date' => [
          'title' => ts('Start Date'),
          'default' => 'this.year',
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
        'end_date' => [
          'title' => ts('End Date'),
          'default' => 'this.year',
          'operatorType' => CRM_Report_Form::OP_DATE,
          'type' => CRM_Utils_Type::T_DATE,
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_queue'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'queue_count' => [
          'name' => 'id',
          'title' => ts('Intended Recipients'),
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_delivered'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'delivered_count' => [
          'name' => 'id',
          'title' => ts('Delivered'),
        ],
        'accepted_rate' => [
          'title' => ts('Successful Deliveries').'%',
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_delivered.delivered_count',
            'base' => 'civicrm_mailing_event_queue.queue_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_bounce'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'bounce_count' => [
          'name' => 'id',
          'title' => ts('Bounce'),
        ],
        'bounce_rate' => [
          'title' => ts('Bounce Rate').'%',
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_bounce.bounce_count',
            'base' => 'civicrm_mailing_event_queue.queue_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_opened'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'unique_open_count' => [
          'name' => 'id',
          'title' => ts('Unique Tracked Opens'),
          'alias' => 'mailing_event_opened_civireport',
          'dbAlias' => 'mailing_event_opened_civireport.event_queue_id',
        ],
        'unique_open_rate' => [
          'title' => ts('Unique Open Rate').'%',
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_opened.unique_open_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ],
        ],
        'open_count' => [
          'name' => 'id',
          'title' => ts('Total Opens'),
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_trackable_url_open'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'unique_click_count' => [
          'name' => 'id',
          'title' => ts('Unique Click-throughs'),
          'alias' => 'mailing_event_trackable_url_open_civireport',
          'dbAlias' => 'mailing_event_trackable_url_open_civireport.event_queue_id',
        ],
        'click_count' => [
          'name' => 'id',
          'title' => ts('Total Clicks'),
        ],
        'CTR' => [
          'title' => ts('Unique Click-throughs Rate').'%',
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_trackable_url_open.unique_click_count',
            'base' => 'civicrm_mailing_event_delivered.delivered_count',
          ],
        ],
        'CTO' => [
          'title' => ts('Click to Open Rate').'%',
          'default' => 0,
          'statistics' => [
            'calc' => 'PERCENTAGE',
            'top' => 'civicrm_mailing_event_trackable_url_open.unique_click_count',
            'base' => 'civicrm_mailing_event_opened.unique_open_count',
          ],
        ],
      ],
    ];

    $this->_columns['civicrm_mailing_event_unsubscribe'] = [
      'dao' => 'CRM_Mailing_DAO_Mailing',
      'fields' => [
        'unsubscribe_count' => [
          'name' => 'id',
          'title' => ts('Unsubscribe'),
        ],
        'unique_unsubscribe_count' => [
          'name' => 'id',
          'title' => ts('Unique Unsubscribe'),
          'alias' => 'mailing_event_unsubscribe_civireport',
          'dbAlias' => 'mailing_event_unsubscribe_civireport.event_queue_id',
        ],
      ],
    ];

    parent::__construct();
  }

  function mailing_select() {


    $data = [];

    $mailing = new CRM_Mailing_BAO_Mailing();
    $query = "SELECT name FROM civicrm_mailing ";
    $mailing->query($query);

    while ($mailing->fetch()) {
      $data[CRM_Core_DAO::escapeString($mailing->name)] = $mailing->name;
    }

    return $data;
  }

  function preProcess() {
    $this->assign('chartSupported', TRUE);
    parent::preProcess();
  }

  // manipulate the select function to query count functions
  function select() {

    $count_tables = [
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_delivered',
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_opened',
      'civicrm_mailing_event_trackable_url_open',
      'civicrm_mailing_event_unsubscribe',
    ];
    $distinctCountColumns = [
      'civicrm_mailing_event_queue.queue_count',
      'civicrm_mailing_event_delivered.delivered_count',
      'civicrm_mailing_event_bounce.bounce_count',
      'civicrm_mailing_event_opened.unique_open_count',
      'civicrm_mailing_event_trackable_url_open.unique_click_count',
      'civicrm_mailing_event_unsubscribe.unique_unsubscribe_count',
      'civicrm_mailing_event_unsubscribe.optout_count',
    ];

    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            # for statistics
            if (CRM_Utils_Array::value('statistics', $field)) {
              switch ($field['statistics']['calc']) {
                case 'PERCENTAGE':
                  $base_table_column = explode('.', $field['statistics']['base']);
                  $top_table_column = explode('.', $field['statistics']['top']);

                  $select[] = "round(
                    count(DISTINCT {$this->_columns[$top_table_column[0]]['fields'][$top_table_column[1]]['dbAlias']}) / 
                    count(DISTINCT {$this->_columns[$base_table_column[0]]['fields'][$base_table_column[1]]['dbAlias']}), 4
                  ) * 100 as {$tableName}_{$fieldName}";
                  break;
              }
            }
            else {
              if (in_array($tableName, $count_tables)) {
                $distinct = '';
                if (in_array("{$tableName}.{$fieldName}", $distinctCountColumns)) {
                  $distinct = 'DISTINCT';
                }
                $select[] = "count($distinct {$field['dbAlias']}) as {$tableName}_{$fieldName}";
              }
              else {
                $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              }
            }
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
    //print_r($this->_select);
  }

  function from() {

    $this->_from = "
    FROM civicrm_mailing {$this->_aliases['civicrm_mailing']} 
      LEFT JOIN civicrm_mailing_job {$this->_aliases['civicrm_mailing_job']}
        ON {$this->_aliases['civicrm_mailing']}.id = {$this->_aliases['civicrm_mailing_job']}.mailing_id
      LEFT JOIN civicrm_mailing_event_queue {$this->_aliases['civicrm_mailing_event_queue']}
        ON {$this->_aliases['civicrm_mailing_event_queue']}.job_id = {$this->_aliases['civicrm_mailing_job']}.id
      LEFT JOIN civicrm_mailing_event_bounce {$this->_aliases['civicrm_mailing_event_bounce']}
        ON {$this->_aliases['civicrm_mailing_event_bounce']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
      LEFT JOIN civicrm_mailing_event_delivered {$this->_aliases['civicrm_mailing_event_delivered']}
        ON {$this->_aliases['civicrm_mailing_event_delivered']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
        AND {$this->_aliases['civicrm_mailing_event_bounce']}.id IS null
      LEFT JOIN civicrm_mailing_event_opened {$this->_aliases['civicrm_mailing_event_opened']}
        ON {$this->_aliases['civicrm_mailing_event_opened']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
      LEFT JOIN civicrm_mailing_event_trackable_url_open {$this->_aliases['civicrm_mailing_event_trackable_url_open']}
        ON {$this->_aliases['civicrm_mailing_event_trackable_url_open']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id
      LEFT JOIN civicrm_mailing_event_unsubscribe {$this->_aliases['civicrm_mailing_event_unsubscribe']}
        ON {$this->_aliases['civicrm_mailing_event_unsubscribe']}.event_queue_id = {$this->_aliases['civicrm_mailing_event_queue']}.id";
    // need group by and order by

    //print_r($this->_from);
  }

  function where() {
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
              if ($fieldName == 'relationship_type_id') {
                $clause = "{$this->_aliases['civicrm_relationship']}.relationship_type_id=" . $this->relationshipId;
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
      $this->_where = "WHERE ( 1 )";
    }
    else {
      $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
    }

    // if ( $this->_aclWhere ) {
    // $this->_where .= " AND {$this->_aclWhere} ";
    // }
  }

  function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_mailing']}.id";
  }

  function orderBy() {
    $this->_orderBy = " ORDER BY {$this->_aliases['civicrm_mailing_job']}.end_date DESC ";
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause(CRM_Utils_Array::value('civicrm_contact', $this->_aliases));

    $sql = $this->buildQuery(TRUE);

    // print_r($sql);

    $rows = $graphRows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  static function getChartCriteria() {
    return ['civicrm_mailing_event_delivered_delivered_count' => ts('Delivered'),
      'civicrm_mailing_event_bounce_bounce_count' => ts('Bounce'),
      'civicrm_mailing_event_opened_open_count' => ts('Opened'),
      'civicrm_mailing_event_trackable_url_open_click_count' => ts('Clicks'),
      'civicrm_mailing_event_unsubscribe_unsubscribe_count' => ts('Unsubscribe'),
    ];
  }

  static function formRule($fields, $files, $self) {
    $errors = [];

    if (!CRM_Utils_Array::value('charts', $fields)) {
      return $errors;
    }

    $criterias = self::getChartCriteria();
    $isError = TRUE;
    foreach ($fields['fields'] as $fld => $isActive) {
      if (in_array($fld, ['delivered_count', 'bounce_count', 'open_count', 'click_count', 'unsubscribe_count'])) {
        $isError = FALSE;
      }
    }

    if ($isError) {
      $errors['_qf_default'] = ts("For Chart view, please select at least one field from %1.", ['%1' => CRM_Utils_Array::implode(', ', $criterias)]);
    }

    return $errors;
  }

  function buildChart(&$rows) {
    if (empty($rows)) {
      return;
    }

    $criterias = self::getChartCriteria();

    $chartInfo = ['legend' => ts('Mail Summary'),
      'xname' => ts('Mailing'),
      'yname' => ts('Statistics'),
      'xLabelAngle' => 20,
      'tip' => [],
    ];

    foreach ($rows as $row) {
      $chartInfo['values'][$row['civicrm_mailing_name']] = [];
      foreach ($criterias as $criteria => $label) {
        if (isset($row[$criteria])) {
          $chartInfo['values'][$row['civicrm_mailing_name']][$label] = $row[$criteria];
          $chartInfo['tip'][$label] = "{$label} #val#";
        }
        elseif (isset($criterias[$criteria])) {
          unset($criterias[$criteria]);
        }
      }
    }

    $chartInfo['criteria'] = array_values($criterias);

    // dynamically set the graph size
    $chartInfo['xSize'] = ((count($rows) * 135) + (count($rows) * count($criterias) * 30));

    // build the chart.
    $this->assign('chartType', $this->_params['charts']);
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
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id
        );
        $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_display_name_hover'] = ts("View Contact details for this contact.");
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

