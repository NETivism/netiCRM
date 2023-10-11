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
class CRM_Contribute_DAO_ContributionRecur extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_recur';
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
   * Unique Contribution Recur ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Foreign key to civicrm_contact.id
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Amount to be contributed or charged each recurrence.
   *
   * @var float
   */
  public $amount;
  /**
   * 3 character string, value from config setting.
   *
   * @var string
   */
  public $currency;
  /**
   * Time units for recurrence of payment.
   *
   * @var enum('day', 'week', 'month', 'year')
   */
  public $frequency_unit;
  /**
   * Number of time units for recurrence of payment.
   *
   * @var int unsigned
   */
  public $frequency_interval;
  /**
   * Total number of payments to be made. Set this to 0 if this is an open-ended commitment.
   *
   * @var int unsigned
   */
  public $installments;
  /**
   * The date the first scheduled recurring contribution occurs.
   *
   * @var datetime
   */
  public $start_date;
  /**
   * When this recurring contribution record was created.
   *
   * @var datetime
   */
  public $create_date;
  /**
   * Last updated date for this record. mostly the last time a payment was received
   *
   * @var datetime
   */
  public $modified_date;
  /**
   * Date this recurring contribution was cancelled by contributor- if we can get access to it
   *
   * @var datetime
   */
  public $cancel_date;
  /**
   * Date this recurring contribution finished successfully
   *
   * @var datetime
   */
  public $end_date;
  /**
   * FK to Payment Processor
   *
   * @var int unsigned
   */
  public $processor_id;
  /**
   * an external unique identifier for this recurring payment order provide by payment method
   *
   * @var string
   */
  public $external_id;
  /**
   * unique transaction id or this recurring defined by this application
   *
   * @var string
   */
  public $trxn_id;
  /**
   * unique invoice id, system generated or passed in
   *
   * @var string
   */
  public $invoice_id;
  /**
   * 1=completed, 2=pending, 3=cancel, 4=failed, 5=in progress, 6=overdue, 7=suspend
   *
   * @var int unsigned
   */
  public $contribution_status_id;
  /**
   * Mark 1 when this order is an testing order.
   *
   * @var boolean
   */
  public $is_test;
  /**
   * Day in the period when the payment should be charged e.g. 1st of month, 15th etc.
   *
   * @var int unsigned
   */
  public $cycle_day;
  /**
   * Next payment day for display or execution(if needed).
   *
   * @var datetime
   */
  public $next_sched_contribution;
  /**
   * Number of failed charge attempts since last success.
   *
   * @var int unsigned
   */
  public $failure_count;
  /**
   * Record retried date after payment fail retry.
   *
   * @var datetime
   */
  public $failure_retry_date;
  /**
   * Auto renew card expiration date when available.
   *
   * @var boolean
   */
  public $auto_renew;
  /**
   * Last payment execution transaction date to prevent duplicate execution at the same frequency_unit.
   *
   * @var datetime
   */
  public $last_execute_date;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_recur
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
        'processor_id' => 'civicrm_payment_processor:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'processor_id', 'civicrm_payment_processor', 'id');
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
          'required' => true,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'amount' => array(
          'name' => 'amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Amount') ,
          'required' => true,
        ) ,
        'currency' => array(
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'default' => 'UL',
        ) ,
        'frequency_unit' => array(
          'name' => 'frequency_unit',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Frequency Unit') ,
          'default' => 'month',
          'enumValues' => 'day,week,month,year',
        ) ,
        'frequency_interval' => array(
          'name' => 'frequency_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Frequency Interval') ,
          'required' => true,
        ) ,
        'installments' => array(
          'name' => 'installments',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Installments') ,
        ) ,
        'start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Start Date') ,
          'required' => true,
        ) ,
        'create_date' => array(
          'name' => 'create_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Create Date') ,
          'required' => true,
        ) ,
        'modified_date' => array(
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Modified Date') ,
        ) ,
        'cancel_date' => array(
          'name' => 'cancel_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Cancel Date') ,
        ) ,
        'end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('End Date') ,
        ) ,
        'processor_id' => array(
          'name' => 'processor_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_PaymentProcessor',
        ) ,
        'external_id' => array(
          'name' => 'external_id',
          'type' => CRM_Utils_Type::T_STRING,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'trxn_id' => array(
          'name' => 'trxn_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Transaction ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'invoice_id' => array(
          'name' => 'invoice_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Invoice ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'contribution_status_id' => array(
          'name' => 'contribution_status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Recurring Contribution Status') ,
          'import' => true,
          'where' => 'civicrm_contribution_recur.contribution_status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'default' => '',
        ) ,
        'is_test' => array(
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Test') ,
          'import' => true,
          'where' => 'civicrm_contribution_recur.is_test',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'cycle_day' => array(
          'name' => 'cycle_day',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Cycle Day') ,
          'required' => true,
          'default' => '',
        ) ,
        'next_sched_contribution' => array(
          'name' => 'next_sched_contribution',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Next Sched Contribution') ,
        ) ,
        'failure_count' => array(
          'name' => 'failure_count',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Failure Count') ,
        ) ,
        'failure_retry_date' => array(
          'name' => 'failure_retry_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Failure Retry Date') ,
        ) ,
        'auto_renew' => array(
          'name' => 'auto_renew',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Auto Renew') ,
          'required' => true,
        ) ,
        'last_execute_date' => array(
          'name' => 'last_execute_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Last Execute Date') ,
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
            self::$_import['contribution_recur'] = &$fields[$name];
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
            self::$_export['contribution_recur'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_contribution_recur table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = array(
      'frequency_unit',
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
        'frequency_unit' => array(
          'day' => ts('day') ,
          'week' => ts('week') ,
          'month' => ts('month') ,
          'year' => ts('year') ,
        ) ,
      );
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_contribution_recur
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Contribute_DAO_ContributionRecur::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Contribute_DAO_ContributionRecur::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
