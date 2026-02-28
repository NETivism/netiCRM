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
class CRM_Event_DAO_Participant extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_participant';
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
   * Participant Id
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
   * FK to Event ID
   *
   * @var int unsigned
   */
  public $event_id;
  /**
   * Participant status ID. FK to civicrm_participant_status_type. Default of 1 should map to status = Registered.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Participant role ID. Implicit FK to civicrm_option_value where option_group = participant_role.
   *
   * @var string
   */
  public $role_id;
  /**
   * When did contact register for event?
   *
   * @var datetime
   */
  public $register_date;
  /**
   * Source of this event registration.
   *
   * @var string
   */
  public $source;
  /**
   * Populate with the label (text) associated with a fee level for paid events with multiple levels. Note that we store the label value and not the key
   *
   * @var text
   */
  public $fee_level;
  /**
   *
   * @var boolean
   */
  public $is_test;
  /**
   *
   * @var boolean
   */
  public $is_pay_later;
  /**
   * actual processor fee if known - may be 0.
   *
   * @var float
   */
  public $fee_amount;
  /**
   * FK to Participant ID
   *
   * @var int unsigned
   */
  public $registered_by_id;
  /**
   * FK to Discount ID
   *
   * @var int unsigned
   */
  public $discount_id;
  /**
   * 3 character string, value derived from config setting.
   *
   * @var string
   */
  public $fee_currency;
  /**
  * class constructor
  *
  * @access public
  * @return civicrm_participant
  */
  public function __construct() {
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'contact_id' => 'civicrm_contact:id',
        'event_id' => 'civicrm_event:id',
        'status_id' => 'civicrm_participant_status_type:id',
        'registered_by_id' => 'civicrm_participant:id',
        'discount_id' => 'civicrm_discount:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'event_id', 'civicrm_event', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'status_id', 'civicrm_participant_status_type', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'registered_by_id', 'civicrm_participant', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'discount_id', 'civicrm_discount', 'id');
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
  * returns all the column names of this table
  *
  * @access public
  * @return array
  */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'participant_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Participant ID') ,
          'required' => TRUE,
               'import' => TRUE,
          'where' => 'civicrm_participant.id',
          'headerPattern' => '/(^(participant(.)?)?id$)/i',
          'dataPattern' => '',
           'export' => TRUE,
               'usage' => 'System',
       ] ,
        'participant_contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.contact_id',
          'headerPattern' => '/contact(.?id)?/i',
          'dataPattern' => '',
           'export' => TRUE,
              'FKClassName' => 'CRM_Contact_DAO_Contact',
           'usage' => 'System',
       ] ,
        'event_id' => [
          'name' => 'event_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Event') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.event_id',
          'headerPattern' => '/event id$/i',
          'dataPattern' => '',
           'export' => TRUE,
              'FKClassName' => 'CRM_Event_DAO_Event',
           'usage' => 'System',
       ] ,
        'participant_status_id' => [
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Participant Status Id') ,
          'required' => TRUE,
               'import' => TRUE,
          'where' => 'civicrm_participant.status_id',
          'headerPattern' => '/(participant.)?(status)$/i',
          'dataPattern' => '',
           'export' => FALSE,
            'default' => '',
            'FKClassName' => 'CRM_Event_DAO_ParticipantStatusType',
           'usage' => 'System',
       ] ,
        'participant_role_id' => [
          'name' => 'role_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Participant Role Id') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
             'import' => TRUE,
          'where' => 'civicrm_participant.role_id',
          'headerPattern' => '/(participant.)?(role)$/i',
          'dataPattern' => '',
           'export' => FALSE,
            'default' => 'UL',
             'usage' => 'System',
       ] ,
        'participant_register_date' => [
          'name' => 'register_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Register date') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.register_date',
          'headerPattern' => '/^(r(egister\s)?date)$/i',
          'dataPattern' => '',
           'export' => TRUE,
               'usage' => 'System',
       ] ,
        'participant_source' => [
          'name' => 'source',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Participant Source') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
             'import' => TRUE,
          'where' => 'civicrm_participant.source',
          'headerPattern' => '/(participant.)?(source)$/i',
          'dataPattern' => '',
           'export' => TRUE,
            ] ,
        'participant_fee_level' => [
          'name' => 'fee_level',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Fee level') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.fee_level',
          'headerPattern' => '/^(f(ee\s)?level)$/i',
          'dataPattern' => '',
           'export' => TRUE,
            ] ,
        'participant_is_test' => [
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Test') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.is_test',
          'headerPattern' => '',
          'dataPattern' => '',
           'export' => TRUE,
               'usage' => 'System',
       ] ,
        'participant_is_pay_later' => [
          'name' => 'is_pay_later',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Pay Later') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.is_pay_later',
          'headerPattern' => '/(is.)?(pay(.)?later)$/i',
          'dataPattern' => '',
           'export' => TRUE,
               'usage' => 'System',
       ] ,
        'participant_fee_amount' => [
          'name' => 'fee_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Fee Amount') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.fee_amount',
          'headerPattern' => '/fee(.?am(ou)?nt)?/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
           'export' => TRUE,
               'usage' => 'System',
       ] ,
        'participant_registered_by_id' => [
          'name' => 'registered_by_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Registered By ID') ,
               'import' => TRUE,
          'where' => 'civicrm_participant.registered_by_id',
          'headerPattern' => '',
          'dataPattern' => '',
           'export' => TRUE,
            'default' => 'UL',
            'FKClassName' => 'CRM_Event_DAO_Participant',
           'usage' => 'System',
       ] ,
        'participant_discount_id' => [
          'name' => 'discount_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Discount ID') ,
                  'default' => 'UL',
            'FKClassName' => 'CRM_Core_DAO_Discount',
        ] ,
        'participant_fee_currency' => [
          'name' => 'fee_currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Fee Currency') ,
           'maxlength' => 3,
           'size' => CRM_Utils_Type::FOUR,
             'import' => TRUE,
          'where' => 'civicrm_participant.fee_currency',
          'headerPattern' => '/(fee)?.?cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/i',
           'export' => TRUE,
            'default' => 'UL',
             'usage' => 'System',
       ] ,
      ];
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  public function getLog() {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   */
  public static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['participant'] = &$fields[$name];
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
   * @access public
   * return array
   */
  public static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['participant'] = &$fields[$name];
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
