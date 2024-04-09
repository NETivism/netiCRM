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
class CRM_Member_DAO_Membership extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_membership';
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
   * Membership Id
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
   * FK to Membership Type
   *
   * @var int unsigned
   */
  public $membership_type_id;
  /**
   * Beginning of initial membership period (member since...).
   *
   * @var date
   */
  public $join_date;
  /**
   * Beginning of current uninterrupted membership period.
   *
   * @var date
   */
  public $start_date;
  /**
   * Current membership period expire date.
   *
   * @var date
   */
  public $end_date;
  /**
   *
   * @var string
   */
  public $source;
  /**
   * FK to Membership Status
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Admin users may set a manual status which overrides the calculated status. When this flag is true, automated status update scripts should NOT modify status for the record.
   *
   * @var boolean
   */
  public $is_override;
  /**
   * When should a reminder be sent.
   *
   * @var date
   */
  public $reminder_date;
  /**
   * Optional FK to Parent Membership.
   *
   * @var int unsigned
   */
  public $owner_membership_id;
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
   * class constructor
   *
   * @access public
   * @return civicrm_membership
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
        'membership_type_id' => 'civicrm_membership_type:id',
        'status_id' => 'civicrm_membership_status:id',
        'owner_membership_id' => 'civicrm_membership:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'membership_type_id', 'civicrm_membership_type', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'status_id', 'civicrm_membership_status', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'owner_membership_id', 'civicrm_membership', 'id');
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
        'membership_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Membership ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_membership.id',
          'headerPattern' => '/^(m(embership\s)?id)$/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'membership_contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_membership.contact_id',
          'headerPattern' => '/contact(.?id)?/i',
          'dataPattern' => '/^\d+$/',
          'export' => true,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'usage' => 'System',
        ) ,
        'membership_type_id' => array(
          'name' => 'membership_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Membership Type Id') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_membership.membership_type_id',
          'headerPattern' => '/^(m(embership\s)?type)$/i',
          'dataPattern' => '',
          'export' => false,
          'FKClassName' => 'CRM_Member_DAO_MembershipType',
          'usage' => 'System',
        ) ,
        'join_date' => array(
          'name' => 'join_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Member Since') ,
          'import' => true,
          'where' => 'civicrm_membership.join_date',
          'headerPattern' => '/^join|(j(oin\s)?date)$/i',
          'dataPattern' => '/\d{4}-?\d{2}-?\d{2}/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'membership_start_date' => array(
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Membership Start Date') ,
          'import' => true,
          'where' => 'civicrm_membership.start_date',
          'headerPattern' => '/(member(ship)?.)?start(s)?(.date$)?/i',
          'dataPattern' => '/\d{4}-?\d{2}-?\d{2}/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'membership_end_date' => array(
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Membership Expiration Date') ,
          'import' => true,
          'where' => 'civicrm_membership.end_date',
          'headerPattern' => '/(member(ship)?.)?end(s)?(.date$)?/i',
          'dataPattern' => '/\d{4}-?\d{2}-?\d{2}/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'membership_source' => array(
          'name' => 'source',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Source') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_membership.source',
          'headerPattern' => '/^(member(ship?))?source$/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Membership Status Id') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_membership.status_id',
          'headerPattern' => '/(member(ship|).)?(status)$/i',
          'dataPattern' => '',
          'export' => false,
          'FKClassName' => 'CRM_Member_DAO_MembershipStatus',
          'usage' => 'System',
        ) ,
        'is_override' => array(
          'name' => 'is_override',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Status Override') ,
          'import' => true,
          'where' => 'civicrm_membership.is_override',
          'headerPattern' => '/override$/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'reminder_date' => array(
          'name' => 'reminder_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Reminder Date') ,
          'export' => true,
          'where' => 'civicrm_membership.reminder_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'usage' => 'System',
        ) ,
        'owner_membership_id' => array(
          'name' => 'owner_membership_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Primary Member ID') ,
          'export' => true,
          'where' => 'civicrm_membership.owner_membership_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Member_DAO_Membership',
          'usage' => 'System',
        ) ,
        'member_is_test' => array(
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Test') ,
          'import' => true,
          'where' => 'civicrm_membership.is_test',
          'headerPattern' => '/(is.)?test(.member(ship)?)?/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'member_is_pay_later' => array(
          'name' => 'is_pay_later',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Pay Later') ,
          'import' => true,
          'where' => 'civicrm_membership.is_pay_later',
          'headerPattern' => '/(is.)?(pay(.)?later)$/i',
          'dataPattern' => '',
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
            self::$_import['membership'] = &$fields[$name];
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
            self::$_export['membership'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
