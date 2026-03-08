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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

/**
 * BAO object for crm_log table
 */
class CRM_Core_BAO_Log extends CRM_Core_DAO_Log {
  public static $_processed = NULL;

  /**
   * Get the last modified log entry for a specific entity.
   *
   * @param int $id entity ID
   * @param string $table name of the entity table (defaults to 'civicrm_contact')
   * @param string $order sort order ('desc' or 'asc')
   *
   * @return array associative array of log details
   */
  public static function lastModified($id, $table = 'civicrm_contact', $order = 'desc') {
    $log = new CRM_Core_DAO_Log();

    $log->entity_table = $table;
    $log->entity_id = $id;
    $log->orderBy('modified_date '.$order);
    $log->limit(1);
    $result = [];
    if ($log->find(TRUE)) {
      if (!empty($log->modified_id)) {
        list($displayName, $contactImage) = CRM_Contact_BAO_Contact::getDisplayAndImage($log->modified_id);
      }
      $result = [
        'log_id' => $log->id,
        'id' => $log->modified_id,
        'name' => $displayName,
        'image' => $contactImage,
        'date' => $log->modified_date,
        'data' => $log->data,
      ];
    }
    return $result;
  }

  /**
   * Add a log entry to the civicrm_log table.
   *
   * @param array &$params associative array of log data
   *
   * @return CRM_Core_BAO_Log the created log object
   */
  public static function add(&$params) {

    $log = new CRM_Core_DAO_Log();
    $log->copyValues($params);
    $log->save();
    return $log;
  }

  /**
   * Register a modification to an entity in the log.
   *
   * @param int $contactID contact ID being modified
   * @param string $tableName name of the table being modified
   * @param int $tableID ID of the record being modified
   * @param int|null $userID optional ID of the user performing the modification
   * @param string|null $data optional custom log data
   *
   * @return void
   */
  public static function register($contactID, $tableName, $tableID, $userID = NULL, $data = NULL) {
    if (!self::$_processed) {
      self::$_processed = [];
    }

    if (!$userID) {
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
    }

    if (!$userID) {
      $userID = $contactID;
    }

    if (!$userID) {
      return;
    }

    $log = new CRM_Core_DAO_Log();
    $log->id = NULL;

    if (isset(self::$_processed[$contactID])) {
      if (isset(self::$_processed[$contactID][$userID])) {
        $log->id = self::$_processed[$contactID][$userID];
      }
      self::$_processed[$contactID][$userID] = 1;
    }
    else {
      self::$_processed[$contactID] = [$userID => 1];
    }

    $logData = "$tableName,$tableID";
    if (!$log->id) {
      $log->entity_table = 'civicrm_contact';
      $log->entity_id = $contactID;
      $log->modified_id = $userID;
      $log->modified_date = date("YmdHis");
      $log->data = $data ? $data : $logData;
      $log->save();
    }
    else {
      $query = "
UPDATE civicrm_log
   SET data = concat( data, ':$logData' )
 WHERE id = {$log->id}
";
      CRM_Core_DAO::executeQuery($query);
    }

    self::$_processed[$contactID][$userID] = $log->id;
  }

  /**
   * Write an audit log for a specific entity.
   *
   * @param int $entityId ID of the entity
   * @param string $auditType type of audit (auto-prepended with 'audit.')
   * @param string $data audit log data
   *
   * @return void
   */
  public static function audit($entityId, $auditType, $data) {
    if (!$entityId || !$auditType || empty($data)) {
      return;
    }
    $userID = CRM_Core_Session::singleton()->get('userID');
    $userID = $userID ? $userID : 'null';
    $log = new CRM_Core_DAO_Log();
    $log->id = NULL;
    $log->entity_table = 'audit.'.$auditType;
    $log->entity_id = $entityId;
    $log->modified_id = $userID;
    $log->modified_date = date("YmdHis");
    $log->data = $data;
    $log->save();
  }

  /**
   * Get the count of log records for a specific contact.
   *
   * @param int $contactID contact ID
   *
   * @return int count of log records
   */
  public static function getContactLogCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_log 
                   WHERE civicrm_log.entity_table = 'civicrm_contact' AND civicrm_log.entity_id = {$contactID}";
    return CRM_Core_DAO::singleValueQuery($query);
  }
}
