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
class CRM_Pledge_DAO_Pledge extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_pledge';
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
   * Pledge ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Foreign key to civicrm_contact.id .
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * FK to Contribution Type. This is propagated to contribution record when pledge payments are made.
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
   * Total pledged amount.
   *
   * @var float
   */
  public $amount;
  /**
   * Original amount for each of the installments.
   *
   * @var float
   */
  public $original_installment_amount;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * Time units for recurrence of pledge payments.
   *
   * @var enum('day', 'week', 'month', 'year')
   */
  public $frequency_unit;
  /**
   * Number of time units for recurrence of pledge payments.
   *
   * @var int unsigned
   */
  public $frequency_interval;
  /**
   * Day in the period when the pledge payment is due e.g. 1st of month, 15th etc. Use this to set the scheduled dates for pledge payments.
   *
   * @var int unsigned
   */
  public $frequency_day;
  /**
   * Total number of payments to be made.
   *
   * @var int unsigned
   */
  public $installments;
  /**
   * The date the first scheduled pledge occurs.
   *
   * @var datetime
   */
  public $start_date;
  /**
   * When this pledge record was created.
   *
   * @var datetime
   */
  public $create_date;
  /**
   * When a pledge acknowledgement message was sent to the contributor.
   *
   * @var datetime
   */
  public $acknowledge_date;
  /**
   * Last updated date for this pledge record.
   *
   * @var datetime
   */
  public $modified_date;
  /**
   * Date this pledge was cancelled by contributor.
   *
   * @var datetime
   */
  public $cancel_date;
  /**
   * Date this pledge finished successfully (total pledge payments equal to or greater than pledged amount).
   *
   * @var datetime
   */
  public $end_date;
  /**
   * FK to contact ID. Used when pledge is made in honor of another contact. This is propagated to contribution records when pledge payments are made.
   *
   * @var int unsigned
   */
  public $honor_contact_id;
  /**
   * Implicit FK to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $honor_type_id;
  /**
   * The maximum number of payment reminders to send for any given payment.
   *
   * @var int unsigned
   */
  public $max_reminders;
  /**
   * Send initial reminder this many days prior to the payment due date.
   *
   * @var int unsigned
   */
  public $initial_reminder_day;
  /**
   * Send additional reminder this many days after last one sent, up to maximum number of reminders.
   *
   * @var int unsigned
   */
  public $additional_reminder_day;
  /**
   * Implicit foreign key to civicrm_option_values in the contribution_status option group.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   *
   * @var boolean
   */
  public $is_test;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_pledge
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
        'honor_contact_id' => 'civicrm_contact:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'honor_contact_id', 'civicrm_contact', 'id');
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
        'pledge_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Pledge ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_pledge.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'pledge_contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_pledge.contact_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'contribution_type_id' => array(
          'name' => 'contribution_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'export' => false,
          'where' => 'civicrm_pledge.contribution_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Contribute_DAO_ContributionType',
        ) ,
        'contribution_page_id' => array(
          'name' => 'contribution_page_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionPage',
        ) ,
        'pledge_amount' => array(
          'name' => 'amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Total Pledged') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_pledge.amount',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'pledge_original_installment_amount' => array(
          'name' => 'original_installment_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Original Installment Amount') ,
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
          'default' => '',
        ) ,
        'frequency_day' => array(
          'name' => 'frequency_day',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Frequency Day') ,
          'required' => true,
          'default' => '',
        ) ,
        'installments' => array(
          'name' => 'installments',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Installments') ,
          'default' => '',
        ) ,
        'start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Start Date') ,
          'required' => true,
        ) ,
        'pledge_create_date' => array(
          'name' => 'create_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Pledge Made') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_pledge.create_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'acknowledge_date' => array(
          'name' => 'acknowledge_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Acknowledge Date') ,
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
        'honor_contact_id' => array(
          'name' => 'honor_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'honor_type_id' => array(
          'name' => 'honor_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Honor Type') ,
        ) ,
        'max_reminders' => array(
          'name' => 'max_reminders',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Maximum Number of Reminders') ,
          'default' => '',
        ) ,
        'initial_reminder_day' => array(
          'name' => 'initial_reminder_day',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Initial Reminder Day') ,
          'default' => '',
        ) ,
        'additional_reminder_day' => array(
          'name' => 'additional_reminder_day',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Additional Reminder Days') ,
          'default' => '',
        ) ,
        'pledge_status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Pledge Status Id') ,
          'import' => true,
          'where' => 'civicrm_pledge.status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'pledge_is_test' => array(
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Test') ,
          'import' => true,
          'where' => 'civicrm_pledge.is_test',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
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
            self::$_import['pledge'] = &$fields[$name];
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
            self::$_export['pledge'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_pledge table
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
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_pledge
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Pledge_DAO_Pledge::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Pledge_DAO_Pledge::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
