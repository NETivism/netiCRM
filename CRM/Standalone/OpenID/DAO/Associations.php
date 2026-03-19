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

class CRM_Standalone_OpenID_DAO_Associations extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_openid_associations';
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
   *
   * @var blob
   */
  public $server_url;
  /**
   *
   * @var string
   */
  public $handle;
  /**
   *
   * @var blob
   */
  public $secret;
  /**
   *
   * @var int
   */
  public $issued;
  /**
   *
   * @var int
   */
  public $lifetime;
  /**
   *
   * @var string
   */
  public $assoc_type;
  /**
   * class constructor
   *
   * @return civicrm_openid_associations
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
        ],
        'server_url' => [
          'name' => 'server_url',
          'type' => CRM_Utils_Type::T_BLOB,
          'title' => ts('Server Url') ,
        ],
        'handle' => [
          'name' => 'handle',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Handle') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'secret' => [
          'name' => 'secret',
          'type' => CRM_Utils_Type::T_BLOB,
          'title' => ts('Secret') ,
        ],
        'issued' => [
          'name' => 'issued',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Issued') ,
        ],
        'lifetime' => [
          'name' => 'lifetime',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Lifetime') ,
        ],
        'assoc_type' => [
          'name' => 'assoc_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Assoc Type') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ],
      ];
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @return boolean
   */
  public function getLog() {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['openid_associations'] = &$fields[$name];
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
   * @return array
   */
  public static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['openid_associations'] = &$fields[$name];
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
