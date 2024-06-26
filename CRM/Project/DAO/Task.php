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
class CRM_Project_DAO_Task extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_task';
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
   * Task name.
   *
   * @var string
   */
  public $title;
  /**
   * Optional verbose description of the Task. May be used for display - HTML allowed.
   *
   * @var string
   */
  public $description;
  /**
   * Configurable task type values (e.g. App Submit, App Review...). FK to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $task_type_id;
  /**
   * Name of table where Task owner being referenced is stored (e.g. civicrm_contact or civicrm_group).
   *
   * @var string
   */
  public $owner_entity_table;
  /**
   * Foreign key to Task owner (contact, group, etc.).
   *
   * @var int unsigned
   */
  public $owner_entity_id;
  /**
   * Name of table where optional Task parent is stored (e.g. civicrm_project, or civicrm_task for sub-tasks).
   *
   * @var string
   */
  public $parent_entity_table;
  /**
   * Optional foreign key to Task Parent (project, another task, etc.).
   *
   * @var int unsigned
   */
  public $parent_entity_id;
  /**
   * Task due date.
   *
   * @var datetime
   */
  public $due_date;
  /**
   * Configurable priority value (e.g. Critical, High, Medium...). FK to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $priority_id;
  /**
   * Optional key to a process class related to this task (e.g. CRM_Quest_PreApp).
   *
   * @var string
   */
  public $task_class;
  /**
   * Is this record active? For tasks: can it be assigned, does it appear on open task listings, etc.
   *
   * @var boolean
   */
  public $is_active;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_task
   */
  function __construct()
  {
    parent::__construct();
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
        'title' => array(
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Title') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'description' => array(
          'name' => 'description',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Description') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'task_type_id' => array(
          'name' => 'task_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Task Type') ,
        ) ,
        'owner_entity_table' => array(
          'name' => 'owner_entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Owner Entity Table') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'owner_entity_id' => array(
          'name' => 'owner_entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Task Owner ID') ,
          'required' => true,
        ) ,
        'parent_entity_table' => array(
          'name' => 'parent_entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Parent Entity Table') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'parent_entity_id' => array(
          'name' => 'parent_entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Task Parent') ,
        ) ,
        'due_date' => array(
          'name' => 'due_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Due Date') ,
        ) ,
        'priority_id' => array(
          'name' => 'priority_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Priority') ,
        ) ,
        'task_class' => array(
          'name' => 'task_class',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Task Class') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Active?') ,
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
            self::$_import['task'] = &$fields[$name];
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
            self::$_export['task'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
