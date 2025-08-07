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


class CRM_Contact_Form_Search_Custom_FullText implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;

  protected $_columns;

  protected $_text = NULL;

  protected $_textID = NULL;

  protected $_table = NULL;

  protected $_tableName = NULL;

  protected $_entityIDTableName = NULL;

  protected $_tableFields = NULL;

  protected $_limitClause = NULL;

  protected $_limitRowClause = NULL;

  protected $_limitNumber = 10;

  protected $_foundRows = [];

  function __construct(&$formValues) {
    $formValues['table'] = $this->getFieldValue($formValues, 'table', 'String');
    $this->_table = $formValues['table'];

    $formValues['text'] = trim($this->getFieldValue($formValues, 'text', 'String', ''));
    $this->_text = $formValues['text'];

    if(empty($formValues['text'])){
      return;
    }
    $this->_formValues = &$formValues;

    // fix text to include wild card characters at begining and end
    if ($this->_text) {
      if (is_numeric($this->_text)) {
        $this->_textID = $this->_text;
      }

      $this->_text = mb_strtolower(CRM_Core_DAO::escapeString($this->_text), 'UTF-8');
      if (strpos($this->_text, '%') === FALSE) {
        $this->_text = "'%{$this->_text}%'";
      }
      else {
        $this->_text = "'{$this->_text}'";
      }
    }
    else {
      $this->_text = "'%'";
    }

    if (!$this->_table) {
      $this->_limitClause = " LIMIT {$this->_limitNumber}";
      $this->_limitRowClause = $this->_limitClause;
    }
    else {
      // when there is table specified, we would like to use the pager. But since
      // 1. this custom search has slightly different structure ,
      // 2. we are in constructor right now,
      // we 'll use a small hack -
      $rowCount = CRM_Utils_Pager::ROWCOUNT;
      $pageId = CRM_Utils_Array::value('crmPID', $_REQUEST);
      $pageId = $pageId ? $pageId : 1;
      $offset = ($pageId - 1) * $rowCount;
      $this->_limitClause = " LIMIT $offset, $rowCount";
      $this->_limitRowClause = " LIMIT $rowCount";
    }

    $this->buildTempTable();

    $this->fillTable();
  }

  /**
   * Get a value from $formValues. If missing, get it from the request.
   *
   * @param $formValues
   * @param $field
   * @param $type
   * @param null $default
   * @return mixed|null
   */
  public function getFieldValue($formValues, $field, $type, $default = NULL) {
    $value = CRM_Utils_Array::value($field, $formValues);
    if (!$value) {
      return CRM_Utils_Request::retrieve($field, $type, CRM_Core_DAO::$_nullObject, FALSE, $default);
    }
    return $value;
  }


  function __destruct() {}

  function buildTempTable() {
    $randomNum = md5(uniqid());
    $this->_tableName = "civicrm_temp_custom_details_{$randomNum}";

    $this->_tableFields = [
      'id' => 'int unsigned NOT NULL AUTO_INCREMENT',
      'table_name' => 'varchar(16)',
      'contact_id' => 'int unsigned',
      'sort_name' => 'varchar(128)',
      'assignee_contact_id' => 'int unsigned',
      'assignee_sort_name' => 'varchar(128)',
      'target_contact_id' => 'int unsigned',
      'target_sort_name' => 'varchar(128)',
      'activity_id' => 'int unsigned',
      'activity_type_id' => 'int unsigned',
      'client_id' => 'int unsigned',
      'case_id' => 'int unsigned',
      'case_start_date' => 'datetime',
      'case_end_date' => 'datetime',
      'case_is_deleted' => 'tinyint',
      'subject' => 'varchar(255)',
      'details' => 'varchar(255)',
      'contribution_id' => 'int unsigned',
      'contribution_type' => 'varchar(255)',
      'contribution_page' => 'varchar(255)',
      'contribution_receive_date' => 'datetime',
      'contribution_total_amount' => 'decimal(20,2)',
      'contribution_trxn_Id' => 'varchar(255)',
      'contribution_source' => 'varchar(255)',
      'contribution_status' => 'varchar(255)',
      'contribution_check_number' => 'varchar(255)',
      'participant_id' => 'int unsigned',
      'event_title' => 'varchar(255)',
      'participant_fee_level' => 'varchar(255)',
      'participant_fee_amount' => 'int unsigned',
      'participant_source' => 'varchar(255)',
      'participant_register_date' => 'datetime',
      'participant_status' => 'varchar(255)',
      'participant_role' => 'varchar(255)',
      'membership_id' => 'int unsigned',
      'membership_fee' => 'int unsigned',
      'membership_type' => 'varchar(255)',
      'membership_start_date' => 'datetime',
      'membership_end_date' => 'datetime',
      'membership_source' => 'varchar(255)',
      'membership_status' => 'varchar(255)',
    ];

    $sql = "
CREATE TEMPORARY TABLE {$this->_tableName} (
";

    foreach ($this->_tableFields as $name => $desc) {
      $sql .= "$name $desc,\n";
    }

    $sql .= "
  PRIMARY KEY ( id )
) ENGINE=HEAP DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
";
    CRM_Core_DAO::executeQuery($sql);

    $this->_entityIDTableName = "civicrm_temp_custom_entityID_{$randomNum}";
    $sql = "
CREATE TEMPORARY TABLE {$this->_entityIDTableName} (
  id int unsigned NOT NULL AUTO_INCREMENT,
  entity_id int unsigned NOT NULL,
  
  UNIQUE INDEX unique_entity_id ( entity_id ),
  PRIMARY KEY ( id )
) ENGINE=HEAP DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
";
    CRM_Core_DAO::executeQuery($sql);
  }

  function fillTable() {

    $config = CRM_Core_Config::singleton();

    if ((!$this->_table ||
        $this->_table == 'Contact'
      )) {
      $this->fillContact();
    }

    if ((!$this->_table ||
        $this->_table == 'Activity'
      ) && CRM_Core_Permission::check('view all activities')) {
      $this->fillActivity();
    }

    if ((!$this->_table ||
        $this->_table == 'Case'
      ) && in_array('CiviCase', $config->enableComponents)) {
      $this->fillCase();
    }

    if ((!$this->_table ||
        $this->_table == 'Contribution'
      ) && in_array('CiviContribute', $config->enableComponents)) {
      $this->fillContribution();
    }

    if ((!$this->_table ||
        $this->_table == 'Participant'
      ) &&
      (in_array('CiviEvent', $config->enableComponents) && CRM_Core_Permission::check('view event participants'))
    ) {
      $this->fillParticipant();
    }

    if ((!$this->_table ||
        $this->_table == 'Membership'
      ) && in_array('CiviMember', $config->enableComponents)) {
      $this->fillMembership();
    }

    $this->filterACLContacts();
  }

  function filterACLContacts() {
    if (CRM_Core_Permission::check('view all contacts')) {
      CRM_Core_DAO::executeQuery("DELETE FROM {$this->_tableName} WHERE contact_id IN (SELECT id FROM civicrm_contact WHERE is_deleted = 1)");
      return;
    }

    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!$contactID) {
      $contactID = 0;
    }


    CRM_Contact_BAO_Contact_Permission::cache($contactID);

    $params = [1 => [$contactID, 'Integer']];

    $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      NOT EXISTS ( SELECT c.id 
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND t.contact_id = c.contact_id )
";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.id 
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.target_contact_id = c.contact_id OR t.target_contact_id IS NULL ) )
";
    CRM_Core_DAO::executeQuery($sql, $params);

    $sql = "
