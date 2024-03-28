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
class CRM_Core_DAO_PaymentProcessor extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_payment_processor';
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
   * Payment Processor ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Which Domain is this match entry for
   *
   * @var int unsigned
   */
  public $domain_id;
  /**
   * Payment Processor Name.
   *
   * @var string
   */
  public $name;
  /**
   * Payment Processor Description.
   *
   * @var string
   */
  public $description;
  /**
   * Payment Processor Type.
   *
   * @var string
   */
  public $payment_processor_type;
  /**
   * Is this processor active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is this processor the default?
   *
   * @var boolean
   */
  public $is_default;
  /**
   * Is this processor for a test site?
   *
   * @var boolean
   */
  public $is_test;
  /**
   *
   * @var string
   */
  public $user_name;
  /**
   *
   * @var string
   */
  public $password;
  /**
   *
   * @var string
   */
  public $signature;
  /**
   *
   * @var string
   */
  public $url_site;
  /**
   *
   * @var string
   */
  public $url_api;
  /**
   *
   * @var string
   */
  public $url_recur;
  /**
   *
   * @var string
   */
  public $url_button;
  /**
   *
   * @var string
   */
  public $subject;
  /**
   *
   * @var string
   */
  public $class_name;
  /**
   * Billing Mode
   *
   * @var int unsigned
   */
  public $billing_mode;
  /**
   * Can process recurring contributions
   *
   * @var boolean
   */
  public $is_recur;
  /**
   * Payment Type: Credit or Debit
   *
   * @var int unsigned
   */
  public $payment_type;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_payment_processor
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
        'domain_id' => 'civicrm_domain:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'domain_id', 'civicrm_domain', 'id');
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
        'domain_id' => array(
          'name' => 'domain_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Core_DAO_Domain',
        ) ,
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Processor') ,
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
        'payment_processor_type' => array(
          'name' => 'payment_processor_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Processor Type') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_default' => array(
          'name' => 'is_default',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_test' => array(
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'user_name' => array(
          'name' => 'user_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('User Name') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'password' => array(
          'name' => 'password',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Password') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'signature' => array(
          'name' => 'signature',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Signature') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'url_site' => array(
          'name' => 'url_site',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Site URL') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'url_api' => array(
          'name' => 'url_api',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('API URL') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'url_recur' => array(
          'name' => 'url_recur',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Recurring Payments URL') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'url_button' => array(
          'name' => 'url_button',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Button URL') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'subject' => array(
          'name' => 'subject',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Subject') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'class_name' => array(
          'name' => 'class_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Suffix for PHP clas name implementation') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'billing_mode' => array(
          'name' => 'billing_mode',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Billing Mode') ,
          'required' => true,
        ) ,
        'is_recur' => array(
          'name' => 'is_recur',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'payment_type' => array(
          'name' => 'payment_type',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Payment Type') ,
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
            self::$_import['payment_processor'] = &$fields[$name];
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
            self::$_export['payment_processor'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
