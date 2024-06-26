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
class CRM_Core_DAO_MappingField extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_mapping_field';
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
  static $_log = false;
  /**
   * Mapping Field ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Mapping to which this field belongs
   *
   * @var int unsigned
   */
  public $mapping_id;
  /**
   * Mapping field key
   *
   * @var string
   */
  public $name;
  /**
   * Contact Type in mapping
   *
   * @var string
   */
  public $contact_type;
  /**
   * Column number for mapping set
   *
   * @var int unsigned
   */
  public $column_number;
  /**
   * Location type of this mapping, if required
   *
   * @var int unsigned
   */
  public $location_type_id;
  /**
   * Which type of phone does this number belongs.
   *
   * @var int unsigned
   */
  public $phone_type_id;
  /**
   * Which type of IM Provider does this name belong.
   *
   * @var int unsigned
   */
  public $im_provider_id;
  /**
   * Which type of website does this site belong
   *
   * @var int unsigned
   */
  public $website_type_id;
  /**
   * Relationship type, if required
   *
   * @var int unsigned
   */
  public $relationship_type_id;
  /**
   *
   * @var string
   */
  public $relationship_direction;
  /**
   * Used to group mapping_field records into related sets (e.g. for criteria sets in search builder mappings).
   *
   * @var int unsigned
   */
  public $grouping;
  /**
   * SQL WHERE operator for search-builder mapping fields (search criteria).
   *
   * @var enum('=', '!=', '>', '<', '>=', '<=', 'IN', 'NOT IN', 'LIKE', 'NOT LIKE', 'IS NULL', 'IS NOT NULL')
   */
  public $operator;
  /**
   * SQL WHERE value for search-builder mapping fields.
   *
   * @var string
   */
  public $value;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_mapping_field
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
        'mapping_id' => 'civicrm_mapping:id',
        'location_type_id' => 'civicrm_location_type:id',
        'relationship_type_id' => 'civicrm_relationship_type:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'mapping_id', 'civicrm_mapping', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'location_type_id', 'civicrm_location_type', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'relationship_type_id', 'civicrm_relationship_type', 'id');
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
        'mapping_id' => array(
          'name' => 'mapping_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Core_DAO_Mapping',
        ) ,
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'contact_type' => array(
          'name' => 'contact_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contact Type') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'column_number' => array(
          'name' => 'column_number',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Column Number') ,
          'required' => true,
        ) ,
        'location_type_id' => array(
          'name' => 'location_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_LocationType',
        ) ,
        'phone_type_id' => array(
          'name' => 'phone_type_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'im_provider_id' => array(
          'name' => 'im_provider_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'website_type_id' => array(
          'name' => 'website_type_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'relationship_type_id' => array(
          'name' => 'relationship_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_RelationshipType',
        ) ,
        'relationship_direction' => array(
          'name' => 'relationship_direction',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Relationship Direction') ,
          'maxlength' => 6,
          'size' => CRM_Utils_Type::EIGHT,
        ) ,
        'grouping' => array(
          'name' => 'grouping',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grouping') ,
          'default' => '',
        ) ,
        'operator' => array(
          'name' => 'operator',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Operator') ,
          'enumValues' => '=, !=, >, <, >=, <=,
       IN, NOT IN, LIKE, NOT LIKE, IS NULL, IS NOT NULL',
        ) ,
        'value' => array(
          'name' => 'value',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Value') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
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
            self::$_import['mapping_field'] = &$fields[$name];
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
            self::$_export['mapping_field'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_mapping_field table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = array(
      'operator',
    );
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
      $translations = array(
        'operator' => array(
          '=' => ts('=') ,
          '!=' => ts('!=') ,
          '>' => ts('>') ,
          '<' => ts('<') ,
          '>=' => ts('>=') ,
          '<=' => ts('<=') ,
          'IN' => ts('IN') ,
          'NOT IN' => ts('NOT IN') ,
          'LIKE' => ts('LIKE') ,
          'NOT LIKE' => ts('NOT LIKE') ,
          'IS NULL' => ts('IS NULL') ,
          'IS NOT NULL' => ts('IS NOT NULL') ,
        ) ,
      );
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_mapping_field
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Core_DAO_MappingField::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Core_DAO_MappingField::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
