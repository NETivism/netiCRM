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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Report_Form_Contact_Participate extends CRM_Report_Form {

  /**
   * Table alias map keyed by table name.
   *
   * @var array<string, string>
   */
  public $_aliases;

  /**
   * The SQL FROM clause built by from().
   *
   * @var string
   */
  public $_from;

  /**
   * The SQL WHERE clause built by where().
   *
   * @var string
   */
  public $_where;

  /**
   * Column header definitions keyed by column alias.
   *
   * @var array<string, array<string, mixed>>
   */
  public $_columnHeaders;

  /**
   * The SQL GROUP BY clause built by groupBy().
   *
   * @var string|string[]
   */
  public $_groupBy;

  /**
   * Legacy alias for $_groupBy (unused).
   *
   * @var string
   */
  public $groupBy;

  /**
   * The SQL ORDER BY clause built by orderBy().
   *
   * @var string
   */
  public $_orderBy;

  /**
   * Whether to generate absolute URLs in alterDisplay().
   *
   * @var bool
   */
  public $_absoluteUrl;

  /**
   * Summary value (unused placeholder).
   *
   * @var null
   */
  protected $_summary = NULL;

  /**
   * Entity type whose custom fields are available in this report.
   *
   * @var string[]
   */
  protected $_customGroupExtends = ['Participant'];

  /**
   * Initialises column definitions for contact, email, address, participant, event,
   * and line item tables.
   */
  public function __construct() {
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
          ['required' => TRUE,
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
            'no_display' => TRUE,
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
        [
          'participant_count' => [
            'title' => ts('Count'),
            'type' => CRM_Utils_Type::T_INT,
            'default_op' => 'gte',
          ],
          'event_id' =>
          ['name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::event(NULL, NULL, "is_template IS NULL OR is_template = 0"),
          ],
          'sid' =>
          ['name' => 'status_id',
            'title' => ts('Participant Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
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
      ],
      'civicrm_line_item' =>
      ['dao' => 'CRM_Price_DAO_LineItem',
      ],
    ];

    parent::__construct();
  }

  /**
   * Delegates to the parent preProcess().
   *
   * @return void
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Computes report statistics by delegating to the parent, then adds a 'Total Participants'
   * count derived from a separate COUNT query on the assembled FROM/WHERE clauses.
   *
   * @param array &$rows Report result rows passed by reference.
   *
   * @return array Statistics array with 'counts' and 'filters' entries.
   */
  public function statistics(&$rows) {
    $statistics = parent::statistics($rows);
    $avg = NULL;
    $select = " SELECT COUNT( {$this->_aliases['civicrm_participant']}.id ) as count	";
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
    }

    return $statistics;
  }

  /**
   * Builds the SELECT clause from selected and required fields, then appends a
   * COUNT(participant.id) as participant_count column. Populates $_select and $_columnHeaders.
   *
   * @return void
   */
  public function select() {
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
    $select[] = "COUNT({$this->_aliases['civicrm_participant']}.id) as participant_count";
    $this->_columnHeaders["participant_count"]['title'] = ts('Count');
    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  /**
   * Form validation callback. No validation rules for this report.
   *
   * @param array $fields Submitted form values (unused).
   * @param array $files Uploaded files (unused).
   * @param CRM_Report_Form_Contact_Participate $self The form instance (unused).
   *
   * @return array Empty array (no errors).
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    return $errors;
  }

  /**
   * Builds the FROM clause joining contact to participant, event, address, and email tables.
   * Non-template events are excluded via the is_template condition. Populates $_from.
   *
   * @return void
   */
  public function from() {
    $this->_from = "
       FROM civicrm_contact {$this->_aliases['civicrm_contact']}
         LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
              ON ({$this->_aliases['civicrm_participant']}.contact_id = {$this->_aliases['civicrm_contact']}.id )
				 LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']} 
						  ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND ({$this->_aliases['civicrm_event']}.is_template IS NULL OR {$this->_aliases['civicrm_event']}.is_template = 0)
				 {$this->_aclFrom}
				 LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
						  ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
							  {$this->_aliases['civicrm_address']}.is_primary = 1 
				 LEFT JOIN	civicrm_email {$this->_aliases['civicrm_email']} 
						  ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
							  {$this->_aliases['civicrm_email']}.is_primary = 1)";
  }

  /**
   * Builds the WHERE clause from submitted filter values. The 'rid' (role ID) filter uses
   * a REGEXP match against a separator-delimited field. The participant_count filter is
   * handled in groupBy() via HAVING, not here. Always excludes test participants.
   * Appends ACL restrictions. Populates $_where.
   *
   * @return void
   */
  public function where() {
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
              if ($field['name'] == 'participant_count') {
                continue;
              }
              $clause = $this->whereClause(
                $field,
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

  /**
   * Builds the GROUP BY clause grouping by participant contact_id. When a participant_count
   * filter is provided, appends a HAVING clause comparing COUNT(participant.id) to the
   * specified value. Populates $_groupBy.
   *
   * @return void
   */
  public function groupBy() {
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

    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_participant']}.contact_id ";

    $op = CRM_Utils_Array::value("participant_count_op", $this->_params);
    $value = CRM_Utils_Array::value("participant_count_value", $this->_params);
    if (!empty($op) && !empty($value) && is_numeric($value)) {
      if (!empty($this->_columns['civicrm_participant']['filters']['participant_count'])) {
        $pcount = $this->_columns['civicrm_participant']['filters']['participant_count'];
        $pcount['dbAlias'] = "COUNT({$pcount['alias']}.id)";
        $clause = $this->whereClause($pcount, $op, $value, NULL, NULL);
        $this->_groupBy .= ' HAVING ' . $clause;
        print $this->groupBy;
      }
    }
  }

  /**
   * Builds the ORDER BY clause sorting by participant_count descending. Populates $_orderBy.
   *
   * @return void
   */
  public function orderBy() {
    $this->_orderBy = " ORDER BY participant_count DESC ";
  }

  /**
   * Builds the ACL clause, assembles and runs the query, formats and assigns
   * result rows to the template, then finalises the output.
   *
   * @return void
   */
  public function postProcess() {

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

  /**
   * Post-processes result rows to linkify contact names and participant IDs,
   * resolve event IDs (with link to income report), event type IDs,
   * participant status IDs, and participant role IDs to human-readable labels.
   *
   * @param array &$rows Report result rows passed by reference.
   *
   * @return void
   */
  public function alterDisplay(&$rows) {

    $entryFound = FALSE;
    $eventType = CRM_Core_OptionGroup::values('event_type');

    foreach ($rows as $rowNum => $row) {

      // convert sort name to links
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_sort_name', $row) &&
        CRM_Utils_Array::arrayKeyExists('civicrm_contact_id', $row)
      ) {
        if ($value = $row['civicrm_contact_sort_name']) {
          $url = CRM_Utils_System::url(
            "civicrm/contact/view",
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
          $url = CRM_Utils_System::url(
            "civicrm/contact/view/participant",
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
          $url = CRM_Report_Utils_Report::getNextUrl(
            'event/Income',
            'reset=1&force=1&event_id_op=eq&event_id_value=' . $value,
            $this->_absoluteUrl,
            $this->_id
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
