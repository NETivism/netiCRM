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
class CRM_Case_DAO_Case extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_case';
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
   * Unique Case ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Id of first case category.
   *
   * @var string
   */
  public $case_type_id;
  /**
   * Short name of the case.
   *
   * @var string
   */
  public $subject;
  /**
   * Date on which given case starts.
   *
   * @var date
   */
  public $start_date;
  /**
   * Date on which given case ends.
   *
   * @var date
   */
  public $end_date;
  /**
   * Details about the meeting (agenda, notes, etc).
   *
   * @var text
   */
  public $details;
  /**
   * Id of case status.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   *
   * @var boolean
   */
  public $is_deleted;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_case
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
        'case_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Case ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_case.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'case_type_id' => array(
          'name' => 'case_type_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Case Type Id') ,
          'required' => true,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_case.case_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'subject' => array(
          'name' => 'subject',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Subject') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'case_start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Case Start Date') ,
          'import' => true,
          'where' => 'civicrm_case.start_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'case_end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Case End Date') ,
          'import' => true,
          'where' => 'civicrm_case.end_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'details' => array(
          'name' => 'details',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Details') ,
          'rows' => 8,
          'cols' => 60,
        ) ,
        'case_status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Case Status Id') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_case.status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'case_deleted' => array(
          'name' => 'is_deleted',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Case is in the Trash') ,
          'import' => true,
          'where' => 'civicrm_case.is_deleted',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
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
            self::$_import['case'] = &$fields[$name];
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
            self::$_export['case'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
