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


class CRM_Report_Form_Contact_Log extends CRM_Report_Form {

  public $activityTypes;
  /**
   * @var never[]
   */
  public $_columnHeaders;
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
  public $_orderBy;
  public $_absoluteUrl;
  protected $_summary = NULL; function __construct() {

    $this->activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);
    asort($this->activityTypes);

    $this->_columns = ['civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name' =>
          ['title' => ts('Modified By'),
            'required' => TRUE,
          ],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name' =>
          ['title' => ts('Modified By')],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_contact_touched' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['display_name_touched' =>
          ['title' => ts('Touched Contact'),
            'name' => 'display_name',
            'required' => TRUE,
          ],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'filters' =>
        ['sort_name_touched' =>
          ['title' => ts('Touched Contact'),
            'name' => 'sort_name',
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_activity' =>
      ['dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        ['id' => ['title' => ts('Activity ID'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'subject' => ['title' => ts('Touched Activity'),
            'required' => TRUE,
          ],
          'activity_type_id' => ['title' => ts('Activity Type'),
            'required' => TRUE,
          ],
          'source_contact_id' => ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_log' =>
      ['dao' => 'CRM_Core_DAO_Log',
        'fields' =>
        ['modified_date' =>
          ['title' => ts('Modified Date'),
            'required' => TRUE,
          ],
          'data' =>
          ['title' => ts('Description'),
          ],
        ],
        'filters' =>
        ['modified_date' =>
          ['title' => ts('Modified Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
            'default' => 'this.week',
          ],
        ],
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
        FROM civicrm_log {$this->_aliases['civicrm_log']}
        inner join civicrm_contact {$this->_aliases['civicrm_contact']} on {$this->_aliases['civicrm_log']}.modified_id = {$this->_aliases['civicrm_contact']}.id
        left join civicrm_contact {$this->_aliases['civicrm_contact_touched']} on ({$this->_aliases['civicrm_log']}.entity_table='civicrm_contact' AND {$this->_aliases['civicrm_log']}.entity_id = {$this->_aliases['civicrm_contact_touched']}.id)
        left join civicrm_activity {$this->_aliases['civicrm_activity']} on ({$this->_aliases['civicrm_log']}.entity_table='civicrm_activity' AND {$this->_aliases['civicrm_log']}.entity_id = {$this->_aliases['civicrm_activity']}.id)
        ";
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

    $clauses[] = "({$this->_aliases['civicrm_log']}.entity_table <> 'civicrm_domain')";
    $this->_where = "WHERE " . CRM_Utils_Array::implode(' AND ', $clauses);
  }

  function orderBy() {
    $this->_orderBy = "
ORDER BY {$this->_aliases['civicrm_log']}.modified_date DESC
";
  }

  /*    function postProcess( ) {

        $this->beginPostProcess( );

        $sql  = $this->buildQuery( true );
//CRM_Core_Error::debug('sql', $sql);             
        $rows = $graphRows = array();
        $this->buildRows ( $sql, $rows );
        
        $this->formatDisplay( $rows );
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );	
    }
*/
  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound = FALSE;
    foreach ($rows as $rowNum => $row) {
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

      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_touched_display_name_touched', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_touched_id', $row) &&
        $row['civicrm_contact_touched_display_name_touched'] !== ''
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $row['civicrm_contact_touched_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_touched_display_name_touched_link'] = $url;
        $rows[$rowNum]['civicrm_contact_touched_display_name_touched_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_subject', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_activity_id', $row) &&
        $row['civicrm_activity_subject'] !== ''
      ) {
        $url = CRM_Utils_System::url('civicrm/contact/view/activity',
          'reset=1&action=view&id=' . $row['civicrm_activity_id'] . '&cid=' . $row['civicrm_activity_source_contact_id'] . '&atype=' . $row['civicrm_activity_activity_type_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_activity_subject_link'] = $url;
        $rows[$rowNum]['civicrm_activity_subject_hover'] = ts("View Contact details for this contact.");
        $entryFound = TRUE;
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {
          $rows[$rowNum]['civicrm_activity_activity_type_id'] = $this->activityTypes[$value];
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

