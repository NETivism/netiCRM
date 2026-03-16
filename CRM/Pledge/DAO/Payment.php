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

class CRM_Pledge_DAO_Payment extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_pledge_payment';
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
  public static $_log = TRUE;
  /**
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to Pledge table
   *
   * @var int unsigned
   */
  public $pledge_id;
  /**
   * FK to contribution table.
   *
   * @var int unsigned
   */
  public $contribution_id;
  /**
   * Pledged amount for this payment (the actual contribution amount might be different).
   *
   * @var float
   */
  public $scheduled_amount;
  /**
   * Actual amount that is paid as the Pledged installment amount.
   *
   * @var float
   */
  public $actual_amount;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * The date the pledge payment is supposed to happen.
   *
   * @var datetime
   */
  public $scheduled_date;
  /**
   * The date that the most recent payment reminder was sent.
   *
   * @var datetime
   */
  public $reminder_date;
  /**
   * The number of payment reminders sent.
   *
   * @var int unsigned
   */
  public $reminder_count;
  /**
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * class constructor
   *
   * @return civicrm_pledge_payment
   */
  public function __construct() {
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @return array
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'pledge_id' => 'civicrm_pledge:id',
        'contribution_id' => 'civicrm_contribution:id',
      ];
    }
    return self::$_links;
  }
  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'pledge_id', 'civicrm_pledge', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contribution_id', 'civicrm_contribution', 'id');
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'pledge_payment_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Payment ID') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'pledge_id' => [
          'name' => 'pledge_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'CRM_Pledge_DAO_Pledge',
        ],
        'contribution_id' => [
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contribute_DAO_Contribution',
        ],
        'pledge_payment_scheduled_amount' => [
          'name' => 'scheduled_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Scheduled Amount') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.scheduled_amount',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'pledge_payment_actual_amount' => [
          'name' => 'actual_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Actual Amount') ,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.actual_amount',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'currency' => [
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'default' => 'UL',
        ],
        'pledge_payment_scheduled_date' => [
          'name' => 'scheduled_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Scheduled Date') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.scheduled_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'pledge_payment_reminder_date' => [
          'name' => 'reminder_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Last Reminder') ,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.reminder_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'pledge_payment_reminder_count' => [
          'name' => 'reminder_count',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Reminders Sent') ,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.reminder_count',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'pledge_payment_status_id' => [
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Payment Status') ,
          'import' => TRUE,
          'where' => 'civicrm_pledge_payment.status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => FALSE,
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
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['pledge_payment'] = &$fields[$name];
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
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['pledge_payment'] = &$fields[$name];
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
