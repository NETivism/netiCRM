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
class CRM_Core_DAO_Track extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_track';
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
   * ID for internal usage
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Session of this visit
   *
   * @var string
   */
  public $session_key;
  /**
   * Counter of visit times in same person.
   *
   * @var int unsigned
   */
  public $counter;
  /**
   * Date time of this visit
   *
   * @var datetime
   */
  public $visit_date;
  /**
   * Page type like civicrm_event or civicrm_contribution_page
   *
   * @var string
   */
  public $page_type;
  /**
   * Page id.
   *
   * @var int unsigned
   */
  public $page_id;
  /**
   * Depth of this visit
   *
   * @var int unsigned
   */
  public $state;
  /**
   * Referrer type of inbound visit
   *
   * @var string
   */
  public $referrer_type;
  /**
   * Referrer network of inbound visit
   *
   * @var string
   */
  public $referrer_network;
  /**
   * Referrer url of inbound traffic. Null when no referrer
   *
   * @var string
   */
  public $referrer_url;
  /**
   * Landing page of this record without protocol and host name
   *
   * @var string
   */
  public $landing;
  /**
   *
   * @var string
   */
  public $utm_source;
  /**
   *
   * @var string
   */
  public $utm_medium;
  /**
   *
   * @var string
   */
  public $utm_campaign;
  /**
   *
   * @var string
   */
  public $utm_term;
  /**
   *
   * @var string
   */
  public $utm_content;
  /**
   * Entity table like civicrm_contribution or civicrm_participant or civicrm_membership
   *
   * @var string
   */
  public $entity_table;
  /**
   * Entity id like contribution id or participant id
   *
   * @var int unsigned
   */
  public $entity_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_track
   */
  function __construct()
  {
    parent::__construct();
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'session_key' => array(
          'name' => 'session_key',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Session Key') ,
          'required' => true,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'counter' => array(
          'name' => 'counter',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Counter') ,
          'required' => true,
          'default' => '',
        ) ,
        'visit_date' => array(
          'name' => 'visit_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Visit Date') ,
          'required' => true,
        ) ,
        'page_type' => array(
          'name' => 'page_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Page Type') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'page_id' => array(
          'name' => 'page_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
        ) ,
        'state' => array(
          'name' => 'state',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('State') ,
        ) ,
        'referrer_type' => array(
          'name' => 'referrer_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Referrer Type') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'referrer_network' => array(
          'name' => 'referrer_network',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Referrer Network') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'referrer_url' => array(
          'name' => 'referrer_url',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Referrer Url') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'landing' => array(
          'name' => 'landing',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Landing') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'utm_source' => array(
          'name' => 'utm_source',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Utm Source') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'utm_medium' => array(
          'name' => 'utm_medium',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Utm Medium') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'utm_campaign' => array(
          'name' => 'utm_campaign',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Utm Campaign') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'utm_term' => array(
          'name' => 'utm_term',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Utm Term') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'utm_content' => array(
          'name' => 'utm_content',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Utm Content') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
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
  function getTableName()
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
  function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = & self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['track'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
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
  function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = & self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['track'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
