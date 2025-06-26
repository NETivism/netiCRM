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
class CRM_Member_DAO_MembershipStatus extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_membership_status';
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
   * Membership Id
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Name for Membership Status
   *
   * @var string
   */
  public $name;
  /**
   * Label for Membership Status
   *
   * @var string
   */
  public $label;
  /**
   * Event when this status starts.
   *
   * @var enum('start_date', 'end_date', 'join_date')
   */
  public $start_event;
  /**
   * Unit used for adjusting from start_event.
   *
   * @var enum('day', 'month', 'year')
   */
  public $start_event_adjust_unit;
  /**
   * Status range begins this many units from start_event.
   *
   * @var int
   */
  public $start_event_adjust_interval;
  /**
   * Event after which this status ends.
   *
   * @var enum('start_date', 'end_date', 'join_date')
   */
  public $end_event;
  /**
   * Unit used for adjusting from the ending event.
   *
   * @var enum('day', 'month', 'year')
   */
  public $end_event_adjust_unit;
  /**
   * Status range ends this many units from end_event.
   *
   * @var int
   */
  public $end_event_adjust_interval;
  /**
   * Does this status aggregate to current members (e.g. New, Renewed, Grace might all be TRUE... while Unrenewed, Lapsed, Inactive would be FALSE).
   *
   * @var boolean
   */
  public $is_current_member;
  /**
   * Is this status for admin/manual assignment only.
   *
   * @var boolean
   */
  public $is_admin;
  /**
   *
   * @var int
   */
  public $weight;
  /**
   * Assign this status to a membership record if no other status match is found.
   *
   * @var boolean
   */
  public $is_default;
  /**
   * Is this membership_status enabled.
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is this membership_status reserved.
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_membership_status
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
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ] ,
        'membership_status' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Membership Status') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_membership_status.name',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ] ,
        'label' => [
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ] ,
        'start_event' => [
          'name' => 'start_event',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Start Event') ,
          'enumValues' => 'start_date, end_date, join_date',
        ] ,
        'start_event_adjust_unit' => [
          'name' => 'start_event_adjust_unit',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Start Event Adjust Unit') ,
          'enumValues' => 'day, month, year',
        ] ,
        'start_event_adjust_interval' => [
          'name' => 'start_event_adjust_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Start Event Adjust Interval') ,
        ] ,
        'end_event' => [
          'name' => 'end_event',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('End Event') ,
          'enumValues' => 'start_date, end_date, join_date',
        ] ,
        'end_event_adjust_unit' => [
          'name' => 'end_event_adjust_unit',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('End Event Adjust Unit') ,
          'enumValues' => 'day, month, year',
        ] ,
        'end_event_adjust_interval' => [
          'name' => 'end_event_adjust_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('End Event Adjust Interval') ,
        ] ,
        'is_current_member' => [
          'name' => 'is_current_member',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Current Membership?') ,
        ] ,
        'is_admin' => [
          'name' => 'is_admin',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Admin Assigned Only?') ,
        ] ,
        'weight' => [
          'name' => 'weight',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Weight') ,
        ] ,
        'is_default' => [
          'name' => 'is_default',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Default Status?') ,
        ] ,
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Active') ,
          'default' => '',
        ] ,
        'is_reserved' => [
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Reserved') ,
        ] ,
      ];
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
    global $dbLocale;
    return self::$_tableName . $dbLocale;
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
      self::$_import = [];
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['membership_status'] = &$fields[$name];
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
      self::$_export = [];
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['membership_status'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_membership_status table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = [
      'start_event',
      'start_event_adjust_unit',
      'end_event',
      'end_event_adjust_unit',
    ];
    return $enums;
  }
  /**
   * returns a ts()-translated enum value for display purposes
   *
   * @param string $field  the enum field in question
   * @param string $value  the enum value up for translation
   *
   * @return string  the display value of the enum
   */
  static function tsEnum($field, $value)
  {
    static $translations = null;
    if (!$translations) {
      $translations = [
        'start_event' => [
          'start_date' => ts('start_date') ,
          'end_date' => ts('end_date') ,
          'join_date' => ts('join_date') ,
        ] ,
        'start_event_adjust_unit' => [
          'day' => ts('day') ,
          'month' => ts('month') ,
          'year' => ts('year') ,
        ] ,
        'end_event' => [
          'start_date' => ts('start_date') ,
          'end_date' => ts('end_date') ,
          'join_date' => ts('join_date') ,
        ] ,
        'end_event_adjust_unit' => [
          'day' => ts('day') ,
          'month' => ts('month') ,
          'year' => ts('year') ,
        ] ,
      ];
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_membership_status
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Member_DAO_MembershipStatus::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Member_DAO_MembershipStatus::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
