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
class CRM_Core_DAO_Country extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_country';
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
   * Country Id
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Country Name
   *
   * @var string
   */
  public $name;
  /**
   * ISO Code
   *
   * @var string
   */
  public $iso_code;
  /**
   * National prefix to be used when dialing TO this country.
   *
   * @var string
   */
  public $country_code;
  /**
   * Foreign key to civicrm_address_format.id.
   *
   * @var int unsigned
   */
  public $address_format_id;
  /**
   * International direct dialing prefix from within the country TO another country
   *
   * @var string
   */
  public $idd_prefix;
  /**
   * Access prefix to call within a country to a different area
   *
   * @var string
   */
  public $ndd_prefix;
  /**
   * Foreign key to civicrm_worldregion.id.
   *
   * @var int unsigned
   */
  public $region_id;
  /**
   * Should state/province be displayed as abbreviation for contacts from this country?
   *
   * @var boolean
   */
  public $is_province_abbreviated;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_country
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
        'address_format_id' => 'civicrm_address_format:id',
        'region_id' => 'civicrm_worldregion:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'address_format_id', 'civicrm_address_format', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'region_id', 'civicrm_worldregion', 'id');
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
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Country') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_country.name',
          'headerPattern' => '/country/i',
          'dataPattern' => '/^[A-Z][a-z]+\.?(\s+[A-Z][a-z]+){0,3}$/',
          'export' => true,
        ) ,
        'iso_code' => array(
          'name' => 'iso_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Iso Code') ,
          'maxlength' => 2,
          'size' => CRM_Utils_Type::TWO,
        ) ,
        'country_code' => array(
          'name' => 'country_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Country Code') ,
          'maxlength' => 4,
          'size' => CRM_Utils_Type::FOUR,
        ) ,
        'address_format_id' => array(
          'name' => 'address_format_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_AddressFormat',
        ) ,
        'idd_prefix' => array(
          'name' => 'idd_prefix',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Idd Prefix') ,
          'maxlength' => 4,
          'size' => CRM_Utils_Type::FOUR,
        ) ,
        'ndd_prefix' => array(
          'name' => 'ndd_prefix',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Ndd Prefix') ,
          'maxlength' => 4,
          'size' => CRM_Utils_Type::FOUR,
        ) ,
        'region_id' => array(
          'name' => 'region_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Core_DAO_Worldregion',
        ) ,
        'is_province_abbreviated' => array(
          'name' => 'is_province_abbreviated',
          'type' => CRM_Utils_Type::T_BOOLEAN,
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
            self::$_import['country'] = &$fields[$name];
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
            self::$_export['country'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
