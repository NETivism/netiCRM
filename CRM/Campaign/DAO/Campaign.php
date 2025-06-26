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
class CRM_Campaign_DAO_Campaign extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_campaign';
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
  static $_log = false;
  /**
   * Unique Campaign ID.
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Name of the Campaign.
   *
   * @var string
   */
  public $name;
  /**
   * Title of the Campaign.
   *
   * @var string
   */
  public $title;
  /**
   * Full description of Campaign.
   *
   * @var text
   */
  public $description;
  /**
   * Date and time that Campaign starts.
   *
   * @var datetime
   */
  public $start_date;
  /**
   * Date and time that Campaign ends.
   *
   * @var datetime
   */
  public $end_date;
  /**
   * Campaign Type ID.Implicit FK to civicrm_option_value where option_group = campaign_type
   *
   * @var int unsigned
   */
  public $campaign_type_id;
  /**
   * Campaign status ID.Implicit FK to civicrm_option_value where option_group = campaign_status
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Unique trusted external ID (generally from a legacy app/datasource). Particularly useful for deduping operations.
   *
   * @var string
   */
  public $external_identifier;
  /**
   * Optional parent id for this Campaign.
   *
   * @var int unsigned
   */
  public $parent_id;
  /**
   * Is this Campaign enabled or disabled/cancelled?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * FK to civicrm_contact, who created this Campaign.
   *
   * @var int unsigned
   */
  public $created_id;
  /**
   * Date and time that Campaign was created.
   *
   * @var datetime
   */
  public $created_date;
  /**
   * FK to civicrm_contact, who recently edited this Campaign.
   *
   * @var int unsigned
   */
  public $last_modified_id;
  /**
   * Date and time that Campaign was edited last time.
   *
   * @var datetime
   */
  public $last_modified_date;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_campaign
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
      self::$_links = [
        'parent_id' => 'civicrm_campaign:id',
        'created_id' => 'civicrm_contact:id',
        'last_modified_id' => 'civicrm_contact:id',
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
  public static function getReferenceColumns()
  {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static ::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'parent_id', 'civicrm_campaign', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'created_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'last_modified_id', 'civicrm_contact', 'id');
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
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Campaign ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_campaign.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ] ,
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Campaign Name') ,
          'required' => true,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_campaign.name',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ] ,
        'title' => [
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Campaign Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_campaign.title',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ] ,
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Campaign Description') ,
          'rows' => 8,
          'cols' => 60,
        ] ,
        'start_date' => [
          'name' => 'start_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Campaign Start Date') ,
          'import' => true,
          'where' => 'civicrm_campaign.start_date',
          'headerPattern' => '/^start|(s(tart\s)?date)$/i',
          'dataPattern' => '',
          'export' => true,
        ] ,
        'end_date' => [
          'name' => 'end_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Campaign End Date') ,
          'import' => true,
          'where' => 'civicrm_campaign.end_date',
          'headerPattern' => '/^end|(e(nd\s)?date)$/i',
          'dataPattern' => '',
          'export' => true,
        ] ,
        'campaign_type_id' => [
          'name' => 'campaign_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Campaign Type ID') ,
          'import' => true,
          'where' => 'civicrm_campaign.campaign_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'default' => 'UL',
        ] ,
        'status_id' => [
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Campaign Status ID') ,
          'import' => true,
          'where' => 'civicrm_campaign.status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'default' => 'UL',
        ] ,
        'external_identifier' => [
          'name' => 'external_identifier',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('External Identifier') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'import' => true,
          'where' => 'civicrm_campaign.external_identifier',
          'headerPattern' => '/external\s?id/i',
          'dataPattern' => '/^\d{11,}$/',
          'export' => true,
        ] ,
        'parent_id' => [
          'name' => 'parent_id',
          'type' => CRM_Utils_Type::T_INT,
          'import' => true,
          'where' => 'civicrm_campaign.parent_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
          'default' => 'UL',
          'FKClassName' => 'CRM_Campaign_DAO_Campaign',
        ] ,
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ] ,
        'created_id' => [
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ] ,
        'created_date' => [
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Campaign Created Date') ,
        ] ,
        'last_modified_id' => [
          'name' => 'last_modified_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ] ,
        'last_modified_date' => [
          'name' => 'last_modified_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Last Modified Date') ,
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
      self::$_import = [];
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['campaign'] = &$fields[$name];
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
      self::$_export = [];
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['campaign'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
