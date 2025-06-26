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
class CRM_Dedupe_Merger {
  // FIXME: this should be auto-generated from the schema
  static $validFields = [
    'addressee', 'addressee_custom', 'birth_date', 'contact_source', 'contact_type',
    'deceased_date', 'do_not_email', 'do_not_mail', 'do_not_sms', 'do_not_phone',
    'do_not_trade', 'external_identifier', 'email_greeting', 'email_greeting_custom', 'first_name', 'gender',
    'home_URL', 'household_name', 'image_URL',
    'individual_prefix', 'individual_suffix', 'is_deceased', 'is_opt_out',
    'job_title', 'last_name', 'legal_identifier', 'legal_name',
    'middle_name', 'nick_name', 'organization_name', 'postal_greeting', 'postal_greeting_custom',
    'preferred_communication_method', 'preferred_mail_format', 'sic_code',
    'current_employer_id',
  ];

  static $locationBlocks = [
    'email' => 'Email',
    'phone' => 'Phone',
    'im' => 'IM',
    'open_id' => 'OpenID',
    'address' => 'Address',
    'website' => 'Website',
  ];

  static $locationValueField = [
    'email' => 'email',
    'phone' => 'phone',
    'im' => 'name',
    'open_id' => 'openid',
    'address' => 'display',
    'website' => 'url',
  ];

  static $dupePairsSorted = [];

  // FIXME: consider creating a common structure with cidRefs() and eidRefs()
  // FIXME: the sub-pages references by the URLs should
  // be loaded dynamically on the merge form instead
  static function relTables() {
    static $relTables;

    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Drupal') {
      $userRecordUrl = CRM_Utils_System::url('user/$ufid');
      $title = ts('%1 User: %2; user id: %3', [1 => $config->userFramework, 2 => '$ufname', 3 => '$ufid']);
    }
    elseif ($config->userFramework == 'Joomla') {
      $userRecordUrl = $config->userFrameworkBaseURL . 'index2.php?option=com_users&view=user&task=edit&cid[]=$ufid';
      $title = ts('%1 User: %2; user id: %3', [1 => $config->userFramework, 2 => '$ufname', 3 => '$ufid']);
    }

