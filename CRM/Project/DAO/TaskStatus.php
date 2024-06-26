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
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Project_DAO_TaskStatus extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_task_status';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported / apu
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported / apu
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = true;
  /**
   * Task ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Status is for which task.
   *
   * @var int unsigned
   */
  public $task_id;
  /**
   * Entity responsible for this task_status instance (table where entity is stored e.g. civicrm_contact or civicrm_group).
   *
   * @var string
   */
  public $responsible_entity_table;
  /**
   * Foreign key to responsible entity (contact, group, etc.).
   *
   * @var int unsigned
   */
  public $responsible_entity_id;
  /**
   * Optional target entity for this task_status instance, i.e. review this membership application-prospect member contact record is target (table where entity is stored e.g. civicrm_contact or civicrm_group).
   *
   * @var string
   */
  public $target_entity_table;
  /**
   * Foreign key to target entity (contact, group, etc.).
   *
   * @var int unsigned
   */
  public $target_entity_id;
  /**
   * Encoded array of status details used for programmatic progress reporting and tracking.
   *
   * @var text
   */
  public $status_detail;
  /**
   * Configurable status value (e.g. Not Started, In Progress, Completed, Deferred...). FK to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Date this record was created (date work on task started).
   *
   * @var datetime
   */
  public $create_date;
  /**
   * Date-time of last update to this task_status record.
   *
   * @var datetime
   */
  public $modified_date;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_task_status
   */
  function __construct()
  {
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  function &links()
  {
    if (!(self::$_links)) {
      self::$_links = array(
        'task_id' => 'civicrm_task:id',
      );
    }
    return self::$_links;
  }
  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns()
  {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'task_id', 'civicrm_task', 'id');
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'task_id' => array(
          'name' => 'task_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Project_DAO_Task',
        ) ,
        'responsible_entity_table' => array(
          'name' => 'responsible_entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Responsible Entity Table') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'responsible_entity_id' => array(
          'name' => 'responsible_entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Responsible') ,
          'required' => true,
        ) ,
        'target_entity_table' => array(
          'name' => 'target_entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Target Entity Table') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'target_entity_id' => array(
          'name' => 'target_entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Target') ,
          'required' => true,
        ) ,
        'status_detail' => array(
          'name' => 'status_detail',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Status Details') ,
        ) ,
        'status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Status') ,
        ) ,
        'create_date' => array(
          'name' => 'create_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Created') ,
        ) ,
        'modified_date' => array(
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Last Modified') ,
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @return string
   */
  static function getTableName()
  {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['task_status'] = &$fields[$name];
          } else {
            self::$_import[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['task_status'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
