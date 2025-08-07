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


class CRM_Report_Form_Activity extends CRM_Report_Form {

  /**
   * @var never[]
   */
  public $_columnHeaders;
  public $_from;
  public $_aliases;
  public $_where;
  /**
   * @var never[]|mixed[]|string[]|string
   */
  public $_groupBy;
  public $_absoluteUrl;
  protected $_emailField = FALSE;
  protected $_customGroupExtends = ['Activity']; function __construct() {
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    $this->_columns = [
      'civicrm_contact' =>
      ['dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        [
          'source_contact_id' =>
          ['name' => 'id',
            'alias' => 'contact_civireport',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'contact_source' =>
          ['name' => 'display_name',
            'title' => ts('Source Contact Name'),
            'alias' => 'contact_civireport',
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'contact_assignee' =>
          ['name' => 'display_name',
            'title' => ts('Assignee Contact Name'),
            'alias' => 'civicrm_contact_assignee',
            'default' => TRUE,
          ],
          'contact_target' =>
          ['name' => 'display_name',
            'title' => ts('Target Contact Name'),
            'alias' => 'civicrm_contact_target',
            'default' => TRUE,
          ],
        ],
        'filters' =>
        ['contact_source' =>
          ['name' => 'sort_name',
            'alias' => 'contact_civireport',
            'title' => ts('Source Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'contact_assignee' =>
          ['name' => 'sort_name',
            'alias' => 'civicrm_contact_assignee',
            'title' => ts('Assignee Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
          'contact_target' =>
          ['name' => 'sort_name',
            'alias' => 'civicrm_contact_target',
            'title' => ts('Target Contact Name'),
            'operator' => 'like',
            'type' => CRM_Report_Form::OP_STRING,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' =>
      ['dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        ['contact_source_email' =>
          ['name' => 'email',
            'title' => ts('Source Contact Email'),
            'alias' => 'civicrm_email_source',
          ],
          'contact_assignee_email' =>
          ['name' => 'email',
            'title' => ts('Assignee Contact Email'),
            'alias' => 'civicrm_email_assignee',
          ],
          'contact_target_email' =>
          ['name' => 'email',
            'title' => ts('Target Contact Email'),
            'alias' => 'civicrm_email_target',
          ],
        ],
      ],
      'civicrm_activity' =>
      ['dao' => 'CRM_Activity_DAO_Activity',
        'fields' =>
        ['id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
          'activity_type_id' =>
          ['title' => ts('Activity Type'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'activity_subject' =>
          ['title' => ts('Subject'),
            'default' => TRUE,
          ],
          'source_contact_id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
          'activity_date_time' =>
          ['title' => ts('Activity Date'),
            'default' => TRUE,
          ],
          'status_id' =>
          ['title' => ts('Activity Status'),
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'duration' =>
          ['title' => ts('Duration'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'filters' =>
        ['activity_date_time' =>
          ['default' => 'this.month',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'activity_subject' =>
          ['title' => ts('Activity Subject')],
          'activity_type_id' =>
          ['title' => ts('Activity Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'label', TRUE),
          ],
          'status_id' =>
          ['title' => ts('Activity Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::activityStatus(),
          ],
        ],
        'group_bys' =>
        ['source_contact_id' =>
          ['title' => ts('Source Contact'),
            'default' => TRUE,
          ],
          'activity_date_time' =>
          ['title' => ts('Activity Date')],
          'activity_type_id' =>
          ['title' => ts('Activity Type')],
        ],
        'grouping' => 'activity-fields',
        'alias' => 'activity',
      ],
      'civicrm_activity_assignment' =>
      ['dao' => 'CRM_Activity_DAO_ActivityAssignment',
        'fields' =>
        [
          'assignee_contact_id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'alias' => 'activity_assignment',
      ],
      'civicrm_activity_target' =>
      ['dao' => 'CRM_Activity_DAO_ActivityTarget',
        'fields' =>
        [
          'target_contact_id' =>
          ['no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'alias' => 'activity_target',
      ],
      'civicrm_case_activity' =>
      ['dao' => 'CRM_Case_DAO_CaseActivity',
        'fields' =>
        [
          'case_id' =>
          ['name' => 'case_id',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'alias' => 'case_activity',
      ],
    ];

    if ($campaignEnabled) {
      // Add display column and filter for Survey Results if CiviCampaign is enabled
      $this->_columns['civicrm_activity']['fields']['result'] = ['title' => 'Survey Result',
        'default' => 'false',
      ];
      $this->_columns['civicrm_activity']['filters']['result'] = ['title' => ts('Survey Result'),
        'operator' => 'like',
        'type' => CRM_Utils_Type::T_STRING,
      ];
    }
    parent::__construct();
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

            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . CRM_Utils_Array::implode(', ', $select) . " ";
  }

  function from() {

    $this->_from = "
        FROM civicrm_activity {$this->_aliases['civicrm_activity']}
        
             LEFT JOIN civicrm_activity_target  {$this->_aliases['civicrm_activity_target']} 
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_target']}.activity_id 
             LEFT JOIN civicrm_activity_assignment {$this->_aliases['civicrm_activity_assignment']}
                    ON {$this->_aliases['civicrm_activity']}.id = {$this->_aliases['civicrm_activity_assignment']}.activity_id 
             LEFT JOIN civicrm_contact contact_civireport
                    ON {$this->_aliases['civicrm_activity']}.source_contact_id = contact_civireport.id 
             LEFT JOIN civicrm_contact civicrm_contact_target 
                    ON {$this->_aliases['civicrm_activity_target']}.target_contact_id = civicrm_contact_target.id
             LEFT JOIN civicrm_contact civicrm_contact_assignee 
                    ON {$this->_aliases['civicrm_activity_assignment']}.assignee_contact_id = civicrm_contact_assignee.id
            
             {$this->_aclFrom}
             LEFT JOIN civicrm_option_value 
                    ON ( {$this->_aliases['civicrm_activity']}.activity_type_id = civicrm_option_value.value )
             LEFT JOIN civicrm_option_group 
                    ON civicrm_option_group.id = civicrm_option_value.option_group_id
             LEFT JOIN civicrm_case_activity case_activity_civireport 
                    ON case_activity_civireport.activity_id = {$this->_aliases['civicrm_activity']}.id
             LEFT JOIN civicrm_case 
                    ON case_activity_civireport.case_id = civicrm_case.id
             LEFT JOIN civicrm_case_contact 
                    ON civicrm_case_contact.case_id = civicrm_case.id ";

    if ($this->_emailField) {
      $this->_from .= "
            LEFT JOIN civicrm_email civicrm_email_source 
                   ON {$this->_aliases['civicrm_activity']}.source_contact_id = civicrm_email_source.contact_id AND
                      civicrm_email_source.is_primary = 1 

            LEFT JOIN civicrm_email civicrm_email_target 
                   ON {$this->_aliases['civicrm_activity_target']}.target_contact_id = civicrm_email_target.contact_id AND 
                      civicrm_email_target.is_primary = 1

            LEFT JOIN civicrm_email civicrm_email_assignee 
                   ON {$this->_aliases['civicrm_activity_assignment']}.assignee_contact_id = civicrm_email_assignee.contact_id AND 
                      civicrm_email_assignee.is_primary = 1 ";
    }
  }

  function where() {
    $this->_where = " WHERE civicrm_option_group.name = 'activity_type' AND 
                                {$this->_aliases['civicrm_activity']}.is_test = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_deleted = 0 AND
                                {$this->_aliases['civicrm_activity']}.is_current_revision = 1";

    $clauses = [];
    foreach ($this->_columns as $tableName => $table) {
      if (CRM_Utils_Array::arrayKeyExists('filters', $table)) {

        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
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
      $this->_where .= " ";
    }
    else {
      $this->_where .= " AND " . CRM_Utils_Array::implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function groupBy() {
    $this->_groupBy = [];
    if (!empty($this->_params['group_bys'])) {
      foreach ($this->_columns as $tableName => $table) {
        if (!empty($table['group_bys'])) {
          foreach ($table['group_bys'] as $fieldName => $field) {
            if (CRM_Utils_Array::value($fieldName, $this->_params['group_bys'])) {
              $this->_groupBy[] = $field['dbAlias'];
            }
          }
        }
      }
    }
    $this->_groupBy[] = "{$this->_aliases['civicrm_activity']}.id";
    $this->_groupBy = "GROUP BY " . CRM_Utils_Array::implode(', ', $this->_groupBy) . " ";
  }

  function buildACLClause($tableAlias = 'contact_a') {
    //override for ACL( Since Cotact may be source
    //contact/assignee or target also it may be null )

    /*


        if ( CRM_Core_Permission::check( 'view all contacts' ) ) {
            $this->_aclFrom = $this->_aclWhere = null;
            return;
        }

        $session = CRM_Core_Session::singleton( );
        $contactID =  $session->get( 'userID' );
        if ( ! $contactID ) {
            $contactID = 0;
        }
        $contactID = CRM_Utils_Type::escape( $contactID, 'Integer' );

        CRM_Contact_BAO_Contact_Permission::cache( $contactID );
        $clauses = array();
        foreach( $tableAlias as $k => $alias ) {
            $clauses[] = " INNER JOIN civicrm_acl_contact_cache aclContactCache_{$k} ON ( {$alias}.id = aclContactCache_{$k}.contact_id OR {$alias}.id IS NULL ) AND aclContactCache_{$k}.user_id = $contactID ";  
        }

        $this->_aclFrom  = CRM_Utils_Array::implode(" ", $clauses );
        $this->_aclWhere = null;
        */
  }

  function postProcess() {

    $this->buildACLClause(['contact_civireport', 'civicrm_contact_target', 'civicrm_contact_assignee']);
    parent::postProcess();
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $activityType = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();
    $viewLinks = FALSE;


    if (CRM_Core_Permission::check('access CiviCRM')) {
      $viewLinks = TRUE;
      $onHover = ts('View Contact Summary for this Contact');
      $onHoverAct = ts('View Activity Record');
    }
    foreach ($rows as $rowNum => $row) {

      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_contact_source', $row)) {
        if ($value = $row['civicrm_contact_source_contact_id']) {
          if ($viewLinks) {
            $url = CRM_Utils_System::url("civicrm/contact/view",
              'reset=1&cid=' . $value,
              $this->_absoluteUrl
            );
            $rows[$rowNum]['civicrm_contact_contact_source_link'] = $url;
            $rows[$rowNum]['civicrm_contact_contact_source_hover'] = $onHover;
          }
          $entryFound = TRUE;
        }
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_contact_assignee', $row) &&
        $row['civicrm_activity_assignment_assignee_contact_id']
      ) {
        $assignee = [];
        //retrieve all contact assignees and build list with links

        $activity_assignment_ids = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames($row['civicrm_activity_id'], FALSE, TRUE);
        foreach ($activity_assignment_ids as $cid => $assignee_name) {
          if ($viewLinks) {
            $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $cid, $this->_absoluteUrl);
            $assignee[] = '<a title="' . $onHover . '" href="' . $url . '">' . $assignee_name . '</a>';
          }
          else {
            $assignee[] = $assignee_name;
          }
        }
        $rows[$rowNum]['civicrm_contact_contact_assignee'] = CRM_Utils_Array::implode('; ', $assignee);
        $entryFound = TRUE;
      }
      if (CRM_Utils_Array::arrayKeyExists('civicrm_contact_contact_target', $row) &&
        $row['civicrm_activity_target_target_contact_id']
      ) {
        $target = [];
        //retrieve all contact targets and build list with links

        $activity_target_ids = CRM_Activity_BAO_ActivityTarget::getTargetNames($row['civicrm_activity_id']);
        foreach ($activity_target_ids as $cid => $target_name) {
          if ($viewLinks) {
            $url = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $cid, $this->_absoluteUrl);
            $target[] = '<a title="' . $onHover . '" href="' . $url . '">' . $target_name . '</a>';
          }
          else {
            $target[] = $target_name;
          }
        }
        $rows[$rowNum]['civicrm_contact_contact_target'] = CRM_Utils_Array::implode('; ', $target);
        $entryFound = TRUE;
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_activity_type_id', $row)) {
        if ($value = $row['civicrm_activity_activity_type_id']) {
          $rows[$rowNum]['civicrm_activity_activity_type_id'] = $activityType[$value];
          if ($viewLinks) {
            // case activities get a special view link
            if ($rows[$rowNum]['civicrm_case_activity_case_id']) {
              $url = CRM_Utils_System::url("civicrm/case/activity/view",
                'reset=1&cid=' . $rows[$rowNum]['civicrm_contact_source_contact_id'] .
                '&aid=' . $rows[$rowNum]['civicrm_activity_id'] . '&caseID=' . $rows[$rowNum]['civicrm_case_activity_case_id'],
                $this->_absoluteUrl
              );
            }
            else {
              $url = CRM_Utils_System::url("civicrm/contact/view/activity",
                'action=view&reset=1&cid=' . $rows[$rowNum]['civicrm_contact_source_contact_id'] .
                '&id=' . $rows[$rowNum]['civicrm_activity_id'] . '&atype=' . $value,
                $this->_absoluteUrl
              );
            }
            $rows[$rowNum]['civicrm_activity_activity_type_id_link'] = $url;
            $rows[$rowNum]['civicrm_activity_activity_type_id_hover'] = $onHoverAct;
          }
          $entryFound = TRUE;
        }
      }

      if (CRM_Utils_Array::arrayKeyExists('civicrm_activity_status_id', $row)) {
        if ($value = $row['civicrm_activity_status_id']) {
          $rows[$rowNum]['civicrm_activity_status_id'] = $activityStatus[$value];
          $entryFound = TRUE;
        }
      }

      if (!$entryFound) {
        break;
      }
    }
  }
}

