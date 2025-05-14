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
class CRM_Contribute_DAO_TapPay extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_tappay';
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
   * TapPay ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Contribuution ID
   *
   * @var int unsigned
   */
  public $contribution_id;
  /**
   * Contribuution Recur ID
   *
   * @var int unsigned
   */
  public $contribution_recur_id;
  /**
   * Used in parameters of transact. Same as contribution trxn id.
   *
   * @var string
   */
  public $order_number;
  /**
   * Card Token
   *
   * @var string
   */
  public $card_token;
  /**
   * Card Key
   *
   * @var string
   */
  public $card_key;
  /**
   * Record Trade ID
   *
   * @var string
   */
  public $rec_trade_id;
  /**
   * The first 6 words of card number.
   *
   * @var string
   */
  public $bin_code;
  /**
   * The last four words of card number.
   *
   * @var string
   */
  public $last_four;
  /**
   * Credit card expiry date.
   *
   * @var date
   */
  public $expiry_date;
  /**
   * Newest return data.
   *
   * @var text
   */
  public $data;
  /**
   * Used in 3JTSP cards, correspond to auto_renew column in contribution_recur.
   *
   * @var string
   */
  public $token_status;
  /**
   * FK to civicrm_contact, who created this contribution
   *
   * @var int unsigned
   */
  public $created_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_tappay
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
        'contribution_recur_id' => 'civicrm_contribution_recur:id',
        'created_id' => 'civicrm_contact:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_recur_id', 'civicrm_contribution_recur', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'created_id', 'civicrm_contact', 'id');
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
        'tappay_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('TapPay ID') ,
          'required' => true,
        ) ,
        'contribution_id' => array(
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribuution ID') ,
          'default' => 'UL',
          'FKClassName' => 'CRM_Contribute_DAO_Contribution',
        ) ,
        'contribution_recur_id' => array(
          'name' => 'contribution_recur_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribuution Recur ID') ,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionRecur',
        ) ,
        'order_number' => array(
          'name' => 'order_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contribution Trxn ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'card_token' => array(
          'name' => 'card_token',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Card Token') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'card_key' => array(
          'name' => 'card_key',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Card Key') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'rec_trade_id' => array(
          'name' => 'rec_trade_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Record Trade ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'bin_code' => array(
          'name' => 'bin_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bin Code') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'last_four' => array(
          'name' => 'last_four',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Last Four') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'expiry_date' => array(
          'name' => 'expiry_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Expiry Date') ,
        ) ,
        'data' => array(
          'name' => 'data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Data') ,
        ) ,
        'token_status' => array(
          'name' => 'token_status',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Token Status') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'created_id' => array(
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
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
            self::$_import['contribution_tappay'] = &$fields[$name];
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
            self::$_export['contribution_tappay'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
