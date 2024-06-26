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
class CRM_Core_DAO_UFGroup extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_uf_group';
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
   * Unique table ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Is this form currently active? If false, hide all related fields for all sharing contexts.
   *
   * @var boolean
   */
  public $is_active;
  /**
   * This column will store a comma separated list of the type(s) of profile fields.
   *
   * @var string
   */
  public $group_type;
  /**
   * Form title.
   *
   * @var string
   */
  public $title;
  /**
   * Description and/or help text to display before fields in form.
   *
   * @var text
   */
  public $help_pre;
  /**
   * Description and/or help text to display after fields in form.
   *
   * @var text
   */
  public $help_post;
  /**
   * Group id, foriegn key from civicrm_group
   *
   * @var int unsigned
   */
  public $limit_listings_group_id;
  /**
   * Redirect to URL.
   *
   * @var string
   */
  public $post_URL;
  /**
   * foreign key to civicrm_group_id
   *
   * @var int unsigned
   */
  public $add_to_group_id;
  /**
   * Should a CAPTCHA widget be included this Profile form.
   *
   * @var boolean
   */
  public $add_captcha;
  /**
   * Do we want to map results from this profile.
   *
   * @var boolean
   */
  public $is_map;
  /**
   * Should edit link display in profile selector
   *
   * @var boolean
   */
  public $is_edit_link;
  /**
   * Should we display a link to the website profile in profile selector
   *
   * @var boolean
   */
  public $is_uf_link;
  /**
   * Should we update the contact record if we find a duplicate
   *
   * @var boolean
   */
  public $is_update_dupe;
  /**
   * Redirect to URL when Cancle button clik .
   *
   * @var string
   */
  public $cancel_URL;
  /**
   * Should we create a cms user for this profile
   *
   * @var boolean
   */
  public $is_cms_user;
  /**
   *
   * @var text
   */
  public $notify;
  /**
   * Is this group reserved for use by some other CiviCRM functionality?
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * Name of the UF group for directly addressing it in the codebase
   *
   * @var string
   */
  public $name;
  /**
   * FK to civicrm_contact, who created this UF group
   *
   * @var int unsigned
   */
  public $created_id;
  /**
   * Date and time this UF group was created.
   *
   * @var datetime
   */
  public $created_date;
  /**
   * Should we include proximity search feature in this profile search form?
   *
   * @var boolean
   */
  public $is_proximity_search;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_uf_group
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
        'limit_listings_group_id' => 'civicrm_group:id',
        'add_to_group_id' => 'civicrm_group:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'limit_listings_group_id', 'civicrm_group', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'add_to_group_id', 'civicrm_group', 'id');
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
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'default' => '',
        ) ,
        'group_type' => array(
          'name' => 'group_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Group Type') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_uf_group.group_type',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'title' => array(
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Title') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'help_pre' => array(
          'name' => 'help_pre',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Help Pre') ,
          'rows' => 4,
          'cols' => 80,
        ) ,
        'help_post' => array(
          'name' => 'help_post',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Help Post') ,
          'rows' => 4,
          'cols' => 80,
        ) ,
        'limit_listings_group_id' => array(
          'name' => 'limit_listings_group_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Group',
        ) ,
        'post_URL' => array(
          'name' => 'post_URL',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Post Url') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'add_to_group_id' => array(
          'name' => 'add_to_group_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Group',
        ) ,
        'add_captcha' => array(
          'name' => 'add_captcha',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Add Captcha') ,
        ) ,
        'is_map' => array(
          'name' => 'is_map',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_edit_link' => array(
          'name' => 'is_edit_link',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_uf_link' => array(
          'name' => 'is_uf_link',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'is_update_dupe' => array(
          'name' => 'is_update_dupe',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'cancel_URL' => array(
          'name' => 'cancel_URL',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Cancel Url') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'is_cms_user' => array(
          'name' => 'is_cms_user',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'notify' => array(
          'name' => 'notify',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Notify') ,
        ) ,
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'name' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'created_id' => array(
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('UF Group Created Date') ,
        ) ,
        'is_proximity_search' => array(
          'name' => 'is_proximity_search',
          'type' => CRM_Utils_Type::T_BOOLEAN,
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
            self::$_import['uf_group'] = &$fields[$name];
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
            self::$_export['uf_group'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
