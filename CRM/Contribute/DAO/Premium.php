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
class CRM_Contribute_DAO_Premium extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_premiums';
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
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Joins these premium settings to another object. Always civicrm_contribution_page for now.
   *
   * @var string
   */
  public $entity_table;
  /**
   *
   * @var int unsigned
   */
  public $entity_id;
  /**
   * Is the Premiums feature enabled for this page?
   *
   * @var boolean
   */
  public $premiums_active;
  /**
   * Title for Premiums section.
   *
   * @var string
   */
  public $premiums_intro_title;
  /**
   * Displayed in <div> at top of Premiums section of page. Text and HTML allowed.
   *
   * @var text
   */
  public $premiums_intro_text;
  /**
   * This email address is included in receipts if it is populated and a premium has been selected.
   *
   * @var string
   */
  public $premiums_contact_email;
  /**
   * This phone number is included in receipts if it is populated and a premium has been selected.
   *
   * @var string
   */
  public $premiums_contact_phone;
  /**
   * Boolean. Should we automatically display minimum contribution amount text after the premium descriptions.
   *
   * @var boolean
   */
  public $premiums_display_min_contribution;
  /**
   * No thank you text for premium page display.
   *
   * @var string
   */
  public $premiums_nothanks_text;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_premiums
   */
  function __construct()
  {
    parent::__construct();
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Dynamic(self::getTableName() , 'entity_id', NULL, 'id', 'entity_table');
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
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'entity_id' => array(
          'name' => 'entity_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'premiums_active' => array(
          'name' => 'premiums_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Premiums Active') ,
          'required' => true,
        ) ,
        'premiums_intro_title' => array(
          'name' => 'premiums_intro_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Title for Premiums section') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'premiums_intro_text' => array(
          'name' => 'premiums_intro_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Premiums Intro Text') ,
        ) ,
        'premiums_contact_email' => array(
          'name' => 'premiums_contact_email',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Premiums Contact Email') ,
          'maxlength' => 100,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'premiums_contact_phone' => array(
          'name' => 'premiums_contact_phone',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Premiums Contact Phone') ,
          'maxlength' => 50,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'premiums_display_min_contribution' => array(
          'name' => 'premiums_display_min_contribution',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Premiums Display Min Contribution') ,
          'required' => true,
        ) ,
        'premiums_nothanks_text' => array(
          'name' => 'premiums_nothanks_text',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Premiums Nothanks Text') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
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
            self::$_import['premiums'] = &$fields[$name];
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
            self::$_export['premiums'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
