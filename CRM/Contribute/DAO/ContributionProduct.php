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
class CRM_Contribute_DAO_ContributionProduct extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_product';
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
   *
   * @var int unsigned
   */
  public $id;
  /**
   *
   * @var int unsigned
   */
  public $product_id;
  /**
   *
   * @var int unsigned
   */
  public $contribution_id;
  /**
   * Option value selected if applicable - e.g. color, size etc.
   *
   * @var string
   */
  public $product_option;
  /**
   *
   * @var int
   */
  public $quantity;
  /**
   * Optional. Can be used to record the date this product was fulfilled or shipped.
   *
   * @var date
   */
  public $fulfilled_date;
  /**
   * Actual start date for a time-delimited premium (subscription, service or membership)
   *
   * @var date
   */
  public $start_date;
  /**
   * Actual end date for a time-delimited premium (subscription, service or membership)
   *
   * @var date
   */
  public $end_date;
  /**
   *
   * @var text
   */
  public $comment;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_product
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
        'contribution_id' => 'civicrm_contribution:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_id', 'civicrm_contribution', 'id');
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
        'product_id' => array(
          'name' => 'product_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'contribution_id' => array(
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Contribute_DAO_Contribution',
        ) ,
        'product_option' => array(
          'name' => 'product_option',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Product Option') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'export' => true,
          'where' => 'civicrm_contribution_product.product_option',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'quantity' => array(
          'name' => 'quantity',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Quantity') ,
          'export' => true,
          'where' => 'civicrm_contribution_product.quantity',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'fulfilled_date' => array(
          'name' => 'fulfilled_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Fulfilled Date') ,
          'export' => true,
          'where' => 'civicrm_contribution_product.fulfilled_date',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'contribution_start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Start Date') ,
          'export' => true,
          'where' => 'civicrm_contribution_product.start_date',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'contribution_end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('End Date') ,
          'export' => true,
          'where' => 'civicrm_contribution_product.end_date',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'comment' => array(
          'name' => 'comment',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Comment') ,
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
            self::$_import['contribution_product'] = &$fields[$name];
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
            self::$_export['contribution_product'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
