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
class CRM_Contribute_DAO_ContributionPage extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_page';
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
   * Contribution Id
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Contribution Page title. For top of page display
   *
   * @var string
   */
  public $title;
  /**
   * Text and html allowed. Displayed below title.
   *
   * @var text
   */
  public $intro_text;
  /**
   * default Contribution type assigned to contributions submitted via this page, e.g. Contribution, Campaign Contribution
   *
   * @var int unsigned
   */
  public $contribution_type_id;
  /**
   * Payment Processors configured for this contribution Page
   *
   * @var string
   */
  public $payment_processor;
  /**
   * if true - processing logic must reject transaction at confirmation stage if pay method != credit card
   *
   * @var boolean
   */
  public $is_credit_card_only;
  /**
   * if true - allows real-time monetary transactions otherwise non-monetary transactions
   *
   * @var boolean
   */
  public $is_monetary;
  /**
   * 0 - disabled reucrring, 1 - enable both, 2 - enabled recur only
   *
   * @var int unsigned
   */
  public $is_recur;
  /**
   * Supported recurring frequency units.
   *
   * @var string
   */
  public $recur_frequency_unit;
  /**
   * if true - supports recurring intervals
   *
   * @var boolean
   */
  public $is_recur_interval;
  /**
   * if true - allows the user to send payment directly to the org later
   *
   * @var boolean
   */
  public $is_pay_later;
  /**
   * The text displayed to the user in the main form
   *
   * @var text
   */
  public $pay_later_text;
  /**
   * The receipt sent to the user instead of the normal receipt text
   *
   * @var text
   */
  public $pay_later_receipt;
  /**
   * if true, page will include an input text field where user can enter their own amount
   *
   * @var boolean
   */
  public $is_allow_other_amount;
  /**
   * FK to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $default_amount_id;
  /**
   * if other amounts allowed, user can configure minimum allowed.
   *
   * @var float
   */
  public $min_amount;
  /**
   * if other amounts allowed, user can configure maximum allowed.
   *
   * @var float
   */
  public $max_amount;
  /**
   * The target goal for this page, allows people to build a goal meter
   *
   * @var float
   */
  public $goal_amount;
  /**
   * The target recurring goal for this page, allows people to build a goal meter base on subscriptions
   *
   * @var int unsigned
   */
  public $goal_recurring;
  /**
   * Title for Thank-you page (header title tag, and display at the top of the page).
   *
   * @var string
   */
  public $thankyou_title;
  /**
   * text and html allowed. displayed above result on success page
   *
   * @var text
   */
  public $thankyou_text;
  /**
   * Text and html allowed. displayed at the bottom of the success page. Common usage is to include link(s) to other pages such as tell-a-friend, etc.
   *
   * @var text
   */
  public $thankyou_footer;
  /**
   * if true, signup is done on behalf of an organization
   *
   * @var boolean
   */
  public $is_for_organization;
  /**
   * This text field is shown when is_for_organization is checked. For example - I am contributing on behalf on an organization.
   *
   * @var text
   */
  public $for_organization;
  /**
   * if true, receipt is automatically emailed to contact on success
   *
   * @var boolean
   */
  public $is_email_receipt;
  /**
   * FROM email name used for receipts generated by contributions to this contribution page.
   *
   * @var string
   */
  public $receipt_from_name;
  /**
   * FROM email address used for receipts generated by contributions to this contribution page.
   *
   * @var string
   */
  public $receipt_from_email;
  /**
   * comma-separated list of email addresses to cc each time a receipt is sent
   *
   * @var string
   */
  public $cc_receipt;
  /**
   * comma-separated list of email addresses to bcc each time a receipt is sent
   *
   * @var string
   */
  public $bcc_receipt;
  /**
   * text to include above standard receipt info on receipt email. emails are text-only, so do not allow html for now
   *
   * @var text
   */
  public $receipt_text;
  /**
   * comma-separated list of email addresses to each time a recurring is failed
   *
   * @var string
   */
  public $recur_fail_notify;
  /**
   * Is this property active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is this page only for internal usage?
   *
   * @var boolean
   */
  public $is_internal;
  /**
   * Text and html allowed. Displayed at the bottom of the first page of the contribution wizard.
   *
   * @var text
   */
  public $footer_text;
  /**
   * Is this property active?
   *
   * @var boolean
   */
  public $amount_block_is_active;
  /**
   * Should this contribution have the honor  block enabled?
   *
   * @var boolean
   */
  public $honor_block_is_active;
  /**
   * Title for honor block.
   *
   * @var string
   */
  public $honor_block_title;
  /**
   * text for honor block.
   *
   * @var text
   */
  public $honor_block_text;
  /**
   * Date and time that this page starts.
   *
   * @var datetime
   */
  public $start_date;
  /**
   * Date and time that this page ends. May be NULL if no defined end date/time
   *
   * @var datetime
   */
  public $end_date;
  /**
   * FK to civicrm_contact, who created this contribution page
   *
   * @var int unsigned
   */
  public $created_id;
  /**
   * Date and time that contribution page was created.
   *
   * @var datetime
   */
  public $created_date;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * Use options for donor to select recurring installments.
   *
   * @var text
   */
  public $installments_option;
  /**
   * Background image url on contribution page of special style.
   *
   * @var string
   */
  public $background_URL;
  /**
   * Background image url on contribution page of special style in mobile.
   *
   * @var string
   */
  public $mobile_background_URL;
  /**
   * if true, it will automatically send mobile SMS message to successful donor.
   *
   * @var boolean
   */
  public $is_send_sms;
  /**
   * SMS message content.
   *
   * @var text
   */
  public $sms_text;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_page
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
        'contribution_type_id' => 'civicrm_contribution_type:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_type_id', 'civicrm_contribution_type', 'id');
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
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'title' => array(
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'intro_text' => array(
          'name' => 'intro_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Intro Text') ,
          'rows' => 6,
          'cols' => 50,
        ) ,
        'contribution_type_id' => array(
          'name' => 'contribution_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionType',
        ) ,
        'payment_processor' => array(
          'name' => 'payment_processor',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Processor') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_credit_card_only' => array(
          'name' => 'is_credit_card_only',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_monetary' => array(
          'name' => 'is_monetary',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'is_recur' => array(
          'name' => 'is_recur',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'recur_frequency_unit' => array(
          'name' => 'recur_frequency_unit',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Recur Frequency Unit') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_recur_interval' => array(
          'name' => 'is_recur_interval',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_pay_later' => array(
          'name' => 'is_pay_later',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'pay_later_text' => array(
          'name' => 'pay_later_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Pay Later Text') ,
        ) ,
        'pay_later_receipt' => array(
          'name' => 'pay_later_receipt',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Pay Later Receipt') ,
        ) ,
        'is_allow_other_amount' => array(
          'name' => 'is_allow_other_amount',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'default_amount_id' => array(
          'name' => 'default_amount_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'min_amount' => array(
          'name' => 'min_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Min Amount') ,
        ) ,
        'max_amount' => array(
          'name' => 'max_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Max Amount') ,
        ) ,
        'goal_amount' => array(
          'name' => 'goal_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Goal Amount') ,
        ) ,
        'goal_recurring' => array(
          'name' => 'goal_recurring',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Goal Recurring') ,
        ) ,
        'thankyou_title' => array(
          'name' => 'thankyou_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Thank-you Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'thankyou_text' => array(
          'name' => 'thankyou_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Thank-you Text') ,
          'rows' => 8,
          'cols' => 60,
        ) ,
        'thankyou_footer' => array(
          'name' => 'thankyou_footer',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Thank-you Footer') ,
          'rows' => 8,
          'cols' => 60,
        ) ,
        'is_for_organization' => array(
          'name' => 'is_for_organization',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'for_organization' => array(
          'name' => 'for_organization',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('On Behalf Of Organization') ,
          'rows' => 2,
          'cols' => 50,
        ) ,
        'is_email_receipt' => array(
          'name' => 'is_email_receipt',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'receipt_from_name' => array(
          'name' => 'receipt_from_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Notification From Name') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'receipt_from_email' => array(
          'name' => 'receipt_from_email',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Notification From Email') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'cc_receipt' => array(
          'name' => 'cc_receipt',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('CC Payment Notification to') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'bcc_receipt' => array(
          'name' => 'bcc_receipt',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('BCC Payment Notification to') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'receipt_text' => array(
          'name' => 'receipt_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Payment Notification Text') ,
          'rows' => 6,
          'cols' => 50,
        ) ,
        'recur_fail_notify' => array(
          'name' => 'recur_fail_notify',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Recurring Failed Notification to') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_internal' => array(
          'name' => 'is_internal',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'footer_text' => array(
          'name' => 'footer_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Footer Text') ,
          'rows' => 6,
          'cols' => 50,
        ) ,
        'amount_block_is_active' => array(
          'name' => 'amount_block_is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'honor_block_is_active' => array(
          'name' => 'honor_block_is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'honor_block_title' => array(
          'name' => 'honor_block_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Honor Block Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'honor_block_text' => array(
          'name' => 'honor_block_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Honor Block Text') ,
          'rows' => 2,
          'cols' => 50,
        ) ,
        'start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Contribution Page Start Date') ,
        ) ,
        'end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Contribution Page End Date') ,
        ) ,
        'created_id' => array(
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Contribution Page Created Date') ,
        ) ,
        'currency' => array(
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'import' => true,
          'where' => 'civicrm_contribution_page.currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/i',
          'export' => true,
          'default' => 'UL',
        ) ,
        'installments_option' => array(
          'name' => 'installments_option',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Installments Option') ,
          'rows' => 4,
          'cols' => 60,
        ) ,
        'background_URL' => array(
          'name' => 'background_URL',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Background Url') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'default' => 'UL',
        ) ,
        'mobile_background_URL' => array(
          'name' => 'mobile_background_URL',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Mobile Background Url') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'default' => 'UL',
        ) ,
        'is_send_sms' => array(
          'name' => 'is_send_sms',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'sms_text' => array(
          'name' => 'sms_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Sms Text') ,
          'rows' => 6,
          'cols' => 50,
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
            self::$_import['contribution_page'] = &$fields[$name];
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
            self::$_export['contribution_page'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
