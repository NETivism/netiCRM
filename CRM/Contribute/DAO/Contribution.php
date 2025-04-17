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
class CRM_Contribute_DAO_Contribution extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution';
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
   * Unique Contribution ID
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
   * FK to Contribution Type
   *
   * @var int unsigned
   */
  public $contribution_type_id;
  /**
   * The Contribution Page which triggered this contribution
   *
   * @var int unsigned
   */
  public $contribution_page_id;
  /**
   * FK to Payment Processor
   *
   * @var int unsigned
   */
  public $payment_processor_id;
  /**
   * FK to Payment Instrument
   *
   * @var int unsigned
   */
  public $payment_instrument_id;
  /**
   * when was contribution submitted
   *
   * @var datetime
   */
  public $created_date;
  /**
   * when was gift received(or got authorized by credit card)
   *
   * @var datetime
   */
  public $receive_date;
  /**
   * Portion of total amount which is NOT tax deductible.
   *
   * @var float
   */
  public $non_deductible_amount;
  /**
   * Total amount of this contribution.
   *
   * @var float
   */
  public $total_amount;
  /**
   * Payment processor fee when available.
   *
   * @var float
   */
  public $fee_amount;
  /**
   * net amount + fee amount = total amount
   *
   * @var float
   */
  public $net_amount;
  /**
   * unique transaction id by this application
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
   * 3 character string, value from config setting.
   *
   * @var string
   */
  public $currency;
  /**
   * when was gift cancelled
   *
   * @var datetime
   */
  public $cancel_date;
  /**
   * Reason that gift was cancelled
   *
   * @var text
   */
  public $cancel_reason;
  /**
   * Record date after (email)receipt sent.
   *
   * @var datetime
   */
  public $receipt_date;
  /**
   * when (if) was donor thanked
   *
   * @var datetime
   */
  public $thankyou_date;
  /**
   * Origin of this Contribution.
   *
   * @var string
   */
  public $source;
  /**
   * Amount level / label that donor choose in contribution page.
   *
   * @var text
   */
  public $amount_level;
  /**
   * FK to Contribution Recurring. If this set, this contribution is belong to a recurring order.
   *
   * @var int unsigned
   */
  public $contribution_recur_id;
  /**
   * FK to contact ID. If this set, contribution is honor of other contact.
   *
   * @var int unsigned
   */
  public $honor_contact_id;
  /**
   * Mark 1 when this order is a testing contribution.
   *
   * @var boolean
   */
  public $is_test;
  /**
   * Mark 1 when this order is not paid by real-time payment instrument. eg. ATM is not real-time.
   *
   * @var boolean
   */
  public $is_pay_later;
  /**
   * 1=completed, 2=pending, 3=cancel, 4=failed
   *
   * @var int unsigned
   */
  public $contribution_status_id;
  /**
   * Implicit FK to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $honor_type_id;
  /**
   * Conditional foreign key to civicrm_address.id. We insert an address record for each contribution when we have associated billing name and address data.
   *
   * @var int unsigned
   */
  public $address_id;
  /**
   *
   * @var string
   */
  public $check_number;
  /**
   *
   * @var string
   */
  public $receipt_id;
  /**
   * when is payment expiration
   *
   * @var datetime
   */
  public $expire_date;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution
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
        'contribution_type_id' => 'civicrm_contribution_type:id',
        'contribution_page_id' => 'civicrm_contribution_page:id',
        'payment_processor_id' => 'civicrm_payment_processor:id',
        'contribution_recur_id' => 'civicrm_contribution_recur:id',
        'honor_contact_id' => 'civicrm_contact:id',
        'address_id' => 'civicrm_address:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_type_id', 'civicrm_contribution_type', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_page_id', 'civicrm_contribution_page', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'payment_processor_id', 'civicrm_payment_processor', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_recur_id', 'civicrm_contribution_recur', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'honor_contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'address_id', 'civicrm_address', 'id');
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
        'contribution_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribution ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_contribution.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'contribution_contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_contribution.contact_id',
          'headerPattern' => '/contact(.?id)?/i',
          'dataPattern' => '/^\d+$/',
          'export' => true,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'usage' => 'System',
        ) ,
        'contribution_type_id' => array(
          'name' => 'contribution_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'export' => false,
          'where' => 'civicrm_contribution.contribution_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Contribute_DAO_ContributionType',
          'usage' => 'System',
        ) ,
        'contribution_page_id' => array(
          'name' => 'contribution_page_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribution Page ID') ,
          'import' => true,
          'where' => 'civicrm_contribution.contribution_page_id',
          'headerPattern' => '',
          'dataPattern' => '/^\d+$/',
          'export' => true,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionPage',
          'usage' => 'System',
        ) ,
        'payment_processor_id' => array(
          'name' => 'payment_processor_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Payment Processor') ,
          'export' => true,
          'where' => 'civicrm_contribution.payment_processor_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Core_DAO_PaymentProcessor',
          'usage' => 'System',
        ) ,
        'payment_instrument_id' => array(
          'name' => 'payment_instrument_id',
          'type' => CRM_Utils_Type::T_INT,
          'usage' => 'System',
        ) ,
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Contribution Created Date') ,
          'import' => false,
          'where' => 'civicrm_contribution.created_date',
          'headerPattern' => '/created(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'receive_date' => array(
          'name' => 'receive_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Receive Date') ,
          'import' => true,
          'where' => 'civicrm_contribution.receive_date',
          'headerPattern' => '/receive(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'non_deductible_amount' => array(
          'name' => 'non_deductible_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Non-deductible Amount') ,
          'import' => true,
          'where' => 'civicrm_contribution.non_deductible_amount',
          'headerPattern' => '/non?.?deduct/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'total_amount' => array(
          'name' => 'total_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Total Amount') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_contribution.total_amount',
          'headerPattern' => '/^total|(.?^am(ou)?nt)/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'fee_amount' => array(
          'name' => 'fee_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Transaction Fee Amount') ,
          'import' => true,
          'where' => 'civicrm_contribution.fee_amount',
          'headerPattern' => '/fee(.?am(ou)?nt)?/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'net_amount' => array(
          'name' => 'net_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Net Amount') ,
          'import' => true,
          'where' => 'civicrm_contribution.net_amount',
          'headerPattern' => '/net(.?am(ou)?nt)?/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'trxn_id' => array(
          'name' => 'trxn_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Transaction ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contribution.trxn_id',
          'headerPattern' => '/tr(ansactio|x)n(.?id)?/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'invoice_id' => array(
          'name' => 'invoice_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Invoice ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contribution.invoice_id',
          'headerPattern' => '/invoice(.?id)?/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'currency' => array(
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'import' => true,
          'where' => 'civicrm_contribution.currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/i',
          'export' => true,
          'default' => 'UL',
          'usage' => 'System',
        ) ,
        'cancel_date' => array(
          'name' => 'cancel_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Cancel Date') ,
          'import' => true,
          'where' => 'civicrm_contribution.cancel_date',
          'headerPattern' => '/cancel(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'cancel_reason' => array(
          'name' => 'cancel_reason',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Cancel Reason') ,
          'import' => true,
          'where' => 'civicrm_contribution.cancel_reason',
          'headerPattern' => '/(cancel.?)?reason/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'receipt_date' => array(
          'name' => 'receipt_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Receipt Date') ,
          'import' => true,
          'where' => 'civicrm_contribution.receipt_date',
          'headerPattern' => '/receipt(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'thankyou_date' => array(
          'name' => 'thankyou_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Thank-you Date') ,
          'import' => true,
          'where' => 'civicrm_contribution.thankyou_date',
          'headerPattern' => '/thank(s|(.?you))?(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'contribution_source' => array(
          'name' => 'source',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contribution Source') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contribution.source',
          'headerPattern' => '/source/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'amount_level' => array(
          'name' => 'amount_level',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Amount Label') ,
          'import' => true,
          'where' => 'civicrm_contribution.amount_level',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'contribution_recur_id' => array(
          'name' => 'contribution_recur_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionRecur',
        ) ,
        'honor_contact_id' => array(
          'name' => 'honor_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'usage' => 'System',
        ) ,
        'is_test' => array(
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Test') ,
          'import' => true,
          'where' => 'civicrm_contribution.is_test',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'is_pay_later' => array(
          'name' => 'is_pay_later',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Pay Later') ,
          'import' => true,
          'where' => 'civicrm_contribution.is_pay_later',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'contribution_status_id' => array(
          'name' => 'contribution_status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribution Status Id') ,
          'import' => true,
          'where' => 'civicrm_contribution.contribution_status_id',
          'headerPattern' => '/status/i',
          'dataPattern' => '',
          'export' => false,
          'default' => '',
          'usage' => 'System',
        ) ,
        'honor_type_id' => array(
          'name' => 'honor_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Honor Type') ,
          'usage' => 'System',
        ) ,
        'address_id' => array(
          'name' => 'address_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_Address',
          'usage' => 'System',
        ) ,
        'check_number' => array(
          'name' => 'check_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Check Number') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contribution.check_number',
          'headerPattern' => '/check(.?number)?/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'receipt_id' => array(
          'name' => 'receipt_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Receipt ID') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contribution.receipt_id',
          'headerPattern' => '/receipt(.?number)?/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'expire_date' => array(
          'name' => 'expire_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Expire Date') ,
          'import' => true,
          'where' => 'civicrm_contribution.expire_date',
          'headerPattern' => '/expire(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'usage' => 'System',
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
            self::$_import['contribution'] = &$fields[$name];
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
            self::$_export['contribution'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
