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

class CRM_Contribute_DAO_LinePay extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_contribution_linepay';
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
   * @return civicrm_contribution_linepay
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'linepay_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('LinePay ID') ,
          'required' => TRUE,
        ],
        'contribution_trxn_id' => [
          'name' => 'trxn_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contribution Trxn ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'transaction_id' => [
          'name' => 'transaction_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Transaction ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'query' => [
          'name' => 'query',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Query') ,
          'default' => 'UL',
        ],
        'request' => [
          'name' => 'request',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Request') ,
          'default' => 'UL',
        ],
        'confirm' => [
          'name' => 'confirm',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Confirm') ,
          'default' => 'UL',
        ],
        'refund' => [
          'name' => 'refund',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Refund') ,
          'default' => 'UL',
        ],
        'authorization' => [
          'name' => 'authorization',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Authorization') ,
          'default' => 'UL',
        ],
        'capture' => [
          'name' => 'capture',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Capture') ,
          'default' => 'UL',
        ],
        'void' => [
          'name' => 'void',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Void') ,
          'default' => 'UL',
        ],
        'recurring_payment' => [
          'name' => 'recurring_payment',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Recurring Payment') ,
          'default' => 'UL',
        ],
        'recurring_check' => [
          'name' => 'recurring_check',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Recurring Check') ,
          'default' => 'UL',
        ],
        'recurring_expire' => [
          'name' => 'recurring_expire',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Recurring Expire') ,
          'default' => 'UL',
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
            self::$_import['contribution_linepay'] = &$fields[$name];
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
            self::$_export['contribution_linepay'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
