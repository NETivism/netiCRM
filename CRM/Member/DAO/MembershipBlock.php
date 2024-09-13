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


class CRM_Member_DAO_MembershipBlock extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_membership_block';
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
   * Name for Membership Status
   *
   * @var string
   */
  public $entity_table;
  /**
   * FK to civicrm_contribution_page.id
   *
   * @var int unsigned
   */
  public $entity_id;
  /**
   * Membership types to be exposed by this block
   *
   * @var string
   */
  public $membership_types;
  /**
   * Optional foreign key to membership_type
   *
   * @var int unsigned
   */
  public $membership_type_default;
  /**
   * Display minimum membership fee
   *
   * @var boolean
   */
  public $display_min_fee;
  /**
   * Should membership transactions be processed separately
   *
   * @var boolean
   */
  public $is_separate_payment;
  /**
   * Title to display at top of block
   *
   * @var string
   */
  public $new_title;
  /**
   * Text to display below title
   *
   * @var text
   */
  public $new_text;
  /**
   * Title for renewal
   *
   * @var string
   */
  public $renewal_title;
  /**
   * Text to display for member renewal
   *
   * @var text
   */
  public $renewal_text;
  /**
   * Is membership sign up optional
   *
   * @var boolean
   */
  public $is_required;
  /**
   * Is this membership_block enabled
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is this membership_block only used for renewal
   *
   * @var boolean
   */
  public $is_renewal_only;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_membership_block
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
        'entity_id' => 'civicrm_contribution_page:id',
        'membership_type_default' => 'civicrm_membership_type:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'entity_id', 'civicrm_contribution_page', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'membership_type_default', 'civicrm_membership_type', 'id');
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
        'entity_table' => array(
          'name' => 'entity_table',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Entity Table') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'entity_id' => array(
          'name' => 'entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionPage',
        ) ,
        'membership_types' => array(
          'name' => 'membership_types',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Membership Types') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'membership_type_default' => array(
          'name' => 'membership_type_default',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Membership Type Default') ,
          'FKClassName' => 'CRM_Member_DAO_MembershipType',
        ) ,
        'display_min_fee' => array(
          'name' => 'display_min_fee',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Display Min Fee') ,
          'default' => '',
        ) ,
        'is_separate_payment' => array(
          'name' => 'is_separate_payment',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'new_title' => array(
          'name' => 'new_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('New Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'new_text' => array(
          'name' => 'new_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('New Text') ,
        ) ,
        'renewal_title' => array(
          'name' => 'renewal_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Renewal Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'renewal_text' => array(
          'name' => 'renewal_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Renewal Text') ,
        ) ,
        'is_required' => array(
          'name' => 'is_required',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Required') ,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Active') ,
          'default' => '',
        ) ,
        'is_renewal_only' => array(
          'name' => 'is_renewal_only',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Renewal Only') ,
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
            self::$_import['membership_block'] = &$fields[$name];
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
            self::$_export['membership_block'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
