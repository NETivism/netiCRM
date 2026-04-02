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

class CRM_ACL_DAO_ACL extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_acl';
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
   * Unique table ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * ACL Name.
   *
   * @var string
   */
  public $name;
  /**
   * Is this ACL entry Allow  (0) or Deny (1) ?
   *
   * @var boolean
   */
  public $deny;
  /**
   * Table of the object possessing this ACL entry (Contact, Group, or ACL Group)
   *
   * @var string
   */
  public $entity_table;
  /**
   * ID of the object possessing this ACL
   *
   * @var int unsigned
   */
  public $entity_id;
  /**
   * What operation does this ACL entry control?
   *
   * @var enum('All', 'View', 'Edit', 'Create', 'Delete', 'Grant', 'Revoke', 'Search')
   */
  public $operation;
  /**
   * The table of the object controlled by this ACL entry
   *
   * @var string
   */
  public $object_table;
  /**
   * The ID of the object controlled by this ACL entry
   *
   * @var int unsigned
   */
  public $object_id;
  /**
   * If this is a grant/revoke entry, what table are we granting?
   *
   * @var string
   */
  public $acl_table;
  /**
   * ID of the ACL or ACL group being granted/revoked
   *
   * @var int unsigned
   */
  public $acl_id;
  /**
   * Is this property active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * class constructor
   *
   * @return civicrm_acl
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Dynamic(self::getTableName(), 'entity_id', NULL, 'id', 'entity_table');
    }
    return Civi::$statics[__CLASS__]['links'];
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
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('ACL Name') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ],
        'deny' => [
          'name' => 'deny',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Deny') ,
          'required' => TRUE,
        ],
        'entity_table' => [
          'name' => 'entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Entity Table') ,
          'required' => TRUE,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ],
        'entity_id' => [
          'name' => 'entity_id',
          'type' => CRM_Utils_Type::T_INT,
        ],
        'operation' => [
          'name' => 'operation',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Operation') ,
          'required' => TRUE,
          'enumValues' => 'All,View,Edit,Create,Delete,Grant,Revoke,Search',
        ],
        'object_table' => [
          'name' => 'object_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Object Table') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ],
        'object_id' => [
          'name' => 'object_id',
          'type' => CRM_Utils_Type::T_INT,
        ],
        'acl_table' => [
          'name' => 'acl_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Acl Table') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ],
        'acl_id' => [
          'name' => 'acl_id',
          'type' => CRM_Utils_Type::T_INT,
        ],
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
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
            self::$_import['acl'] = &$fields[$name];
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
            self::$_export['acl'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_acl table
   *
   * @return array (reference)  the array of enum fields
   */
  public static function &getEnums() {
    static $enums = [
        'operation',
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
  public static function tsEnum($field, $value) {
    static $translations = NULL;
    if (!$translations) {
      $translations = [
        'operation' => [
          'All' => ts('All'),
          'View' => ts('View'),
          'Edit' => ts('Edit'),
          'Create' => ts('Create'),
          'Delete' => ts('Delete'),
          'Grant' => ts('Grant'),
          'Revoke' => ts('Revoke'),
          'Search' => ts('Search'),
        ],
      ];
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_acl
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  public static function addDisplayEnums(&$values) {
    $enumFields = &CRM_ACL_DAO_ACL::getEnums();
    foreach ($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_ACL_DAO_ACL::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