    if (!$relTables) {
      $relTables = [
        'rel_table_contributions' => [
          'title' => ts('Contributions'),
          'tables' => ['civicrm_contribution', 'civicrm_contribution_recur', 'civicrm_contribution_soft'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=contribute'),
        ],
        'rel_table_contribution_page' => [
          'title' => ts('Contribution Pages'),
          'tables' => ['civicrm_contribution_page'],
          'url' => CRM_Utils_System::url('civicrm/admin/contribute', 'reset=1&cid=$cid'),
        ],
        'rel_table_memberships' => [
          'title' => ts('Memberships'),
          'tables' => ['civicrm_membership', 'civicrm_membership_log', 'civicrm_membership_type'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=member'),
        ],
        'rel_table_participants' => [
          'title' => ts('Participants'),
          'tables' => ['civicrm_participant'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=participant'),
        ],
        'rel_table_events' => [
          'title' => ts('Events'),
          'tables' => ['civicrm_event'],
          'url' => CRM_Utils_System::url('civicrm/event/manage', 'reset=1&cid=$cid'),
        ],
        'rel_table_activities' => [
          'title' => ts('Activities'),
          'tables' => ['civicrm_activity', 'civicrm_activity_target', 'civicrm_activity_assignment'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=activity'),
        ],
        'rel_table_relationships' => [
          'title' => ts('Relationships'),
          'tables' => ['civicrm_relationship'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=rel'),
        ],
        'rel_table_custom_groups' => [
          'title' => ts('Custom Groups'),
          'tables' => ['civicrm_custom_group'],
          'url' => CRM_Utils_System::url('civicrm/admin/custom/group', 'reset=1'),
        ],
        'rel_table_uf_groups' => [
          'title' => ts('Profiles'),
          'tables' => ['civicrm_uf_group'],
          'url' => CRM_Utils_System::url('civicrm/admin/uf/group', 'reset=1'),
        ],
        'rel_table_groups' => [
          'title' => ts('Groups'),
          'tables' => ['civicrm_group_contact'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=group'),
        ],
        'rel_table_notes' => [
          'title' => ts('Notes'),
          'tables' => ['civicrm_note'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=note'),
        ],
        'rel_table_tags' => [
          'title' => ts('Tags'),
          'tables' => ['civicrm_entity_tag'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=tag'),
        ],
        'rel_table_mailings' => [
          'title' => ts('Mailings'),
          'tables' => ['civicrm_mailing', 'civicrm_mailing_event_queue', 'civicrm_mailing_event_subscribe'],
          'url' => CRM_Utils_System::url('civicrm/mailing', 'reset=1&force=1&cid=$cid'),
        ],
        'rel_table_cases' => [
          'title' => ts('Cases'),
          'tables' => ['civicrm_case_contact'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=case'),
        ],
        'rel_table_grants' => [
          'title' => ts('Grants'),
          'tables' => ['civicrm_grant'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=grant'),
        ],
        'rel_table_pcp' => [
          'title' => ts('PCPs'),
          'tables' => ['civicrm_pcp'],
          'url' => CRM_Utils_System::url('civicrm/contribute/pcp/manage', 'reset=1'),
        ],
        'rel_table_pledges' => [
          'title' => ts('Pledges'),
          'tables' => ['civicrm_pledge', 'civicrm_pledge_payment'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=pledge'),
        ],
        'rel_table_users' => [
          'title' => $title,
          'tables' => ['civicrm_uf_match'],
          'url' => $userRecordUrl,
        ],
        'rel_table_coupons' => [
          'title' => ts('Coupon'),
          'tables' => ['civicrm_coupon_track'],
          'url' => CRM_Utils_System::url('civicrm/contact/view', 'reset=1&force=1&cid=$cid&selectedChild=coupon'),
        ],
      ];

      // Allow hook_civicrm_merge() to adjust $relTables
      CRM_Utils_Hook::merge('relTables', $relTables);
    }
    return $relTables;
  }

  /**
   * Returns the related tables groups for which a contact has any info entered
   */
  static function getActiveRelTables($cid) {
    $cid = (int) $cid;
    $groups = [];

    $relTables = self::relTables();
    $cidRefs = self::cidRefs();
    $eidRefs = self::eidRefs();
    foreach ($relTables as $group => $params) {
      $sqls = [];
      foreach ($params['tables'] as $table) {
        if (isset($cidRefs[$table])) {
          foreach ($cidRefs[$table] as $field) {
            $sqls[] = "SELECT COUNT(*) AS count FROM $table WHERE $field = $cid";
          }
        }
        if (isset($eidRefs[$table])) {
          foreach ($eidRefs[$table] as $entityTable => $entityId) {
            $sqls[] = "SELECT COUNT(*) AS count FROM $table WHERE $entityId = $cid AND $entityTable = 'civicrm_contact'";
          }
        }
        foreach ($sqls as $sql) {
          if (CRM_Core_DAO::singleValueQuery($sql,
              CRM_Core_DAO::$_nullArray
            ) > 0) {
            $groups[] = $group;
          }
        }
      }
    }
    return array_unique($groups);
  }

  /**
   * Return tables and their fields referencing civicrm_contact.contact_id explicitely
   */
  public static function cidRefs() {
    static $cidRefs;
    if (!$cidRefs) {
      $cidRefs = CRM_Core_DAO::getReferencesToContactTable();
    }
    CRM_Utils_Hook::merge('cidRefs', $contactReferences);
    return $cidRefs;
  }

  /**
   * Return tables and their fields referencing civicrm_contact.contact_id with entity_id
   */
  public static function eidRefs() {
    static $eidRefs;
    if (!$eidRefs) {
      // FIXME: this should be generated dynamically from the schema
      // tables that reference contacts with entity_{id,table}
      $eidRefs = [
        'civicrm_acl' => ['entity_table' => 'entity_id'],
        'civicrm_acl_entity_role' => ['entity_table' => 'entity_id'],
        'civicrm_entity_file' => ['entity_table' => 'entity_id'],
        'civicrm_log' => ['entity_table' => 'entity_id'],
        'civicrm_mailing_group' => ['entity_table' => 'entity_id'],
        'civicrm_note' => ['entity_table' => 'entity_id'],
        'civicrm_project' => ['owner_entity_table' => 'owner_entity_id'],
        'civicrm_task' => ['owner_entity_table' => 'owner_entity_id'],
        'civicrm_task_status' => ['responsible_entity_table' => 'responsible_entity_id', 'target_entity_table' => 'target_entity_id'],
        'civicrm_entity_tag' => ['entity_table' => 'entity_id'],
      ];

      // Allow hook_civicrm_merge() to adjust $eidRefs
      CRM_Utils_Hook::merge('eidRefs', $eidRefs);
    }
    return $eidRefs;
  }

  /**
   * return custom processing tables.
   */
  static function cpTables() {
    static $tables;
    if (!$tables) {
      $tables = [
        'civicrm_case_contact' => ['CRM_Case_BAO_Case' => 'mergeContacts'],
        'civicrm_group_contact' => ['CRM_Contact_BAO_GroupContact' => 'mergeGroupContact'],
        'civicrm_relationship' => ['CRM_Contact_BAO_Relationship' => 'mergeRelationships'],
        // Empty array == do nothing - this table is handled by mergeGroupContact
        'civicrm_subscription_history' => [],
      ];
    }

    return $tables;
  }

  /**
   * return payment related table.
   */
  static function paymentTables() {
    static $tables;
    if (!$tables) {
      $tables = ['civicrm_pledge', 'civicrm_membership', 'civicrm_participant'];
    }

    return $tables;
  }

  /**
   * return payment update Query.
   */
  static function paymentSql($tableName, $mainContactId, $otherContactId) {
    $sqls = [];
    if (!$tableName || !$mainContactId || !$otherContactId) {
      return $sqls;
    }

    $paymentTables = self::paymentTables();
    if (!in_array($tableName, $paymentTables)) {
      return $sqls;
    }

    switch ($tableName) {
      case 'civicrm_pledge':
        $sqls[] = "
    UPDATE  IGNORE  civicrm_contribution contribution
INNER JOIN  civicrm_pledge_payment payment ON ( payment.contribution_id = contribution.id )
INNER JOIN  civicrm_pledge pledge ON ( pledge.id = payment.pledge_id )                                               
       SET  contribution.contact_id = $mainContactId
     WHERE  pledge.contact_id = $otherContactId";
        break;

      case 'civicrm_membership':
        $sqls[] = "
    UPDATE  IGNORE  civicrm_contribution contribution
INNER JOIN  civicrm_membership_payment payment ON ( payment.contribution_id = contribution.id )
INNER JOIN  civicrm_membership membership ON ( membership.id = payment.membership_id )      
       SET  contribution.contact_id = $mainContactId
     WHERE  membership.contact_id = $otherContactId";
        break;

      case 'civicrm_participant':
        $sqls[] = "
    UPDATE  IGNORE  civicrm_contribution contribution
INNER JOIN  civicrm_participant_payment payment ON ( payment.contribution_id = contribution.id )
INNER JOIN  civicrm_participant participant ON ( participant.id = payment.participant_id )      
       SET  contribution.contact_id = $mainContactId
     WHERE  participant.contact_id = $otherContactId";
        break;
    }

    return $sqls;
  }

  static function operationSql($mainId, $otherId, $tableName, $tableOperations = [], $mode = 'add') {
    $sqls = [];
    if (!$tableName || !$mainId || !$otherId) {
      return $sqls;
    }

    switch ($tableName) {
      case 'civicrm_uf_match':
        $sqls[] = "DELETE FROM civicrm_uf_match WHERE contact_id = {$mainId}";
        break;
    }

    return $sqls;
  }

  /**
   * Based on the provided two contact_ids and a set of tables, move the
   * belongings of the other contact to the main one.
   */
  static function moveContactBelongings($mainId, $otherId, $tables = FALSE, $tableOperations = []) {
    $cidRefs = self::cidRefs();
    $eidRefs = self::eidRefs();
    $cpTables = self::cpTables();
    $paymentTables = self::paymentTables();

    $affected = array_merge(array_keys($cidRefs), array_keys($eidRefs));
    if ($tables !== FALSE) {
      // if there are specific tables, sanitize the list
      $affected = array_unique(array_intersect($affected, $tables));
    }
    else {
      // if there aren't any specific tables, don't affect the ones handled by relTables()
      $relTables = self::relTables();
      $handled = [];
      foreach ($relTables as $params) {
        $handled = array_merge($handled, $params['tables']);
      }
      $affected = array_diff($affected, $handled);
    }

    $mainId = (int) $mainId;
    $otherId = (int) $otherId;

    // use UPDATE IGNORE + DELETE query pair to skip on situations when
    // there's a UNIQUE restriction on ($field, some_other_field) pair
    $sqls = [];
    foreach ($affected as $table) {
      // here we require custom processing.
      if (isset($cpTables[$table])) {
        foreach ($cpTables[$table] as $className => $fnName) {
          $className::$fnName($mainId, $otherId, $sqls);
        }
        // Skip normal processing
        continue;
      }

      if (isset($cidRefs[$table])) {
        foreach ($cidRefs[$table] as $field) {
          // carry related contributions CRM-5359
          if (in_array($table, $paymentTables)) {
            $paymentSqls = self::paymentSql($table, $mainId, $otherId);
            $sqls = array_merge($sqls, $paymentSqls);
          }

          $preOperationSqls = self::operationSql($mainId, $otherId, $table, $tableOperations);
          $sqls = array_merge($sqls, $preOperationSqls);

          // skip location related table, because move All Belongings has done this
          $shortName = str_replace('civicrm_', '', $table);
          if (!isset(self::$locationBlocks[$shortName])) {
            $sqls[] = "UPDATE IGNORE $table SET $field = $mainId WHERE $field = $otherId";
            $sqls[] = "DELETE FROM $table WHERE $field = $otherId";
          }
        }
      }
      if (isset($eidRefs[$table])) {
        foreach ($eidRefs[$table] as $entityTable => $entityId) {
          $sqls[] = "UPDATE IGNORE $table SET $entityId = $mainId WHERE $entityId = $otherId AND $entityTable = 'civicrm_contact'";
          $sqls[] = "DELETE FROM $table WHERE $entityId = $otherId AND $entityTable = 'civicrm_contact'";
        }
      }
    }

    // Allow hook_civicrm_merge() to add SQL statements for the merge operation.
    CRM_Utils_Hook::merge('sqls', $sqls, $mainId, $otherId, $tables);

    // call the SQL queries in one transaction

    $transaction = new CRM_Core_Transaction();
    foreach ($sqls as $sql) {
      CRM_Core_DAO::executeQuery($sql,
        CRM_Core_DAO::$_nullArray,
        TRUE, NULL, TRUE
      );
    }
    $transaction->commit();
  }

  /**
   * Find differences between contacts.
   */
  static function findDifferences($main, $other) {
    $result = [
      'contact' => [],
      'custom' => [],
    ];
    foreach (self::$validFields as $validField) {
      if (CRM_Utils_Array::value($validField, $main) != CRM_Utils_Array::value($validField, $other)) {
        $result['contact'][] = $validField;
      }
    }

    $mainEvs = CRM_Core_BAO_CustomValueTable::getEntityValues($main['id']);
    $otherEvs = CRM_Core_BAO_CustomValueTable::getEntityValues($other['id']);
    $keys = array_unique(array_merge(array_keys($mainEvs), array_keys($otherEvs)));
    foreach ($keys as $key) {
      $key1 = CRM_Utils_Array::value($key, $mainEvs);
      $key2 = CRM_Utils_Array::value($key, $otherEvs);
      if ($key1 != $key2) {
        $result['custom'][] = $key;
      }
    }
    return $result;
  }

  /**
   * Function to merge given set of contacts. Performs core operation.
   *
   * @param  array   $dupePairs   set of pair of contacts for whom merge is to be done.
   * @param  array   $cacheParams prev-next-cache params based on which next pair of contacts are computed.
   *                              Generally used with batch-merge.
   * @param  string  $mode       helps decide how to behave when there are conflicts.
   *                             A 'safe' value skips the merge if there are any un-resolved conflicts.
   *                             Does a force merge otherwise (aggressive mode).
   * @param  boolean $autoFlip   wether to let api decide which contact to retain and which to delete.
   *
   *
   * @static
   * @access public
   */
  static function merge($dupePairs = [], $cacheParams = [], $mode = 'safe', $autoFlip = TRUE, $redirectForPerformance = FALSE, $action = CRM_Core_Action::PREVIEW) {
    $cacheKeyString = CRM_Utils_Array::value('cache_key_string', $cacheParams);
    $resultStats = ['merged' => [], 'skipped' => []];

    // we don't want dupe caching to get reset after every-merge, and therefore set the
    // doNotResetCache flag
    $config = CRM_Core_Config::singleton();
    $config->doNotResetCache = 1;

    while (!empty($dupePairs)) {
      foreach ($dupePairs as $dupes) {
        $mainId = $dupes['dstID'];
        $otherId = $dupes['srcID'];
        // make sure that $mainId is the one with lower id number
        if ($autoFlip && ($mainId > $otherId)) {
          $mainId = $dupes['srcID'];
          $otherId = $dupes['dstID'];
        }
        if (!$mainId || !$otherId || !is_numeric($mainId) || !is_numeric($otherId)) {
          // return error
          return FALSE;
        }

        // check both contacts are not deleted
        $dao = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_contact WHERE is_deleted = 1 AND id IN ($mainId, $otherId)");
        if ($dao->N) {
          while($dao->fetch()) {
            $deleted[] = $dao->id;
          }
          $resultStats['skipped'][] = [
            'main_id' => $mainId,
            'other_id' => $otherId,
            'reason' => ["Contact id ".CRM_Utils_Array::implode(",", $deleted).' is/were deleted.'],
          ];
          continue;
        }

        // Generate var $migrationInfo. The variable structure is exactly same as
        // $formValues submitted during a UI merge for a pair of contacts.
        $rowsElementsAndInfo = &CRM_Dedupe_Merger::getRowsElementsAndInfo($mainId, $otherId);

        $migrationInfo = &$rowsElementsAndInfo['migration_info'];

        // add additional details that we might need to resolve conflicts
        $migrationInfo['main_details'] = &$rowsElementsAndInfo['main_details'];
        $migrationInfo['other_details'] = &$rowsElementsAndInfo['other_details'];
        $migrationInfo['main_loc_block'] = &$rowsElementsAndInfo['main_loc_block'];
        $migrationInfo['rows'] = &$rowsElementsAndInfo['rows'];

        // go ahead with merge if there is no conflict
        $reason = [];
        if (!CRM_Dedupe_Merger::skipMerge($mainId, $otherId, $migrationInfo, $mode, $reason)) {
          if ($action != CRM_Core_Action::PREVIEW) {
            CRM_Dedupe_Merger::moveAllBelongings($mainId, $otherId, $migrationInfo);
            $stat = 'merged';
          }
          else {
            $stat = 'merge_preview';
          }
          $resultStats[$stat][] = [
            'main_id' => $mainId,
            'other_id' => $otherId,
          ];
        }
        else {
          $resultStats['skipped'][] = [
            'main_id' => $mainId,
            'other_id' => $otherId,
            'reason' => $reason,
          ];
        }

        // delete entry from PrevNextCache table so we don't consider the pair next time
        // pair may have been flipped, so make sure we delete using both orders
        #CRM_Core_BAO_PrevNextCache::deletePair($mainId, $otherId, $cacheKeyString);
        #CRM_Core_BAO_PrevNextCache::deletePair($otherId, $mainId, $cacheKeyString);

        CRM_Core_DAO::freeResult();
        unset($rowsElementsAndInfo, $migrationInfo);
      }

      /*
      if ($cacheKeyString && !$redirectForPerformance) {
        // retrieve next pair of dupes
        $dupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString,
          $cacheParams['join'],
          $cacheParams['where']
        );
      }
      else {
        // do not proceed. Terminate the loop
      }
      */
      unset($dupePairs);
    }
    return $resultStats;
  }

  /**
   * A function which uses various rules / algorithms for choosing which contact to bias to
   * when there's a conflict (to handle "gotchas"). Plus the safest route to merge.
   *
   * @param  int     $mainId         main contact with whom merge has to happen
   * @param  int     $otherId        duplicate contact which would be deleted after merge operation
   * @param  array   $migrationInfo  array of information about which elements to merge.
   * @param  string  $mode           helps decide how to behave when there are conflicts.
   *                                 A 'safe' value skips the merge if there are any un-resolved conflicts.
   *                                 Does a force merge otherwise (aggressive mode).
   *
   * @static
   * @access public
   */
  static function skipMerge($mainId, $otherId, &$migrationInfo, $mode, &$reason) {
    $conflicts = [];
    $migrationData = [
      'old_migration_info' => $migrationInfo,
      'mode' => $mode ? $mode : 'safe',
    ];
    $allLocationTypes = CRM_Core_PseudoConstant::locationType(TRUE, 'name');
    $otherLocationTypeId = array_search('Other', $allLocationTypes);

    // skip these field for conflict detection
    $validFields = CRM_Dedupe_Merger::$validFields;
    foreach(['do_not_email', 'do_not_mail', 'do_not_sms', 'do_not_phone', 'do_not_trade', 'is_opt_out', 'preferred_communication_method']  as $fld) {
      $exists = array_search($fld, $validFields);
      if ($exists !== FALSE) {
        unset($validFields[$exists]);
      }
    }

    foreach ($migrationInfo as $key => $val) {
      if ($val === "null") {
        // Rule: no overwriting with empty values in any mode
        unset($migrationInfo[$key]);
        continue;
      }
      elseif ((in_array(substr($key, 5), $validFields) || substr($key, 0, 12) == 'move_custom_') && $val != NULL) {
        // Rule: if both main-contact has other-contact, let $mode decide if to merge a
        // particular field or not
        if (!empty($migrationInfo['rows'][$key]['main'])) {
          // if main also has a value its a conflict
          if ($mode == 'safe') {
            // note it down & lets wait for response from the hook.
            // For no response skip this merge
            $conflicts[$key] = NULL;
          }
          elseif ($mode == 'aggressive') {
            // let the main-field be overwritten
            continue;
          }
        }
      }
      elseif (substr($key, 0, 14) == 'move_location_' and $val != NULL) {
        $locField = explode('_', $key);
        $fieldName = $locField[2];
        $fieldCount = $locField[3];

        // Rule: resolve address conflict if any -
        if ($fieldName == 'address') {
          $mainNewLocTypeId = $migrationInfo['location'][$fieldName][$fieldCount]['locTypeId'];
          if (!empty($migrationInfo['main_loc_block']) &&
              CRM_Utils_Array::arrayKeyExists("main_{$fieldName}{$mainNewLocTypeId}", $migrationInfo['main_loc_block'])) {
            // main loc already has some address for the loc-type. Its a overwrite situation.

            // look for next available loc-type
            $newTypeId = $otherLocationTypeId;
            if (!$newTypeId) {
              foreach ($allLocationTypes as $typeId => $typeLabel) {
                if (!CRM_Utils_Array::arrayKeyExists("main_{$fieldName}{$typeId}", $migrationInfo['main_loc_block'])) {
                  $newTypeId = $typeId;
                }
              }
            }

            if ($newTypeId) {
              // try insert address at new available loc-type
              $migrationInfo['location'][$fieldName][$fieldCount]['locTypeId'] = $newTypeId;
            }
            elseif ($mode == 'safe') {
              // note it down & lets wait for response from the hook.
              // For no response skip this merge
              $conflicts[$key] = NULL;
            }
            elseif ($mode == 'aggressive') {
              // let the loc-type-id be same as that of other-contact & go ahead
              // with merge assuming aggressive mode
              continue;
            }
          }
        }
        elseif ($migrationInfo['rows'][$key]['main'] == $migrationInfo['rows'][$key]['other']) {
          // for loc blocks other than address like email, phone .. if values are same no point in merging
          // and adding redundant value
          unset($migrationInfo[$key]);
        }
      }
    }

    // A hook to implement other algorithms for choosing which contact to bias to when
    // there's a conflict (to handle "gotchas"). fields_in_conflict could be modified here
    // merge happens with new values filled in here. For a particular field / row not to be merged
    // field should be unset from fields_in_conflict.
    $migrationData['fields_in_conflict'] = $conflicts;
    CRM_Utils_Hook::merge('conflict', $migrationData, $mainId, $otherId);
    $conflicts = $migrationData['fields_in_conflict'];

    if (!empty($conflicts)) {
      $reason = $conflicts;
      foreach ($conflicts as $key => $val) {
        if ($val === NULL and $mode == 'safe') {
          // un-resolved conflicts still present. Lets skip this merge.
          return TRUE;
        }
        else {
          // copy over the resolved values
          $migrationInfo[$key] = $val;
        }
      }
    }
    return FALSE;
  }

  /**
   * A function to build an array of information required by merge function and the merge UI.
   *
   * @param  int     $mainId         main contact with whom merge has to happen
   * @param  int     $otherId        duplicate contact which would be deleted after merge operation
   *
   * @static
   * @access public
   */
  static function getRowsElementsAndInfo($mainId, $otherId) {
    $qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';

    // Fetch contacts
    foreach (['main' => $mainId, 'other' => $otherId] as $moniker => $cid) {
      $params = ['contact_id' => $cid, 'version' => 3, 'return' => array_merge(['display_name'], self::$validFields)];
      $result = civicrm_api('contact', 'get', $params);

      if (empty($result['values'][$cid]['contact_type'])) {
        return FALSE;
      }
      $$moniker = $result['values'][$cid];
    }

    static $fields = [];
    if (empty($fields)) {
      $fields = CRM_Contact_DAO_Contact::fields();
      CRM_Core_DAO::freeResult();
    }

    // FIXME: there must be a better way
    foreach (['main', 'other'] as $moniker) {
      $contact = &$$moniker;
      $preferred_communication_method = CRM_Utils_Array::value('preferred_communication_method', $contact);
      $value = empty($preferred_communication_method) ? [] : $preferred_communication_method;
      $specialValues[$moniker] = [
        'preferred_communication_method' => $value,
      ];

      if (CRM_Utils_array::value('preferred_communication_method', $contact)){
      // api 3 returns pref_comm_method as an array, which breaks the lookup; so we reconstruct
      $prefCommList = is_array($specialValues[$moniker]['preferred_communication_method']) ?
        CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR, $specialValues[$moniker]['preferred_communication_method']) :
        $specialValues[$moniker]['preferred_communication_method'];
        $specialValues[$moniker]['preferred_communication_method'] = CRM_Core_DAO::VALUE_SEPARATOR . $prefCommList . CRM_Core_DAO::VALUE_SEPARATOR;
      }
      $names = [
        'preferred_communication_method' =>
        [
          'newName' => 'preferred_communication_method_display',
          'groupName' => 'preferred_communication_method',
        ],
      ];
      CRM_Core_OptionGroup::lookupValues($specialValues[$moniker], $names);
    }

    static $optionValueFields = [];
    if (empty($optionValueFields)) {
      $optionValueFields = CRM_Core_OptionValue::getFields();
    }
    foreach ($optionValueFields as $field => $params) {
      $fields[$field]['title'] = $params['title'];
    }

    $diffs = self::findDifferences($main, $other);

    $rows = $elements = $relTableElements = $migrationInfo = [];

    foreach ($diffs['contact'] as $field) {
      foreach (['main', 'other'] as $moniker) {
        $contact = &$$moniker;
        $value = CRM_Utils_Array::value($field, $contact);
        if (isset($specialValues[$moniker][$field]) && is_string($specialValues[$moniker][$field])) {
          $value = CRM_Core_DAO::VALUE_SEPARATOR . trim($specialValues[$moniker][$field], CRM_Core_DAO::VALUE_SEPARATOR) . CRM_Core_DAO::VALUE_SEPARATOR;
        }
        $label = $specialValues[$moniker]["{$field}_display"] ?? $value;
        if (CRM_Utils_Array::value('type', $fields[$field]) && $fields[$field]['type'] == CRM_Utils_Type::T_DATE) {
          if ($value) {
            $value = str_replace('-', '', $value);
            $label = CRM_Utils_Date::customFormat($label);
          }
          else {
            $value = "null";
          }
        }
        elseif (CRM_Utils_Array::value('type', $fields[$field]) && $fields[$field]['type'] == CRM_Utils_Type::T_BOOLEAN) {
          if ($label === '0') {
            $label = '<i class="zmdi zmdi-square-o"></i>';
          }
          if ($label === '1') {
            $label = '<i class="zmdi zmdi-check-square"></i>'.ts('Yes').'';
          }
        } elseif ($field == 'individual_prefix' || $field == 'prefix_id') {
          $label = CRM_Utils_Array::value('prefix', $contact);
          $value = CRM_Utils_Array::value('prefix_id', $contact);
          $field = 'prefix_id';
        } elseif ($field == 'individual_suffix' || $field == 'suffix_id') {
          $label = CRM_Utils_Array::value('suffix', $contact);
          $value = CRM_Utils_Array::value('suffix_id', $contact);
          $field = 'suffix_id';
        }
        $rows["move_$field"][$moniker] = $label;
        if ($moniker == 'other') {
          if ($value === NULL) {
            $value = 'null';
          }
          if ($value === 0 or $value === '0') {
            $value = $qfZeroBug;
          }
          if (is_array($value) &&
              !CRM_Utils_Array::value(1, $value)) {
            $value[1] = NULL;
          }

          // preferred communication should merge instead replace
          if ($field == 'preferred_communication_method' && !empty($value)) {
            if (empty($main[$field])) {
              $commuArray = $other[$field];
            }
            else {
              $commuArray = array_merge($main[$field], array_diff($other[$field], $main[$field]));
            }
            $value = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $commuArray) . CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
          }
          $elements[] = ['advcheckbox', "move_$field", NULL, NULL, NULL, $value];
          $migrationInfo["move_$field"] = $value;
        }
      }
      $rows["move_$field"]['title'] = $fields[$field]['title'];
    }

    // handle location blocks.
    $locations = $locationsExists = [];

    foreach (array_keys(self::$locationBlocks) as $block) {
      foreach (['main' => $mainId, 'other' => $otherId] as $moniker => $cid) {
        $cnt = 1;
        $values = civicrm_api($block, 'get', ['contact_id' => $cid, 'version' => 3]);
        $count = $values['count'];
        $valueField = self::$locationValueField[$block];
        if ($count) {
          $value = [];
          if ($count > $cnt) {
            foreach ($values['values'] as $value) {
              // check if value exists in main, if exists, skipped
              if ($moniker == 'other' && !empty($value[$valueField]) && $locationsExists['main'][$block][$value[$valueField]]) {
                continue;
              }
              if (empty($value[$valueField])) {
                continue;
              }
              $locations[$moniker][$block][$cnt] = $value;
              if (!empty($value[$valueField])) {
                $locationsExists[$moniker][$block][$value[$valueField]] = 1;
              }
              $cnt++;
            }
          }
          else {
            $id = $values['id'];
            $value = $values['values'][$id];
            // check if value exists in main, if exists, skipped
            if ($moniker == 'other' && !empty($value[$valueField]) && $locationsExists['main'][$block][$value[$valueField]]) {
              continue;
            }
            if (empty($value[$valueField])) {
              continue;
            }
            $locations[$moniker][$block][$cnt] = $value;
            if (!empty($value[$valueField])) {
              $locationsExists[$moniker][$block][$value[$valueField]] = 1;
            }
          }
        }
      }
    }

    $allLocationTypes = CRM_Core_PseudoConstant::locationType();

    $mainLocBlock = $locBlockIds = [];
    $locBlockIds['main'] = $locBlockIds['other'] = [];
    foreach (self::$locationBlocks as $block) {
      $name = strtolower($block);
      foreach (['main', 'other'] as $moniker) {
        $locIndex = CRM_Utils_Array::value($moniker, $locations);
        $blockValue = CRM_Utils_Array::value($name, $locIndex, []);
        if (empty($blockValue)) {
          $locValue[$moniker][$name] = 0;
          $locLabel[$moniker][$name] = $locTypes[$moniker][$name] = [];
        }
        else {
          $locValue[$moniker][$name] = TRUE;
          foreach ($blockValue as $count => $blkValues) {
            $fldName = $name;
            $locTypeId = $blkValues['location_type_id'];
            if ($name == 'im') {
              $fldName = 'name';
            }
            if ($name == 'address') {
              $fldName = 'display';
            }
            $locLabel[$moniker][$name][$count] = CRM_Utils_Array::value($fldName,
              $blkValues
            );
            $locTypes[$moniker][$name][$count] = $locTypeId;
            if ($moniker == 'main' && CRM_Utils_Array::arrayKeyExists($name, self::$locationBlocks)) {
              $mainLocBlock["main_$name$locTypeId"] = CRM_Utils_Array::value($fldName,
                $blkValues
              );
              $locBlockIds['main'][$name][$locTypeId] = $blkValues['id'];
            }
            else {
              $locBlockIds[$moniker][$name][$count] = $blkValues['id'];
            }
          }
        }
      }

      if ($locValue['other'][$name] != 0) {
        foreach ($locLabel['other'][$name] as $count => $value) {
          $locTypeId = $locTypes['other'][$name][$count];
          $rows["move_location_{$name}_$count"]['other'] = $value;
          $rows["move_location_{$name}_$count"]['main'] = CRM_Utils_Array::value($count,
            $locLabel['main'][$name]
          );
          $rows["move_location_{$name}_$count"]['title'] = ts('%1:%2:%3',
            [
              1 => $block,
              2 => $count,
              3 => $allLocationTypes[$locTypeId]
            ]
          );

          $elements[] = ['advcheckbox', "move_location_{$name}_{$count}"];
          $migrationInfo["move_location_{$name}_{$count}"] = 1;

          // make sure default location type is always on top
          $mainLocTypeId = CRM_Utils_Array::value($count, $locTypes['main'][$name], $locTypeId);
          $locTypeValues = $allLocationTypes;
          $defaultLocType = [$mainLocTypeId => $locTypeValues[$mainLocTypeId]];
          unset($locTypeValues[$mainLocTypeId]);

          // keep 1-1 mapping for address - location type.
          $attr = NULL;
          if (CRM_Utils_Array::arrayKeyExists($name, self::$locationBlocks) && !empty($mainLocBlock)) {
            $attr = [
              'data-location-name' => $name,
              'data-location-id' => $count,
            ];
          }
          $elements[] = [
            'select', "location[{$name}][$count][locTypeId]", NULL,
            $defaultLocType + $locTypeValues, $attr,
          ];
          // keep location-type-id same as that of other-contact
          $migrationInfo['location'][$name][$count]['locTypeId'] = $locTypeId;

          if ($name != 'address') {
            $elements[] = ['advcheckbox', "location[{$name}][$count][operation]", NULL, ts('add new')];
            // always use add operation
            $migrationInfo['location'][$name][$count]['operation'] = 1;
          }
        }
      }
    }

    // add the related tables and unset the ones that don't sport any of the duplicate contact's info
    $config = CRM_Core_Config::singleton();
    $mainUfId = CRM_Core_BAO_UFMatch::getUFId($mainId);
    $mainUser = NULL;
    if ($mainUfId) {
      // d6 compatible
      if ($config->userSystem->is_drupal == '1' && function_exists($mainUser)) {
        $mainUser = user_load($mainUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $mainUser = JFactory::getUser($mainUfId);
      }
    }
    $otherUfId = CRM_Core_BAO_UFMatch::getUFId($otherId);
    $otherUser = NULL;
    if ($otherUfId) {
      // d6 compatible
      if ($config->userSystem->is_drupal == '1' && function_exists($mainUser)) {
        $otherUser = user_load($otherUfId);
      }
      elseif ($config->userFramework == 'Joomla') {
        $otherUser = JFactory::getUser($otherUfId);
      }
    }

    $relTables = CRM_Dedupe_Merger::relTables();
    $activeRelTables = CRM_Dedupe_Merger::getActiveRelTables($otherId);
    $activeMainRelTables = CRM_Dedupe_Merger::getActiveRelTables($mainId);
    foreach ($relTables as $name => $null) {
      if (!in_array($name, $activeRelTables) && !(($name == 'rel_table_users') && in_array($name, $activeMainRelTables))) {
        unset($relTables[$name]);
        continue;
      }

      $relTableElements[] = ['checkbox', "move_$name"];
      $migrationInfo["move_$name"] = 1;

      $relTables[$name]['main_url'] = str_replace('$cid', $mainId, $relTables[$name]['url']);
      $relTables[$name]['other_url'] = str_replace('$cid', $otherId, $relTables[$name]['url']);
      if ($name == 'rel_table_users') {
        $relTables[$name]['main_url'] = str_replace('%ufid', $mainUfId, $relTables[$name]['url']);
        $relTables[$name]['other_url'] = str_replace('%ufid', $otherUfId, $relTables[$name]['url']);
        $find = ['$ufid', '$ufname'];
        if ($mainUser) {
          $replace = [$mainUfId, $mainUser->name];
          $relTables[$name]['main_title'] = str_replace($find, $replace, $relTables[$name]['title']);
        }
        if ($otherUser) {
          $replace = [$otherUfId, $otherUser->name];
          $relTables[$name]['other_title'] = str_replace($find, $replace, $relTables[$name]['title']);
        }
      }
    }
    foreach ($relTables as $name => $null) {
      $relTables["move_$name"] = $relTables[$name];
      unset($relTables[$name]);
    }

    // handle custom fields
    $mainTree = CRM_Core_BAO_CustomGroup::getTree($main['contact_type'], CRM_Core_DAO::$_nullObject, $mainId, -1,
      CRM_Utils_Array::value('contact_sub_type', $main)
    );
    $otherTree = CRM_Core_BAO_CustomGroup::getTree($main['contact_type'], CRM_Core_DAO::$_nullObject, $otherId, -1,
      CRM_Utils_Array::value('contact_sub_type', $other)
    );
    CRM_Core_DAO::freeResult();

    foreach ($otherTree as $gid => $group) {
      $foundField = FALSE;
      if (!isset($group['fields'])) {
        continue;
      }

      foreach ($group['fields'] as $fid => $field) {
        if (in_array($fid, $diffs['custom'])) {
          if (!$foundField) {
            $rows["custom_group_$gid"]['title'] = $group['title'];
            $foundField = TRUE;
          }
          if (CRM_Utils_Array::value('customValue', $mainTree[$gid]['fields'][$fid])) {
            foreach ($mainTree[$gid]['fields'][$fid]['customValue'] as $valueId => $values) {
              $rows["move_custom_$fid"]['main'] = CRM_Core_BAO_CustomGroup::formatCustomValues($values,
                $field, TRUE
              );
            }
          }
          $value = NULL;
          if (CRM_Utils_Array::value('customValue', $otherTree[$gid]['fields'][$fid])) {
            foreach ($otherTree[$gid]['fields'][$fid]['customValue'] as $valueId => $values) {
              $rows["move_custom_$fid"]['other'] = CRM_Core_BAO_CustomGroup::formatCustomValues($values,
                $field, TRUE
              );
              if ($values['data'] === 0 || $values['data'] === '0') {
                $values['data'] = $qfZeroBug;
            }
              $value = ($values['data']) ? $values['data'] : $value;
          }
          }
          $rows["move_custom_$fid"]['title'] = $field['label'];

          $elements[] = ['advcheckbox', "move_custom_$fid", NULL, NULL, NULL, $value];
          $migrationInfo["move_custom_$fid"] = $value;
        }
      }
    }
    $result = [
      'rows' => $rows,
      'elements' => $elements,
      'rel_table_elements' => $relTableElements,
      'rel_tables' => $relTables,
      'main_loc_block' => $mainLocBlock,
      'main_details' => $main,
      'other_details' => $other,
      'migration_info' => $migrationInfo,
    ];

    $result['main_details']['loc_block_ids'] = $locBlockIds['main'];
    $result['other_details']['loc_block_ids'] = $locBlockIds['other'];

    return $result;
  }

  /**
   * Based on the provided two contact_ids and a set of tables, move the belongings of the
   * other contact to the main one - be it Location / CustomFields or Contact .. related info.
   * A superset of moveContactBelongings() function.
   *
   * @param  int     $mainId         main contact with whom merge has to happen
   * @param  int     $otherId        duplicate contact which would be deleted after merge operation
   *
   * @static
   * @access public
   */
  static function moveAllBelongings($mainId, $otherId, $migrationInfo) {
    if (empty($migrationInfo)) {
      return FALSE;
    }

    $allLocationTypes = CRM_Core_PseudoConstant::locationType(TRUE, 'name');
    $otherLocationTypeId = array_search('Other', $allLocationTypes);
    $qfZeroBug = 'e8cddb72-a257-11dc-b9cc-0016d3330ee9';
    $relTables = CRM_Dedupe_Merger::relTables();
    $moveTables = $locBlocks = $tableOperations = [];
    foreach ($migrationInfo as $key => $value) {
      if ($value == $qfZeroBug) {
        $value = '0';
      }
      if ((in_array(substr($key, 5), self::$validFields) || substr($key, 0, 12) == 'move_custom_') and $value != NULL) {
        // do not something should only add when value is 1
        if (substr($key, 5, 7) == 'do_not_' || substr($key, 5) == 'is_opt_out') {
          if ($value == '1') {
            $submitted[substr($key, 5)] = $value;
          }
          else {
            // respect human submit form
            if (!empty($migrationInfo['qfKey'])) {
              $submitted[substr($key, 5)] = $value;
            }
          }
        }
        else {
          $submitted[substr($key, 5)] = $value;
        }
      }
      elseif (substr($key, 0, 14) == 'move_location_' and $value != NULL) {
        $locField = explode('_', $key);
        $fieldName = $locField[2];
        $fieldCount = $locField[3];
        $operation = CRM_Utils_Array::value('operation', $migrationInfo['location'][$fieldName][$fieldCount]);
        // default operation is overwrite.
        if (!$operation) {
          $operation = 2;
          if ($fieldName == 'address' && $migrationInfo['location'][$fieldName][$fieldCount]['locTypeId'] == $otherLocationTypeId) {
            $operation = 1;
          }
        }

        $locBlocks[$fieldName][$fieldCount]['operation'] = $operation;
        $locBlocks[$fieldName][$fieldCount]['locTypeId'] = CRM_Utils_Array::value('locTypeId', $migrationInfo['location'][$fieldName][$fieldCount]);
      }
      elseif (substr($key, 0, 15) == 'move_rel_table_' and $value == '1') {
        $moveTables = array_merge($moveTables, $relTables[substr($key, 5)]['tables']);
        if (CRM_Utils_Array::arrayKeyExists('operation', $migrationInfo)) {
          foreach ($relTables[substr($key, 5)]['tables'] as $table) {
            if (CRM_Utils_Array::arrayKeyExists($key, $migrationInfo['operation'])) {
              $tableOperations[$table] = $migrationInfo['operation'][$key];
            }
          }
        }
      }
    }
    // indicate is human form submit that not select any table to move
    if (empty($moveTables) && !empty($migrationInfo['qfKey'])) {
      $moveTables = ['none'];
    }

    // **** Do location related migration:
    if (!empty($locBlocks)) {
      $locComponent = self::$locationBlocks;

      $primaryBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($mainId, ['is_primary' => 1]);
      $billingBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($mainId, ['is_billing' => 1]);

      foreach ($locBlocks as $name => $block) {
        if (!is_array($block) || CRM_Utils_System::isNull($block)) {
          continue;
        }
        $daoName = 'CRM_Core_DAO_' . $locComponent[$name];
        $primaryDAOId = (CRM_Utils_Array::arrayKeyExists($name, $primaryBlockIds)) ? array_pop($primaryBlockIds[$name]) : NULL;
        $billingDAOId = (CRM_Utils_Array::arrayKeyExists($name, $billingBlockIds)) ? array_pop($billingBlockIds[$name]) : NULL;

        foreach ($block as $blkCount => $values) {
          $locTypeId = CRM_Utils_Array::value('locTypeId', $values, 1);
          $operation = CRM_Utils_Array::value('operation', $values, 2);
          $otherBlockId = CRM_Utils_Array::value($blkCount,
            $migrationInfo['other_details']['loc_block_ids'][$name]
          );

          // keep 1-1 mapping for address - loc type.
          $idKey = $blkCount;
          if (CRM_Utils_Array::arrayKeyExists($name, $locComponent)) {
            $idKey = $locTypeId;
          }

          if (isset($migrationInfo['main_details']['loc_block_ids'][$name])) {
            $mainBlockId = CRM_Utils_Array::value($idKey, $migrationInfo['main_details']['loc_block_ids'][$name]);
          }

          if (!$otherBlockId) {
            continue;
          }

          // for the block which belongs to other-contact, link the contact to main-contact
          $otherBlockDAO = new $daoName();
          $otherBlockDAO->id = $otherBlockId;
          $otherBlockDAO->contact_id = $mainId;
          $otherBlockDAO->location_type_id = $locTypeId;

          // if main contact already has primary & billing, set the flags to 0.
          if ($primaryDAOId) {
            $otherBlockDAO->is_primary = 0;
          }
          if ($billingDAOId) {
            $otherBlockDAO->is_billing = 0;
          }

          // overwrite - need to delete block which belongs to main-contact.
          if ($mainBlockId && ($operation == 2)) {
            $deleteDAO = new $daoName();
            $deleteDAO->id = $mainBlockId;
            $deleteDAO->find(TRUE);

            // if we about to delete a primary / billing block, set the flags for new block
            // that we going to assign to main-contact
            if ($primaryDAOId && ($primaryDAOId == $deleteDAO->id)) {
              $otherBlockDAO->is_primary = 1;
            }
            if ($billingDAOId && ($billingDAOId == $deleteDAO->id)) {
              $otherBlockDAO->is_billing = 1;
            }

            $deleteDAO->delete();
            $deleteDAO->free();
          }

          $otherBlockDAO->update();
          $otherBlockDAO->free();
        }
      }
    }

    // **** Do tables related migrations
    if (!empty($moveTables)) {
      CRM_Dedupe_Merger::moveContactBelongings($mainId, $otherId, $moveTables, $tableOperations);
      unset($moveTables, $tableOperations);
    }

    // **** Do contact related migrations
    CRM_Dedupe_Merger::moveContactBelongings($mainId, $otherId);

    // FIXME: fix gender, prefix and postfix, so they're edible by createProfileContact()
    $names['gender'] = ['newName' => 'gender_id', 'groupName' => 'gender'];
    $names['individual_prefix'] = ['newName' => 'prefix_id', 'groupName' => 'individual_prefix'];
    $names['individual_suffix'] = ['newName' => 'suffix_id', 'groupName' => 'individual_suffix'];
    $names['addressee'] = ['newName' => 'addressee_id', 'groupName' => 'addressee'];
    $names['email_greeting'] = ['newName' => 'email_greeting_id', 'groupName' => 'email_greeting'];
    $names['postal_greeting'] = ['newName' => 'postal_greeting_id', 'groupName' => 'postal_greeting'];
    CRM_Core_OptionGroup::lookupValues($submitted, $names, TRUE);

    // fix custom fields so they're edible by createProfileContact()
    static $treeCache = [];
    if (!CRM_Utils_Array::arrayKeyExists($migrationInfo['main_details']['contact_type'], $treeCache)) {
      $treeCache[$migrationInfo['main_details']['contact_type']] = CRM_Core_BAO_CustomGroup::getTree($migrationInfo['main_details']['contact_type'],
        CRM_Core_DAO::$_nullObject, NULL, -1
      );
    }
    $cgTree = &$treeCache[$migrationInfo['main_details']['contact_type']];

    $cFields = [];
    foreach ($cgTree as $key => $group) {
      if (!isset($group['fields'])) {
        continue;
      }
      foreach ($group['fields'] as $fid => $field) {
        $cFields[$fid]['attributes'] = $field;
      }
    }

    if (!isset($submitted)) {
      $submitted = [];
    }
    foreach ($submitted as $key => $value) {
      if (substr($key, 0, 7) == 'custom_') {
        $fid = (int) substr($key, 7);
        $htmlType = $cFields[$fid]['attributes']['html_type'];
        switch ($htmlType) {
          case 'File':
            $customFiles[] = $fid;
            unset($submitted["custom_$fid"]);
            break;

          case 'Select Country':
          case 'Select State/Province':
            $submitted[$key] = CRM_Core_BAO_CustomField::getDisplayValue($value, $fid, $cFields);
            break;

          case 'CheckBox':
          case 'AdvMulti-Select':
          case 'Multi-Select':
          case 'Multi-Select Country':
          case 'Multi-Select State/Province':
            // Merge values from both contacts for multivalue fields, CRM-4385
            // get the existing custom values from db.
            $customParams = ['entityID' => $mainId, $key => TRUE];
            $customfieldValues = CRM_Core_BAO_CustomValueTable::getValues($customParams);
            if (CRM_Utils_array::value($key, $customfieldValues)) {
              $existingValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $customfieldValues[$key]);
              if (is_array($existingValue) && !empty($existingValue)) {
                $mergeValue = $submmtedCustomValue = [];
                if ($value) {
                  $submmtedCustomValue = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
                }

                //hack to remove null and duplicate values from array.
                foreach (array_merge($submmtedCustomValue, $existingValue) as $k => $v) {
                  if ($v != '' && !in_array($v, $mergeValue)) {
                    $mergeValue[] = $v;
                  }
                }

                //keep state and country as array format.
                //for checkbox and m-select format w/ VALUE_SEPARATOR
                if (in_array($htmlType, [
                  'CheckBox', 'Multi-Select', 'AdvMulti-Select'])) {
                  $submitted[$key] = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR,
                    $mergeValue
                  ) . CRM_Core_DAO::VALUE_SEPARATOR;
                }
                else {
                  $submitted[$key] = $mergeValue;
                }
              }
            }
            elseif (in_array($htmlType, [
              'Multi-Select Country', 'Multi-Select State/Province'])) {
              //we require submitted values should be in array format
              if ($value) {
                $mergeValueArray = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
                //hack to remove null values from array.
                $mergeValue = [];
                foreach ($mergeValueArray as $k => $v) {
                  if ($v != '') {
                    $mergeValue[] = $v;
                  }
                }
                $submitted[$key] = $mergeValue;
              }
            }
            break;

          default:
            break;
        }
      }
    }

    // custom value tables already processed from moveContactBelongings
    // but we need to update custom value's file_id when mainId already have ref file
    if (!isset($customFiles)) {
      $customFiles = [];
    }
    foreach ($customFiles as $customId) {
      list($tableName, $columnName, $groupID) = CRM_Core_BAO_CustomField::getTableColumnGroup($customId);

      $dao = CRM_Core_DAO::executeQuery("SELECT entity_id, entity_table, file_id FROM civicrm_entity_file WHERE entity_id = {$otherId} ORDER BY id DESC");
      $dao->fetch();
      if ($dao->file_id) {
        $entity_file_id = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_entity_file WHERE entity_table = '{$tableName}' AND entity_id = {$mainId}");
        if ($entity_file_id) {
          $sql = "UPDATE civicrm_entity_file SET entity_id = {$mainId}, file_id = {$dao->file_id} WHERE id = {$entity_file_id}";
        }
        else {
          $sql = "INSERT INTO civicrm_entity_file ( entity_table, entity_id, file_id ) VALUES ( '{$tableName}', {$mainId}, {$dao->file_id})";
        }
        CRM_Core_DAO::executeQuery($sql);
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_entity_file WHERE entity_table = '{$tableName}' AND entity_id = {$otherId} AND file_id = {$dao->file_id}");
        if (CRM_Core_DAO::singleValueQuery("SELECT id FROM {$tableName} WHERE entity_id = {$mainId}")) {
          CRM_Core_DAO::executeQuery("UPDATE {$tableName} SET {$columnName} = {$dao->file_id} WHERE entity_id = {$mainId}");
        }
        else {
          CRM_Core_DAO::executeQuery("INSERT INTO {$tableName} (entity_id, {$columnName}) VALUES ({$mainId}, {$dao->file_id})");
        }
      }
      $dao->free();
    }

    // move view only custom fields CRM-5362
    $viewOnlyCustomFields = [];
    foreach ($submitted as $key => $value) {
      $fid = (int) substr($key, 7);
      if (CRM_Utils_Array::arrayKeyExists($fid, $cFields) &&
        CRM_Utils_Array::value('is_view', $cFields[$fid]['attributes'])
      ) {
        $viewOnlyCustomFields[$key] = $value;
      }
    }

    // special case to set values for view only, CRM-5362
    if (!empty($viewOnlyCustomFields)) {
      $viewOnlyCustomFields['entityID'] = $mainId;
      CRM_Core_BAO_CustomValueTable::setValues($viewOnlyCustomFields);
    }

    // **** Update contact related info for the main contact
    if (!empty($submitted)) {
      $submitted['contact_id'] = $mainId;

      //update current employer field
      if ($currentEmloyerId = CRM_Utils_Array::value('current_employer_id', $submitted)) {
        if (!CRM_Utils_System::isNull($currentEmloyerId)) {
          $submitted['current_employer'] = $submitted['current_employer_id'];
        }
        else {
          $submitted['current_employer'] = '';
        }
        unset($submitted['current_employer_id']);
      }
      $submitted['log_data'] = ts('Updated contact') . ' - '.ts('merge duplicate contacts');
      
      // if ext id is submitted then set it null for contact to be deleted to prevent already exists
      if (!empty($submitted['external_identifier'])) {
        $query = "UPDATE civicrm_contact SET external_identifier = null WHERE id = {$otherId}";
        CRM_Core_DAO::executeQuery($query);
      }
      CRM_Contact_BAO_Contact::createProfileContact($submitted, CRM_Core_DAO::$_nullArray, $mainId);
    }

    // **** After migrate, check email on-hold data on other contact when email is duplicated
    $dao = CRM_Core_DAO::executeQuery("SELECT email, on_hold, hold_date FROM civicrm_email WHERE on_hold = 1 AND contact_id = %1", [1 => [$otherId, 'Integer']]);
    while($dao->fetch()) {
      if (empty($dao->hold_date)) {
        $dao->hold_date = 'NULL';
      }
      CRM_Core_DAO::executeQuery("UPDATE civicrm_email SET on_hold = 1, hold_date = %1 WHERE contact_id = %2 AND email = %3", [
        1 => [$dao->hold_date, 'String'],
        2 => [$mainId, 'Integer'],
        3 => [$dao->email, 'String']
      ]);
    }    

    // **** Delete other contact & update prev-next caching
    if (CRM_Core_Permission::check('merge duplicate contacts') &&
      CRM_Core_Permission::check('delete contacts')
    ) {
      CRM_Contact_BAO_Contact::deleteContact($otherId, FALSE, FALSE, ts('Delete Contact').' - '.ts('merge duplicate contacts'));
    }
    // FIXME: else part
    else {
      CRM_Core_Session::setStatus( ts('Do not have sufficient permission to delete duplicate contact.') );

    }

    unset($submitted);
    return TRUE;
  }

  static function formatReason($conflicts) {
    static $lables;
    static $customFields;
    if (empty($labels)) {
      $labels = [];
    }
    if (empty($customFields)) {
      $cfields = CRM_Core_BAO_CustomField::getFields();
      foreach($cfields as $fld) {
        $customFields[$fld['name']]['title'] = $fld['groupTitle'].'::'.$fld['label'];
      }
    }
    $needToFind = array_diff_key($conflicts, $labels);
    $fields = array_merge(CRM_Contact_DAO_Contact::fields(), $customFields);
    foreach($needToFind as $conflict => $dontcare) {
      $field = str_replace('move_', '', $conflict);
      if (isset($fields[$field])) {
        $labels[$conflict] = $fields[$field]['title'];
      }
      else {
        $labels[$conflict] = str_replace('_', ' ', $field);
      }
    }
    return array_intersect_key($labels, $conflicts);
  }


  /**
   * Prioritize parent-child relationship of dupes
   */
  public static function treeDupes($dupes) {
    $tree = [];
    $referenced = [];
    $unique = [];
    foreach ($dupes as $pair) {
      $parent = $pair[0];
      $child = $pair[1];
      $unique[$parent] = 1;
      $unique[$child] = 1;
      if (!isset($tree[$child])) {
        $tree[$child] = [];
      }
      if (!empty($parent) && !isset($referenced[$child])) {
        $tree[$parent][$child] =& $tree[$child];
        $referenced[$child] = 1;
      }
    }
    foreach($tree as $parent => &$child) {
      if (empty($child)) {
        unset($tree[$parent]);
      }
      if ($referenced[$parent]) {
        // it's not root
        unset($tree[$parent]);
      }
    }
    ksort($tree);
    return $tree;
  }

  /**
   * Sort dupes by deepest tree(children) to root(parent)
   */
  static function sortDupes($dupePairs) {
    self::$dupePairsSorted = [];
    $dupeTree = CRM_Dedupe_Merger::treeDupes($dupePairs);
    $iterator = new RecursiveArrayIterator($dupeTree);
    iterator_apply($iterator, [self, 'recursiveIterator'], [$iterator]);
    return self::$dupePairsSorted;
  }
  function recursiveIterator($iterator) {
    while ( $iterator -> valid() ) {
      if ( $iterator->hasChildren() ) {
        self::recursiveIterator($iterator->getChildren());
        $children = $iterator->current();
        if (empty($children)) {
          $iterator->next();
          continue;
        }
      }
      $parent = $iterator->key();
      $pair = $iterator->current();
      foreach ($pair as $child => $dontcare){
        self::$dupePairsSorted[] = [$parent, $child];
      }
      $iterator->next();
    }
  }
}
