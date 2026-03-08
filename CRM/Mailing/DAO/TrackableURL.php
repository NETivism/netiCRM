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
class CRM_Mailing_DAO_TrackableURL extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_mailing_trackable_url';
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
  public static $_log = FALSE;
  /**
   *
   * @var int unsigned
   */
  public $id;
  /**
   * The URL to be tracked.
   *
   * @var string
   */
  public $url;
  /**
   * FK to the mailing
   *
   * @var int unsigned
   */
  public $mailing_id;
  /**
   * Class constructor.
   *
   * @return CRM_Mailing_DAO_TrackableURL
   */
  public function __construct() {
    parent::__construct();
  }
  /**
   * Return foreign links.
   *
   * @return array Array of foreign links.
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'mailing_id' => 'civicrm_mailing:id',
      ];
    }
    return self::$_links;
  }
  /**
   * Returns foreign keys and entity references.
   *
   * @return array Array of CRM_Core_Reference_Interface objects.
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'mailing_id', 'civicrm_mailing', 'id');
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * Returns all the column names of this table.
   *
   * @return array Array of column names.
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
                  ] ,
        'url' => [
          'name' => 'url',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Url') ,
          'required' => TRUE,
           'maxlength' => 511,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'mailing_id' => [
          'name' => 'mailing_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
                    'FKClassName' => 'CRM_Mailing_DAO_Mailing',
        ] ,
      ];
    }
    return self::$_fields;
  }
  /**
   * Returns the name of this table.
   *
   * @return string The table name.
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
   * Returns the list of fields that can be imported.
   *
   * @param bool $prefix Whether to prefix the field names.
   *
   * @return array Array of importable fields.
   */
  public static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['mailing_trackable_url'] = &$fields[$name];
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
   * Returns the list of fields that can be exported.
   *
   * @param bool $prefix Whether to prefix the field names.
   *
   * @return array Array of exportable fields.
   */
  public static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['mailing_trackable_url'] = &$fields[$name];
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