DELETE     t.*
FROM       {$this->_tableName} t
WHERE      t.table_name = 'Activity' AND
           NOT EXISTS ( SELECT c.id 
                        FROM civicrm_acl_contact_cache c
                        WHERE c.user_id = %1 AND ( t.assignee_contact_id = c.contact_id OR t.assignee_contact_id IS NULL ) )
";
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  function fillCustomInfo(&$tables,
    $extends
  ) {

    $sql = "
SELECT     cg.table_name, cf.column_name
FROM       civicrm_custom_group cg
INNER JOIN civicrm_custom_field cf ON cf.custom_group_id = cg.id
WHERE      cg.extends IN $extends
AND        cg.is_active = 1
AND        cf.is_active = 1
AND        cf.is_searchable = 1
AND        cf.html_type IN ( 'Text', 'TextArea', 'RichTextEditor' )
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      if (!CRM_Utils_Array::arrayKeyExists($dao->table_name,
          $tables
        )) {
        $tables[$dao->table_name] = ['id' => 'entity_id',
          'fields' => [],
        ];
      }
      $tables[$dao->table_name]['fields'][$dao->column_name] = NULL;
    }
  }

  function runQueries(&$tables) {
    $sql = "TRUNCATE {$this->_entityIDTableName}";
    CRM_Core_DAO::executeQuery($sql);

    $maxRowCount = 0;
    foreach ($tables as $tableName => $tableValues) {
      if ($tableName == 'sql') {
        foreach ($tableValues as $sqlStatement) {
          $sqlStatement = str_ireplace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $sqlStatement);
          $sql = "
REPLACE INTO {$this->_entityIDTableName} ( entity_id )
$sqlStatement
{$this->_limitClause}
";
          CRM_Core_DAO::executeQuery($sql);
          $totalRows = CRM_Core_DAO::singleValueQuery("SELECT FOUND_ROWS();");
          $maxRowCount = ($totalRows > $maxRowCount) ? $totalRows : $maxRowCount;
        }
      }
      else {
        $clauses = [];

        foreach ($tableValues['fields'] as $fieldName => $fieldType) {
          if ($fieldType == 'Int') {
            if ($this->_textID) {
              $clauses[] = "$fieldName = {$this->_textID}";
            }
          }
          else {
            $clauses[] = "$fieldName LIKE {$this->_text}";
          }
        }

        if (empty($clauses)) {
          continue;
        }

        $whereClause = CRM_Utils_Array::implode(' OR ', $clauses);

        //resolve conflict between entity tables.
        if ($tableName == 'civicrm_note' &&
          $entityTable = CRM_Utils_Array::value('entity_table', $tableValues)
        ) {
          $whereClause .= " AND entity_table = '{$entityTable}'";
        }

        $sql = "
REPLACE INTO {$this->_entityIDTableName} ( entity_id )
SELECT  SQL_CALC_FOUND_ROWS distinct {$tableValues['id']}
FROM    $tableName
WHERE   ( $whereClause )
AND     {$tableValues['id']} IS NOT NULL
{$this->_limitClause}
";
        CRM_Core_DAO::executeQuery($sql);
        $totalRows = CRM_Core_DAO::singleValueQuery("SELECT FOUND_ROWS();");
        $maxRowCount = ($totalRows > $maxRowCount) ? $totalRows : $maxRowCount;
      }
    }

    $tableKey = array_keys($tables);
    $this->_foundRows[ucfirst(str_replace('civicrm_', '', $tableKey[0]))] = $maxRowCount;
  }

  function fillContactIDs() {
    $tables = ['civicrm_contact' => ['id' => 'id',
        'fields' => ['sort_name' => NULL,
          'nick_name' => NULL,
        ],
      ],
      'civicrm_address' => ['id' => 'contact_id',
        'fields' => ['street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
        ],
      ],
      'civicrm_email' => ['id' => 'contact_id',
        'fields' => ['email' => NULL],
      ],
      'civicrm_phone' => ['id' => 'contact_id',
        'fields' => ['phone' => NULL],
      ],
      'civicrm_note' => ['id' => 'entity_id',
        'entity_table' => 'civicrm_contact',
        'fields' => ['subject' => NULL,
          'note' => NULL,
        ],
      ],
    ];

    // get the custom data info
    $this->fillCustomInfo($tables,
      "( 'Contact', 'Individual', 'Organization', 'Household' )"
    );

    $this->runQueries($tables);
  }

  function fillContact() {

    $this->fillContactIDs();

    //move data from entity table to detail table.
    $this->moveEntityToDetail('Contact');
  }

  function fillActivityIDs() {
    $contactSQL = [];

    $contactSQL[] = "
SELECT     distinct ca.id 
FROM       civicrm_activity ca
INNER JOIN civicrm_contact c ON ca.source_contact_id = c.id
LEFT JOIN  civicrm_email e ON e.contact_id = c.id
LEFT JOIN  civicrm_option_group og ON og.name = 'activity_type'
LEFT JOIN  civicrm_option_value ov ON ( ov.option_group_id = og.id ) 
WHERE      c.sort_name LIKE {$this->_text} OR
           ( e.email LIKE {$this->_text}    AND 
             ca.activity_type_id = ov.value AND
             ov.name IN ('Inbound Email', 'Email') )
AND (ca.is_deleted = 0 OR ca.is_deleted IS NULL OR
     c.is_deleted = 0 OR c.is_deleted IS NULL)
";

    $contactSQL[] = "
SELECT     distinct ca.id 
FROM       civicrm_activity ca
INNER JOIN civicrm_activity_target cat ON cat.activity_id = ca.id
INNER JOIN civicrm_contact c ON cat.target_contact_id = c.id
LEFT  JOIN civicrm_email e ON cat.target_contact_id = e.contact_id
LEFT  JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT  JOIN civicrm_option_value ov ON ( ov.option_group_id = og.id ) 
WHERE      c.sort_name LIKE {$this->_text} OR
           ( e.email LIKE {$this->_text}    AND 
             ca.activity_type_id = ov.value AND
             ov.name IN ('Inbound Email', 'Email') )
AND (ca.is_deleted = 0 OR ca.is_deleted IS NULL OR
     c.is_deleted = 0 OR c.is_deleted IS NULL)
";

    $contactSQL[] = "
SELECT     distinct ca.id 
FROM       civicrm_activity ca
INNER JOIN civicrm_activity_assignment caa ON caa.activity_id = ca.id
INNER JOIN civicrm_contact c ON caa.assignee_contact_id = c.id
LEFT  JOIN civicrm_email e ON caa.assignee_contact_id = e.contact_id
LEFT  JOIN civicrm_option_group og ON og.name = 'activity_type'
LEFT  JOIN civicrm_option_value ov ON ( ov.option_group_id = og.id )
WHERE      caa.activity_id = ca.id
AND        caa.assignee_contact_id = c.id
AND        c.sort_name LIKE {$this->_text}  OR
           ( e.email LIKE {$this->_text} AND
             ca.activity_type_id = ov.value AND
             ov.name IN ('Inbound Email', 'Email') )
AND (ca.is_deleted = 0 OR ca.is_deleted IS NULL OR
     c.is_deleted = 0 OR c.is_deleted IS NULL)
";

    $tables = ['civicrm_activity' => ['id' => 'id',
        'fields' => ['subject' => NULL,
          'details' => NULL,
        ],
      ],
      'sql' => $contactSQL,
    ];

    $this->fillCustomInfo($tables, "( 'Activity' )");
    $this->runQueries($tables);
  }

  function fillActivity() {

    $this->fillActivityIDs();

    //move data from entity table to detail table
    $this->moveEntityToDetail('Activity');
  }

  function fillCase() {
    $maxRowCount = 0;
    $sql = "
INSERT INTO {$this->_tableName}
( table_name, contact_id, sort_name, case_id, case_start_date, case_end_date, case_is_deleted )
SELECT SQL_CALC_FOUND_ROWS 'Case', c.id, c.sort_name, cc.id, DATE(cc.start_date), DATE(cc.end_date), cc.is_deleted
FROM      civicrm_case cc 
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     c.sort_name LIKE {$this->_text}
          AND (cc.is_deleted = 0 OR cc.is_deleted IS NULL)
{$this->_limitClause}
";

    CRM_Core_DAO::executeQuery($sql);
    $totalRows = CRM_Core_DAO::singleValueQuery("SELECT FOUND_ROWS();");
    $maxRowCount = ($totalRows > $maxRowCount) ? $totalRows : $maxRowCount;

    if ($this->_textID) {
      $sql = "
INSERT INTO {$this->_tableName}
  ( table_name, contact_id, sort_name, case_id, case_start_date, case_end_date, case_is_deleted )
SELECT SQL_CALC_FOUND_ROWS 'Case', c.id, c.sort_name, cc.id, DATE(cc.start_date), DATE(cc.end_date), cc.is_deleted
FROM      civicrm_case cc 
LEFT JOIN civicrm_case_contact ccc ON cc.id = ccc.case_id
LEFT JOIN civicrm_contact c ON ccc.contact_id = c.id
WHERE     cc.id = {$this->_textID}
          AND (cc.is_deleted = 0 OR cc.is_deleted IS NULL)
{$this->_limitClause}
    ";

      CRM_Core_DAO::executeQuery($sql);
      $totalRows = CRM_Core_DAO::singleValueQuery("SELECT FOUND_ROWS();");
      $maxRowCount = ($totalRows > $maxRowCount) ? $totalRows : $maxRowCount;
    }

    $this->_foundRows['Case'] = $maxRowCount;
  }

  function fillContribution() {

    //get contribution ids in entity table.
    $this->fillContributionIDs();

    //move data from entity table to detail table
    $this->moveEntityToDetail('Contribution');
  }

  /**
   * get contribution ids in entity tables.
   */
  function fillContributionIDs() {
    $contactSQL = [];
    $contactSQL[] = "
SELECT     distinct cc.id 
FROM       civicrm_contribution cc
INNER JOIN civicrm_contact c ON cc.contact_id = c.id
WHERE      c.sort_name LIKE {$this->_text}
";
    $tables = ['civicrm_contribution' => ['id' => 'id',
        'fields' => ['source' => NULL,
          'amount_level' => NULL,
          'trxn_Id' => NULL,
          'invoice_id' => NULL,
          'check_number' => ($this->_textID) ? 'Int' : NULL,
          'total_amount' => ($this->_textID) ? 'Int' : NULL,
        ],
      ],
      'sql' => $contactSQL,
      'civicrm_note' => ['id' => 'entity_id',
        'entity_table' => 'civicrm_contribution',
        'fields' => ['subject' => NULL,
          'note' => NULL,
        ],
      ],
    ];

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Contribution' )");
    $this->runQueries($tables);
  }

  function fillParticipant() {
    //get participant ids in entity table.
    $this->fillParticipantIDs();

    //move data from entity table to detail table
    $this->moveEntityToDetail('Participant');
  }

  /**
   * get participant ids in entity tables.
   */
  function fillParticipantIDs() {
    $contactSQL = [];
    $contactSQL[] = "
SELECT     distinct cp.id 
FROM       civicrm_participant cp
INNER JOIN civicrm_contact c ON cp.contact_id = c.id
WHERE      c.sort_name LIKE {$this->_text}
";
    $tables = ['civicrm_participant' => ['id' => 'id',
        'fields' => ['source' => NULL,
          'fee_level' => NULL,
          'fee_amount' => ($this->_textID) ? 'Int' : NULL,
        ],
      ],
      'sql' => $contactSQL,
      'civicrm_note' => ['id' => 'entity_id',
        'entity_table' => 'civicrm_participant',
        'fields' => ['subject' => NULL,
          'note' => NULL,
        ],
      ],
    ];

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Participant' )");
    $this->runQueries($tables);
  }

  function fillMembership() {

    //get membership ids in entity table.
    $this->fillMembershipIDs();

    //move data from entity table to detail table
    $this->moveEntityToDetail('Membership');
  }

  /**
   * get membership ids in entity tables.
   */
  function fillMembershipIDs() {
    $contactSQL = [];
    $contactSQL[] = "
SELECT     distinct cm.id 
FROM       civicrm_membership cm
INNER JOIN civicrm_contact c ON cm.contact_id = c.id
WHERE      c.sort_name LIKE {$this->_text}
";
    $tables = ['civicrm_membership' => ['id' => 'id',
        'fields' => ['source' => NULL],
      ],
      'sql' => $contactSQL,
    ];

    // get the custom data info
    $this->fillCustomInfo($tables, "( 'Membership' )");
    $this->runQueries($tables);
  }

  function buildForm(&$form) {

    $config = CRM_Core_Config::singleton();

    $form->applyFilter('__ALL__', 'trim');
    $form->add('text',
      'text',
      ts('Find'),
      TRUE
    );

    // also add a select box to allow the search to be constrained
    $tables = ['' => ts('All tables')];
    if (CRM_Core_Permission::check('view all contacts')) {
      $tables['Contact'] = ts('Contacts');
    }
    if (CRM_Core_Permission::check('view all activities')) {
      $tables['Activity'] = ts('Activities');
    }
    if (in_array('CiviCase', $config->enableComponents)) {
      $tables['Case'] = ts('Cases');
    }
    if (in_array('CiviContribute', $config->enableComponents)) {
      $tables['Contribution'] = ts('Contributions');
    }
    if (in_array('CiviEvent', $config->enableComponents) && CRM_Core_Permission::check('view event participants')) {
      $tables['Participant'] = ts('Participants');
    }
    if (in_array('CiviMember', $config->enableComponents)) {
      $tables['Membership'] = ts('Memberships');
    }

    $form->add('select',
      'table',
      ts('Tables'),
      $tables
    );

    $form->assign('csID', CRM_Utils_Array::value('customSearchID', $this->_formValues));
  }

  function &columns() {
    $this->_columns = [ts('Contact Id') => 'contact_id',
      ts('Name') => 'sort_name',
    ];

    return $this->_columns;
  }

  function summary() {
    $summary = ['Contact' => [],
      'Activity' => [],
      'Case' => [],
      'Contribution' => [],
      'Participant' => [],
      'Membership' => [],
    ];


    // now iterate through the table and add entries to the relevant section
    $sql = "SELECT * FROM {$this->_tableName}";
    if ($this->_table) {
      $sql .= " {$this->_limitRowClause} ";
    }
    $dao = CRM_Core_DAO::executeQuery($sql);

    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE);

    $roleIds = CRM_Event_PseudoConstant::participantRole();
    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_tableFields as $name => $dontCare) {
        if ($name != 'activity_type_id') {
          $row[$name] = $dao->$name;
        }
        else {
          $row['activity_type'] = CRM_Utils_Array::value($dao->$name,
            $activityTypes
          );
        }
      }
      if (isset($row['participant_role'])) {
        $participantRole = explode(CRM_Core_DAO::VALUE_SEPARATOR, $row['participant_role']);
        $viewRoles = [];
        foreach ($participantRole as $k => $v) {
          $viewRoles[] = $roleIds[$v];
        }
        $row['participant_role'] = CRM_Utils_Array::implode(', ', $viewRoles);
      }
      $summary[$dao->table_name][] = $row;
    }

    $summary['Count'] = [];
    foreach (array_keys($summary) as $table) {
      $summary['Count'][$table] = $this->_foundRows[$table];
    }

    if (!$this->_table) {
      $summary['addShowAllLink'] = TRUE;
    }

    return $summary;
  }

  function count() {
    if ($this->_table) {
      return $this->_foundRows[$this->_table];
    }
    else {
      return CRM_Core_DAO::singleValueQuery("SELECT count(id) FROM {$this->_tableName}");
    }
  }

  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) {
    return CRM_Core_DAO::singleValueQuery("SELECT contact_id FROM {$this->_tableName}");
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL,
    $includeContactIDs = FALSE
  ) {
    $sql = "
SELECT 
  contact_a.contact_id   as contact_id  ,
  contact_a.sort_name as sort_name
FROM
  {$this->_tableName} contact_a
{$this->_limitRowClause}
";
    return $sql;
  }

  function from() {
    return NULL;
  }

  function where($includeContactIDs = FALSE) {
    return NULL;
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/FullText.tpl';
  }

  function setDefaultValues() {
    return [];
  }

  function alterRow(&$row) {}

  /**
   * get entity id retrieve related data from db and move all data to detail table.
   *
   */
  function moveEntityToDetail($tableName) {
    $sql = NULL;
    switch ($tableName) {
      case 'Contact':
        $sql = "
INSERT INTO {$this->_tableName}
( contact_id, sort_name, table_name )
SELECT     c.id, c.sort_name, 'Contact'
  FROM     {$this->_entityIDTableName} ct
INNER JOIN civicrm_contact c ON ct.entity_id = c.id
{$this->_limitRowClause}
";
        break;

      case 'Activity':
        $sql = "
INSERT INTO {$this->_tableName}
( table_name, activity_id, subject, details, contact_id, sort_name, assignee_contact_id, assignee_sort_name, target_contact_id, 
  target_sort_name, activity_type_id, case_id, client_id )
SELECT    'Activity', ca.id, substr(ca.subject, 1, 50), substr(ca.details, 1, 250),
           c1.id, c1.sort_name,
           c2.id, c2.sort_name,
           c3.id, c3.sort_name,
           ca.activity_type_id,
           cca.case_id,
           ccc.contact_id as client_id
FROM       {$this->_entityIDTableName} eid
INNER JOIN civicrm_activity ca ON ca.id = eid.entity_id
LEFT JOIN  civicrm_contact c1 ON ca.source_contact_id = c1.id
LEFT JOIN  civicrm_activity_assignment caa ON caa.activity_id = ca.id
LEFT JOIN  civicrm_contact c2 ON caa.assignee_contact_id = c2.id
LEFT JOIN  civicrm_activity_target cat ON cat.activity_id = ca.id
LEFT JOIN  civicrm_contact c3 ON cat.target_contact_id = c3.id
LEFT JOIN  civicrm_case_activity cca ON cca.activity_id = ca.id
LEFT JOIN  civicrm_case_contact ccc ON ccc.case_id = cca.case_id
WHERE (ca.is_deleted = 0 OR ca.is_deleted IS NULL)
{$this->_limitRowClause}
";
        break;

      case 'Contribution':
        $sql = "
INSERT INTO {$this->_tableName}
( table_name, contact_id, sort_name, contribution_id, contribution_type, contribution_page, contribution_receive_date, 
  contribution_total_amount, contribution_trxn_Id, contribution_source, contribution_status, contribution_check_number )
   SELECT  'Contribution', c.id, c.sort_name, cc.id, cct.name, ccp.title, cc.receive_date, 
           cc.total_amount, cc.trxn_id, cc.source, contribution_status.label, cc.check_number 
     FROM  {$this->_entityIDTableName} ct
INNER JOIN civicrm_contribution cc ON cc.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cc.contact_id = c.id
LEFT JOIN  civicrm_contribution_type cct ON cct.id = cc.contribution_type_id
LEFT JOIN  civicrm_contribution_page ccp ON ccp.id = cc.contribution_page_id 
LEFT JOIN  civicrm_option_group option_group_contributionStatus ON option_group_contributionStatus.name = 'contribution_status'
LEFT JOIN  civicrm_option_value contribution_status ON 
( contribution_status.option_group_id = option_group_contributionStatus.id AND contribution_status.value = cc.contribution_status_id )
{$this->_limitRowClause}
";
        break;

      case 'Participant':
        $sql = "
INSERT INTO {$this->_tableName}
( table_name, contact_id, sort_name, participant_id, event_title, participant_fee_level, participant_fee_amount, 
participant_register_date, participant_source, participant_status, participant_role )
   SELECT  'Participant', c.id, c.sort_name, cp.id, ce.title, cp.fee_level, cp.fee_amount, cp.register_date, cp.source, 
           participantStatus.label, cp.role_id
     FROM  {$this->_entityIDTableName} ct
INNER JOIN civicrm_participant cp ON cp.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cp.contact_id = c.id
LEFT JOIN  civicrm_event ce ON ce.id = cp.event_id
LEFT JOIN  civicrm_participant_status_type participantStatus ON participantStatus.id = cp.status_id
{$this->_limitRowClause}
";
        break;

      case 'Membership':
        $sql = " 
INSERT INTO {$this->_tableName}
( table_name, contact_id, sort_name, membership_id, membership_type, membership_fee, membership_start_date, 
membership_end_date, membership_source, membership_status )
   SELECT  'Membership', c.id, c.sort_name, cm.id, cmt.name, cc.total_amount, cm.start_date, cm.end_date, cm.source, cms.name 
     FROM  {$this->_entityIDTableName} ct
INNER JOIN civicrm_membership cm ON cm.id = ct.entity_id
LEFT JOIN  civicrm_contact c ON cm.contact_id = c.id
LEFT JOIN  civicrm_membership_type cmt ON cmt.id = cm.membership_type_id
LEFT JOIN  civicrm_membership_payment cmp ON cmp.membership_id = cm.id
LEFT JOIN  civicrm_contribution cc ON cc.id = cmp.contribution_id
LEFT JOIN  civicrm_membership_status cms ON cms.id = cm.status_id
{$this->_limitRowClause}
";
        break;
    }

    if ($sql) {
      CRM_Core_DAO::executeQuery($sql);
    }
  }
}

