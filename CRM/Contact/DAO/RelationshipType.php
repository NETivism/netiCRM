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
class CRM_Contact_DAO_RelationshipType extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_relationship_type';
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
   * Primary key
   *
   * @var int unsigned
   */
  public $id;
  /**
   * name for relationship of contact_a to contact_b.
   *
   * @var string
   */
  public $name_a_b;
  /**
   * label for relationship of contact_a to contact_b.
   *
   * @var string
   */
  public $label_a_b;
  /**
   * Optional name for relationship of contact_b to contact_a.
   *
   * @var string
   */
  public $name_b_a;
  /**
   * Optional label for relationship of contact_b to contact_a.
   *
   * @var string
   */
  public $label_b_a;
  /**
   * Optional verbose description of the relationship type.
   *
   * @var string
   */
  public $description;
  /**
   * If defined, contact_a in a relationship of this type must be a specific contact_type.
   *
   * @var enum('Individual', 'Organization', 'Household')
   */
  public $contact_type_a;
  /**
   * If defined, contact_b in a relationship of this type must be a specific contact_type.
   *
   * @var enum('Individual', 'Organization', 'Household')
   */
  public $contact_type_b;
  /**
   * If defined, contact_sub_type_a in a relationship of this type must be a specific contact_sub_type.
   *
   * @var string
   */
  public $contact_sub_type_a;
  /**
   * If defined, contact_sub_type_b in a relationship of this type must be a specific contact_sub_type.
   *
   * @var string
   */
  public $contact_sub_type_b;
  /**
   * Is this relationship type a predefined system type (can not be changed or de-activated)?
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * Is this relationship type currently active (i.e. can be used when creating or editing relationships)?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_relationship_type
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
        'name_a_b' => array(
          'name' => 'name_a_b',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name A B') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'label_a_b' => array(
          'name' => 'label_a_b',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label A B') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'name_b_a' => array(
          'name' => 'name_b_a',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name B A') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'label_b_a' => array(
          'name' => 'label_b_a',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label B A') ,
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
        'contact_type_a' => array(
          'name' => 'contact_type_a',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Contact Type A') ,
          'enumValues' => 'Individual, Organization, Household',
        ) ,
        'contact_type_b' => array(
          'name' => 'contact_type_b',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Contact Type B') ,
          'enumValues' => 'Individual, Organization, Household',
        ) ,
        'contact_sub_type_a' => array(
          'name' => 'contact_sub_type_a',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contact Subtype A') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'contact_sub_type_b' => array(
          'name' => 'contact_sub_type_b',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contact Subtype B') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
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
            self::$_import['relationship_type'] = &$fields[$name];
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
            self::$_export['relationship_type'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_relationship_type table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = array(
      'contact_type_a',
      'contact_type_b',
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
        'contact_type_a' => array(
          'Individual' => ts('Individual') ,
          'Organization' => ts('Organization') ,
          'Household' => ts('Household') ,
        ) ,
        'contact_type_b' => array(
          'Individual' => ts('Individual') ,
          'Organization' => ts('Organization') ,
          'Household' => ts('Household') ,
        ) ,
      );
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_relationship_type
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Contact_DAO_RelationshipType::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Contact_DAO_RelationshipType::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
