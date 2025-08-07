<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3		                         				  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010							      |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.								      |
 |																      |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License			  |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.	  |
 |																	  |
 | CiviCRM is distributed in the hope that it will be useful, but	  |
 | WITHOUT ANY WARRANTY; without even the implied warranty of	      |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.				  |
 | See the GNU Affero General Public License for more details.		  |
 |																	  |
 | You should have received a copy of the GNU Affero General Public	  |
 | License and the CiviCRM Licensing Exception along				  |
 | with this program; if not, contact CiviCRM LLC					  |
 | at info[AT]civicrm[DOT]org. If you have questions about the		  |
 | GNU Affero General Public License or the licensing of CiviCRM,	  |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing		  |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */





class CRM_Report_Form_Event_ParticipantListCount extends CRM_Report_Form {

  public $_aliases;
  public $_from;
  public $_where;
  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $_groupBy;
  public $_absoluteUrl;
  protected $_summary = NULL;

  protected $_customGroupExtends = ['Participant']; function __construct() {
    $this->_columns = [
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        ['sort_name' =>
          ['title' => ts('Name'),
            'default' => TRUE,
            'no_repeat' => TRUE,
            'required' => TRUE,
          ],
          'id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'filters' => ['sort_name' =>
          ['title' => ts('Participant Name'),
            'operator' => 'like',
          ],
        ],
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' =>
          ['title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'filters' =>
        ['email' =>
          ['title' => ts('Participant E-mail'),
            'operator' => 'like',
          ],
        ],
      ],
      'civicrm_address' =>
      ['dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        ['street_address' => NULL,
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_participant' =>
      ['dao' => 'CRM_Event_DAO_Participant',
        'fields' =>
        ['participant_id' =>
          ['title' => ts('Participant ID'),
            'default' => TRUE,
          ],
          'event_id' =>
          ['title' => ts('Event'),
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'role_id' =>
          ['title' => ts('Role'),
            'default' => TRUE,
          ],
          'status_id' =>
          ['title' => ts('Status'),
            'default' => TRUE,
          ],
          'participant_register_date' =>
          ['title' => ts('Registration Date'),
          ],
        ],
        'grouping' => 'event-fields',
        'filters' =>
        ['event_id' =>
          ['name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::event(NULL, NULL, "is_template IS NULL OR is_template = 0"),
          ],
          'sid' =>
          ['name' => 'status_id',
            'title' => ts('Participant Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULl, NULL, 'label'),
          ],
          'rid' =>
          ['name' => 'role_id',
            'title' => ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ],
          'participant_register_date' => ['title' => ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'group_bys' =>
        ['event_id' =>
          ['title' => ts('Event'),
          ],
        ],
      ],
      'civicrm_event' =>
      ['dao' => 'CRM_Event_DAO_Event',
        'fields' =>
        ['event_type_id' =>
          ['title' => ts('Event Type'),
          ],
          'start_date' =>
          ['title' => ts('Event Start Date'),
          ],
          'end_date' =>
          ['title' => ts('Event End Date'),
          ],
        ],
        'grouping' => 'event-fields',
        'filters' => ['eid' =>
          ['name' => 'event_type_id',
            'title' => ts('Event Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ],
          'event_start_date' =>
          ['name' => 'start_date',
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'event_end_date' =>
          ['name' => 'end_date',
            'title' => ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'group_bys' =>
        ['event_type_id' =>
          ['title' => ts('Event Type '),
          ],
        ],
      ],
      'civicrm_line_item' =>
      ['dao' => 'CRM_Price_DAO_LineItem',
        'fields' =>
        ['line_total' =>
          ['title' => ts('Income'),
            'default' => TRUE,
            'statistics' =>
            ['sum' => ts('Amount'),
              'avg' => ts('Average'),
            ],
          ],
          'participant_count' =>
          ['title' => ts('Count'),
            'default' => TRUE,
            'statistics' =>
            ['sum' => ts('Count'),
            ],
          ],
        ],
      ],
    ];

    $this->_options = [
      'blank_column_begin' =>
      ['title' => ts('Blank column at the Begining'),
        'type' => 'checkbox',
      ],
      'blank_column_end' =>
      ['title' => ts('Blank column at the End'),
        'type' => 'select',
        'options' => ['' => '-select-',
          1 => 'One',
          2 => 'Two',
          3 => 'Three',
        ],
      ],
    ];
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  //Add The statistics
  function statistics(&$rows) {

    $statistics = parent::statistics($rows);
    $avg = NULL;
    $select = " SELECT SUM( {$this->_aliases['civicrm_line_item']}.participant_count ) as count,
									SUM( {$this->_aliases['civicrm_line_item']}.line_total )	 as amount
						";
    $sql = "{$select} {$this->_from} {$this->_where}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    if ($dao->fetch()) {

      if ($dao->count && $dao->amount) {
        $avg = $dao->amount / $dao->count;
      }
      $statistics['counts']['count'] = ['value' => $dao->count,
        'title' => 'Total Participants',
        'type' => CRM_Utils_Type::T_INT,
      ];
      $statistics['counts']['amount'] = ['value' => $dao->amount,
        'title' => 'Total Income',
        'type' => CRM_Utils_Type::T_MONEY,
      ];
      $statistics['counts']['avg	  '] = ['value' => $avg,
        'title' => 'Average',
        'type' => CRM_Utils_Type::T_MONEY,
      ];
    }

    return $statistics;
  }

  function select() {
    $select = [];
    $this->_columnHeaders = [];

    //add blank column at the Start
    if (CRM_Utils_Array::value('blank_column_begin', $this->_params['options'])) {
      $select[] = " '' as blankColumnBegin";
      $this->_columnHeaders['blankColumnBegin']['title'] = '_ _ _ _';
    }
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if (CRM_Utils_Array::value('statistics', $field)) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }
    //add blank column at the end
    if ($blankcols = CRM_Utils_Array::value('blank_column_end', $this->_params)) {
      for ($i = 1; $i <= $blankcols; $i++) {
        $select[] = " '' as blankColumnEnd_{$i}";
        $this->_columnHeaders["blank_{$i}"]['title'] = "_ _ _ _";
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
		  FROM civicrm_participant {$this->_aliases['civicrm_participant']}
				 LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']} 
						  ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND ({$this->_aliases['civicrm_event']}.is_template IS NULL OR {$this->_aliases['civicrm_event']}.is_template = 0)
				 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
						  ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id	)
				 {$this->_aclFrom}
				 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
						  ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
							  {$this->_aliases['civicrm_address']}.is_primary = 1 
				 LEFT JOIN	civicrm_email {$this->_aliases['civicrm_email']} 
						  ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
							  {$this->_aliases['civicrm_email']}.is_primary = 1) 
				 LEFT JOIN civicrm_line_item {$this->_aliases['civicrm_line_item']}
						  ON {$this->_aliases['civicrm_participant']}.id ={$this->_aliases['civicrm_line_item']}.entity_id AND {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant'";
  }

  function where() {
    $clauses = [];
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

            if ($fieldName == 'rid') {
              $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (!empty($value)) {
                $clause = "( {$field['dbAlias']} REGEXP '[[:<:]]" . CRM_Utils_Array::implode('[[:>:]]|[[:<:]]', $value) . "[[:>:]]' )";
              }
              $op = NULL;
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
    }

    if (empty($clauses)) {
      $this->_where = "WHERE {$this->_aliases['civicrm_participant']}.is_test = 0 ";
    }
    else {
      $this->_where = "WHERE {$this->_aliases['civicrm_participant']}.is_test = 0 AND " . CRM_Utils_Array::implode(' AND ', $clauses);
    }
    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = "";
    if (CRM_Utils_Array::value('group_bys', $this->_params) &&
      is_array($this->_params['group_bys']) &&
      !empty($this->_params['group_bys'])
    ) {
      foreach ($this->_columns as $tableName => $table) {
        if (CRM_Utils_Array::arrayKeyExists('group_bys', $table)) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
              $this->_groupBy[] = $field['dbAlias'];
            }
          }
        }
      }
    }

    if (!empty($this->_groupBy)) {
      $this->_groupBy = "ORDER BY " . CRM_Utils_Array::implode(', ', $this->_groupBy) . ", {$this->_aliases['civicrm_contact']}.sort_name";
    }
    else {
      $this->_groupBy = "ORDER BY {$this->_aliases['civicrm_contact']}.sort_name";
    }
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_participant']}.id " . $this->_groupBy;
  }

  function postProcess() {

    // get ready with post process params
    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    // build query
    $sql = $this->buildQuery(TRUE);

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {

    $entryFound = FALSE;
    $eventType = CRM_Core_OptionGroup::values('event_type');

    foreach ($rows as $rowNum => $row) {

      // convert sort name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        if ($value = $row['civicrm_contact_sort_name']) {
          $url = CRM_Utils_System::url("civicrm/contact/view",
            'reset=1&cid=' . $row['civicrm_contact_id'],
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
          $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        }
        $entryFound = TRUE;
      }

      // convert participant ID to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_participant_participant_id', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        if ($value = $row['civicrm_participant_participant_id']) {
          $url = CRM_Utils_System::url("civicrm/contact/view/participant",
            'reset=1&id=' . $row['civicrm_participant_participant_id'] . '&cid=' . $row['civicrm_contact_id'] . '&action=view',
            $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_participant_participant_id_link'] = $url;
          $rows[$rowNum]['civicrm_participant_participant_id_hover'] = ts("View Participant Record for this Contact.");
        }
        $entryFound = TRUE;
      }

      // convert event name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_participant_event_id', $row)) {
        if ($value = $row['civicrm_participant_event_id']) {
          $rows[$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($value, FALSE);
          $url = CRM_Report_Utils_Report::getNextUrl('event/Income',
            'reset=1&force=1&event_id_op=eq&event_id_value=' . $value,
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_participant_event_id_link'] = $url;
          $rows[$rowNum]['civicrm_participant_event_id_hover'] = ts("View Event Income Details for this Event");
        }
        $entryFound = TRUE;
      }

      // handle event type id
      if (CRM_Utils_Array::arrayKeyExists('civicrm_event_event_type_id', $row)) {
        if ($value = $row['civicrm_event_event_type_id']) {
          $rows[$rowNum]['civicrm_event_event_type_id'] = $eventType[$value];
        }
        $entryFound = TRUE;
      }

      // handle participant status id
      if (CRM_Utils_Array::arrayKeyExists('civicrm_participant_status_id', $row)) {
        if ($value = $row['civicrm_participant_status_id']) {
          $rows[$rowNum]['civicrm_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($value, FALSE, 'label');
        }
        $entryFound = TRUE;
      }

      // handle participant role id
      if (CRM_Utils_Array::arrayKeyExists('civicrm_participant_role_id', $row)) {
        if ($value = $row['civicrm_participant_role_id']) {
          $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $value = [];
          foreach ($roles as $role) {
            $value[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
          }
          $rows[$rowNum]['civicrm_participant_role_id'] = CRM_Utils_Array::implode(', ', $value);
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

