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
class CRM_Contribute_DAO_ContributionType extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_type';
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
   * Contribution Type ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Contribution Type Name.
   *
   * @var string
   */
  public $name;
  /**
   * Optional value for mapping contributions to accounting system codes for each type/category of contribution.
   *
   * @var string
   */
  public $accounting_code;
  /**
   * Contribution Type Description.
   *
   * @var string
   */
  public $description;
  /**
   * What the tax rate?
   *
   * @var int unsigned
   */
  public $tax_rate;
  /**
   * Is this contribution type a tax-receipt? If true, you will have tax receipt interface in contribution.
   *
   * @var boolean
   */
  public $is_taxreceipt;
  /**
   * Is this contribution type tax-deductible? If true, contributions of this type may be fully OR partially deductible - non-deductible amount is stored in the Contribution record.
   *
   * @var boolean
   */
  public $is_deductible;
  /**
   * Is this a predefined system object?
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * Is this property active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_type
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
        'contribution_type' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contribution Type') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_contribution_type.name',
          'headerPattern' => '/(contrib(ution)?)?type/i',
          'dataPattern' => '/donation|member|campaign/i',
          'export' => true,
          'usage' => 'System',
        ) ,
        'accounting_code' => array(
          'name' => 'accounting_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Accounting Code') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'export' => true,
          'where' => 'civicrm_contribution_type.accounting_code',
          'headerPattern' => '',
          'dataPattern' => '',
          'usage' => 'System',
        ) ,
        'description' => array(
          'name' => 'description',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Description') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'tax_rate' => array(
          'name' => 'tax_rate',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Tax Rate') ,
        ) ,
        'is_taxreceipt' => array(
          'name' => 'is_taxreceipt',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_deductible' => array(
          'name' => 'is_deductible',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
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
            self::$_import['contribution_type'] = &$fields[$name];
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
            self::$_export['contribution_type'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
