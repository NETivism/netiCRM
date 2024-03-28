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
class CRM_Contribute_DAO_Widget extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_widget';
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
   * The Contribution Page which triggered this contribution
   *
   * @var int unsigned
   */
  public $contribution_page_id;
  /**
   * Is this property active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Widget title.
   *
   * @var string
   */
  public $title;
  /**
   * URL to Widget logo
   *
   * @var string
   */
  public $url_logo;
  /**
   * Button title.
   *
   * @var string
   */
  public $button_title;
  /**
   * About description.
   *
   * @var text
   */
  public $about;
  /**
   * URL to Homepage.
   *
   * @var string
   */
  public $url_homepage;
  /**
   *
   * @var string
   */
  public $color_title;
  /**
   *
   * @var string
   */
  public $color_button;
  /**
   *
   * @var string
   */
  public $color_bar;
  /**
   *
   * @var string
   */
  public $color_main_text;
  /**
   *
   * @var string
   */
  public $color_main;
  /**
   *
   * @var string
   */
  public $color_main_bg;
  /**
   *
   * @var string
   */
  public $color_bg;
  /**
   *
   * @var string
   */
  public $color_about_link;
  /**
   *
   * @var string
   */
  public $color_homepage_link;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_widget
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
        'contribution_page_id' => 'civicrm_contribution_page:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_page_id', 'civicrm_contribution_page', 'id');
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
        'contribution_page_id' => array(
          'name' => 'contribution_page_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionPage',
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ) ,
        'title' => array(
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'url_logo' => array(
          'name' => 'url_logo',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Url Logo') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'button_title' => array(
          'name' => 'button_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Button Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'about' => array(
          'name' => 'about',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('About') ,
        ) ,
        'url_homepage' => array(
          'name' => 'url_homepage',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Url Homepage') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'color_title' => array(
          'name' => 'color_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Title') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_button' => array(
          'name' => 'color_button',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Button') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_bar' => array(
          'name' => 'color_bar',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Bar') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_main_text' => array(
          'name' => 'color_main_text',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Main Text') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_main' => array(
          'name' => 'color_main',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Main') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_main_bg' => array(
          'name' => 'color_main_bg',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Main Bg') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_bg' => array(
          'name' => 'color_bg',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Bg') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_about_link' => array(
          'name' => 'color_about_link',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color About Link') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
        ) ,
        'color_homepage_link' => array(
          'name' => 'color_homepage_link',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Color Homepage Link') ,
          'maxlength' => 10,
          'size' => CRM_Utils_Type::TWELVE,
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
            self::$_import['contribution_widget'] = &$fields[$name];
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
            self::$_export['contribution_widget'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
