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
class CRM_Core_DAO_PreferencesDate extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_preferences_date';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  public static $_fields = NULL;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  public static $_links = NULL;
  /**
   * static instance to hold the values that can
   * be imported / apu
   *
   * @var array
   * @static
   */
  public static $_import = NULL;
  /**
   * static instance to hold the values that can
   * be exported / apu
   *
   * @var array
   * @static
   */
  public static $_export = NULL;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  public static $_log = TRUE;
  /**
   *
   * @var int unsigned
   */
  public $id;
  /**
   * The meta name for this date (fixed in code)
   *
   * @var string
   */
  public $name;
  /**
   * Description of this date type.
   *
   * @var string
   */
  public $description;
  /**
   * The start offset relative to current year
   *
   * @var int
   */
  public $start;
  /**
   * The end offset relative to current year, can be negative
   *
   * @var int
   */
  public $end;
  /**
   * The date type
   *
   * @var string
   */
  public $date_format;
  /**
   * time format
   *
   * @var string
   */
  public $time_format;
  /**
  * class constructor
  *
  * @access public
  * @return civicrm_preferences_date
  */
  public function __construct() {
    parent::__construct();
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
                  ] ,
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
          'required' => TRUE,
           'maxlength' => 64,
           'size' => CRM_Utils_Type::BIG,
                ] ,
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Description') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'start' => [
          'name' => 'start',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Start') ,
          'required' => TRUE,
                  ] ,
        'end' => [
          'name' => 'end',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('End') ,
          'required' => TRUE,
                  ] ,
        'date_format' => [
          'name' => 'date_format',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Date Format') ,
           'maxlength' => 64,
           'size' => CRM_Utils_Type::BIG,
                ] ,
        'time_format' => [
          'name' => 'time_format',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Time Format') ,
           'maxlength' => 64,
           'size' => CRM_Utils_Type::BIG,
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
  public static function getTableName() {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  public function getLog() {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   */
  public static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['preferences_date'] = &$fields[$name];
          }
          else {
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
  public static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['preferences_date'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
