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
require_once 'CRM/Core/DAO/County.php';
require_once 'CRM/Core/DAO/StateProvince.php';
require_once 'CRM/Core/DAO/Country.php';
class CRM_Core_DAO_Address extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_address';
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
   * Unique Address ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to Contact ID
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Which Location does this address belong to.
   *
   * @var int unsigned
   */
  public $location_type_id;
  /**
   * Is this the primary address.
   *
   * @var boolean
   */
  public $is_primary;
  /**
   * Is this the billing address.
   *
   * @var boolean
   */
  public $is_billing;
  /**
   * Concatenation of all routable street address components (prefix, street number, street name, suffix, unit number OR P.O. Box). Apps should be able to determine physical location with this data (for mapping, mail delivery, etc.).
   *
   * @var string
   */
  public $street_address;
  /**
   * Numeric portion of address number on the street, e.g. For 112A Main St, the street_number = 112.
   *
   * @var int
   */
  public $street_number;
  /**
   * Non-numeric portion of address number on the street, e.g. For 112A Main St, the street_number_suffix = A
   *
   * @var string
   */
  public $street_number_suffix;
  /**
   * Directional prefix, e.g. SE Main St, SE is the prefix.
   *
   * @var string
   */
  public $street_number_predirectional;
  /**
   * Actual street name, excluding St, Dr, Rd, Ave, e.g. For 112 Main St, the street_name = Main.
   *
   * @var string
   */
  public $street_name;
  /**
   * St, Rd, Dr, etc.
   *
   * @var string
   */
  public $street_type;
  /**
   * Directional prefix, e.g. Main St S, S is the suffix.
   *
   * @var string
   */
  public $street_number_postdirectional;
  /**
   * Secondary unit designator, e.g. Apt 3 or Unit # 14, or Bldg 1200
   *
   * @var string
   */
  public $street_unit;
  /**
   * Supplemental Address Information, Line 1
   *
   * @var string
   */
  public $supplemental_address_1;
  /**
   * Supplemental Address Information, Line 2
   *
   * @var string
   */
  public $supplemental_address_2;
  /**
   * Supplemental Address Information, Line 3
   *
   * @var string
   */
  public $supplemental_address_3;
  /**
   * City, Town or Village Name.
   *
   * @var string
   */
  public $city;
  /**
   * Which County does this address belong to.
   *
   * @var int unsigned
   */
  public $county_id;
  /**
   * Which State_Province does this address belong to.
   *
   * @var int unsigned
   */
  public $state_province_id;
  /**
   * Store the suffix, like the +4 part in the USPS system.
   *
   * @var string
   */
  public $postal_code_suffix;
  /**
   * Store both US (zip5) AND international postal codes. App is responsible for country/region appropriate validation.
   *
   * @var string
   */
  public $postal_code;
  /**
   * USPS Bulk mailing code.
   *
   * @var string
   */
  public $usps_adc;
  /**
   * Which Country does this address belong to.
   *
   * @var int unsigned
   */
  public $country_id;
  /**
   * Latitude
   *
   * @var float
   */
  public $geo_code_1;
  /**
   * Longitude
   *
   * @var float
   */
  public $geo_code_2;
  /**
   * Timezone expressed as a UTC offset - e.g. United States CST would be written as "UTC-6".
   *
   * @var string
   */
  public $timezone;
  /**
   *
   * @var string
   */
  public $name;
  /**
   * FK to Address ID
   *
   * @var int unsigned
   */
  public $master_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_address
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
        'contact_id' => 'civicrm_contact:id',
        'county_id' => 'civicrm_county:id',
        'state_province_id' => 'civicrm_state_province:id',
        'country_id' => 'civicrm_country:id',
        'master_id' => 'civicrm_address:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'county_id', 'civicrm_county', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'state_province_id', 'civicrm_state_province', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'country_id', 'civicrm_country', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'master_id', 'civicrm_address', 'id');
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
        'contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'location_type_id' => array(
          'name' => 'location_type_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'is_primary' => array(
          'name' => 'is_primary',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_billing' => array(
          'name' => 'is_billing',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'street_address' => array(
          'name' => 'street_address',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Address') ,
          'maxlength' => 96,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_address.street_address',
          'headerPattern' => '/(street|address)/i',
          'dataPattern' => '/^(\d{1,5}( [0-9A-Za-z]+)+)$|^(P\.?O\.\? Box \d{1,5})$/i',
          'export' => true,
        ) ,
        'street_number' => array(
          'name' => 'street_number',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Street Number') ,
          'export' => true,
          'where' => 'civicrm_address.street_number',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'street_number_suffix' => array(
          'name' => 'street_number_suffix',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Number Suffix') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
          'export' => true,
          'where' => 'civicrm_address.street_number_suffix',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'street_number_predirectional' => array(
          'name' => 'street_number_predirectional',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Number Predirectional') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
        ) ,
        'street_name' => array(
          'name' => 'street_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Name') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'export' => true,
          'where' => 'civicrm_address.street_name',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'street_type' => array(
          'name' => 'street_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Type') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
        ) ,
        'street_number_postdirectional' => array(
          'name' => 'street_number_postdirectional',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Number Postdirectional') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
        ) ,
        'street_unit' => array(
          'name' => 'street_unit',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Street Unit') ,
          'maxlength' => 16,
          'size' => CRM_Utils_Type::TWELVE,
          'export' => true,
          'where' => 'civicrm_address.street_unit',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'supplemental_address_1' => array(
          'name' => 'supplemental_address_1',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Supplemental Address 1') ,
          'maxlength' => 96,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_address.supplemental_address_1',
          'headerPattern' => '/(supplemental(\s)?)?address(\s\d+)?/i',
          'dataPattern' => '/unit|ap(ar)?t(ment)?\s(\d|\w)+/i',
          'export' => true,
        ) ,
        'supplemental_address_2' => array(
          'name' => 'supplemental_address_2',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Supplemental Address 2') ,
          'maxlength' => 96,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_address.supplemental_address_2',
          'headerPattern' => '/(supplemental(\s)?)?address(\s\d+)?/i',
          'dataPattern' => '/unit|ap(ar)?t(ment)?\s(\d|\w)+/i',
          'export' => true,
        ) ,
        'supplemental_address_3' => array(
          'name' => 'supplemental_address_3',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Supplemental Address 3') ,
          'maxlength' => 96,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'city' => array(
          'name' => 'city',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('City') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_address.city',
          'headerPattern' => '/city/i',
          'dataPattern' => '/^[A-Za-z]+(\.?)(\s?[A-Za-z]+){0,2}$/',
          'export' => true,
        ) ,
        'county_id' => array(
          'name' => 'county_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_County',
        ) ,
        'state_province_id' => array(
          'name' => 'state_province_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_StateProvince',
        ) ,
        'postal_code_suffix' => array(
          'name' => 'postal_code_suffix',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Postal Code Suffix') ,
          'maxlength' => 12,
          'size' => CRM_Utils_Type::TWELVE,
          'import' => true,
          'where' => 'civicrm_address.postal_code_suffix',
          'headerPattern' => '/p(ostal)\sc(ode)\ss(uffix)/i',
          'dataPattern' => '/\d?\d{4}(-\d{4})?/',
          'export' => true,
        ) ,
        'postal_code' => array(
          'name' => 'postal_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Postal Code') ,
          'maxlength' => 12,
          'size' => CRM_Utils_Type::TWELVE,
          'import' => true,
          'where' => 'civicrm_address.postal_code',
          'headerPattern' => '/postal|zip/i',
          'dataPattern' => '/\d?\d{4}(-\d{4})?/',
          'export' => true,
        ) ,
        'usps_adc' => array(
          'name' => 'usps_adc',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Usps Adc') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'country_id' => array(
          'name' => 'country_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_Country',
        ) ,
        'geo_code_1' => array(
          'name' => 'geo_code_1',
          'type' => CRM_Utils_Type::T_FLOAT,
          'title' => ts('Geo Code 1') ,
          'import' => true,
          'where' => 'civicrm_address.geo_code_1',
          'headerPattern' => '/geo/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'geo_code_2' => array(
          'name' => 'geo_code_2',
          'type' => CRM_Utils_Type::T_FLOAT,
          'title' => ts('Geo Code 2') ,
          'import' => true,
          'where' => 'civicrm_address.geo_code_2',
          'headerPattern' => '/geo/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'timezone' => array(
          'name' => 'timezone',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Timezone') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
        ) ,
        'address_name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Address Name') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_address.name',
          'headerPattern' => '/^location|(l(ocation\s)?name)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'master_id' => array(
          'name' => 'master_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Master Address ID') ,
          'import' => true,
          'where' => 'civicrm_address.master_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'FKClassName' => 'CRM_Core_DAO_Address',
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
      self::$_import = array();
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['address'] = &$fields[$name];
          } else {
            self::$_import[$name] = &$fields[$name];
          }
        }
      }
      self::$_import = array_merge(self::$_import, CRM_Core_DAO_County::import(true));
      self::$_import = array_merge(self::$_import, CRM_Core_DAO_StateProvince::import(true));
      self::$_import = array_merge(self::$_import, CRM_Core_DAO_Country::import(true));
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
            self::$_export['address'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
      self::$_export = array_merge(self::$_export, CRM_Core_DAO_County::export(true));
      self::$_export = array_merge(self::$_export, CRM_Core_DAO_StateProvince::export(true));
      self::$_export = array_merge(self::$_export, CRM_Core_DAO_Country::export(true));
    }
    return self::$_export;
  }
}
