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
class CRM_Contribute_DAO_LinePay extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_linepay';
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
   * LinePay ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * trxn_id from contribution
   *
   * @var string
   */
  public $trxn_id;
  /**
   * transaction id from linepay
   *
   * @var string
   */
  public $transaction_id;
  /**
   * response of /payments
   *
   * @var text
   */
  public $query;
  /**
   * response of /payments/request
   *
   * @var text
   */
  public $request;
  /**
   * response of /payments/{transactionId}/confirm
   *
   * @var text
   */
  public $confirm;
  /**
   * response of /payments/{transactionId}/refund
   *
   * @var text
   */
  public $refund;
  /**
   * response of /payments/authorization
   *
   * @var text
   */
  public $authorization;
  /**
   * response of /payments/authorizations/{transactionId}/capture
   *
   * @var text
   */
  public $capture;
  /**
   * response of /payments/authorizations/{transactionId}/void
   *
   * @var text
   */
  public $void;
  /**
   * response of /payments/preapprovedPay/{regKey}/payment
   *
   * @var text
   */
  public $recurring_payment;
  /**
   * response of /payments/preapprovedPay/{regKey}/check
   *
   * @var text
   */
  public $recurring_check;
  /**
   * response of /payments/preapprovedPay/{regKey}/expire
   *
   * @var text
   */
  public $recurring_expire;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_linepay
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
  function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'linepay_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('LinePay ID') ,
          'required' => true,
        ) ,
        'contribution_trxn_id' => array(
          'name' => 'trxn_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contribution Trxn ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'transaction_id' => array(
          'name' => 'transaction_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Transaction ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'query' => array(
          'name' => 'query',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Query') ,
          'default' => 'UL',
        ) ,
        'request' => array(
          'name' => 'request',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Request') ,
          'default' => 'UL',
        ) ,
        'confirm' => array(
          'name' => 'confirm',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Confirm') ,
          'default' => 'UL',
        ) ,
        'refund' => array(
          'name' => 'refund',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Refund') ,
          'default' => 'UL',
        ) ,
        'authorization' => array(
          'name' => 'authorization',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Authorization') ,
          'default' => 'UL',
        ) ,
        'capture' => array(
          'name' => 'capture',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Capture') ,
          'default' => 'UL',
        ) ,
        'void' => array(
          'name' => 'void',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Void') ,
          'default' => 'UL',
        ) ,
        'recurring_payment' => array(
          'name' => 'recurring_payment',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Recurring Payment') ,
          'default' => 'UL',
        ) ,
        'recurring_check' => array(
          'name' => 'recurring_check',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Recurring Check') ,
          'default' => 'UL',
        ) ,
        'recurring_expire' => array(
          'name' => 'recurring_expire',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Recurring Expire') ,
          'default' => 'UL',
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
  function getTableName()
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
  function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = & self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['contribution_linepay'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
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
  function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = & self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['contribution_linepay'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
