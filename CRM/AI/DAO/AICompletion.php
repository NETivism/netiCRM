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
class CRM_AI_DAO_AICompletion extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_aicompletion';
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
   * AICompletion ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to civicrm_contact
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Set to 1 to indicate that this AI completion is saved as a template.
   *
   * @var boolean
   */
  public $is_template;
  /**
   * Save as template title.
   *
   * @var string
   */
  public $template_title;
  /**
   * AI tone style
   *
   * @var string
   */
  public $tone_style;
  /**
   * AI role
   *
   * @var string
   */
  public $ai_role;
  /**
   * AI context
   *
   * @var text
   */
  public $context;
  /**
   * text that AI return
   *
   * @var text
   */
  public $output_text;
  /**
   * Mark 1 when this context allow to share with other
   *
   * @var boolean
   */
  public $is_share_with_others;
  /**
   * the day create AI completion
   *
   * @var datetime
   */
  public $created_date;
  /**
   * context that send to AI
   *
   * @var text
   */
  public $prompt;
  /**
   * Name of the function where the referenced item is used in.
   *
   * @var string
   */
  public $component;
  /**
   * Additional data field to be used in AI completion.
   *
   * @var string
   */
  public $field;
  /**
   * If the number is higher the more creative.
   *
   * @var float
   */
  public $temperature;
  /**
   * Data to be sent as a POST request to the AI.
   *
   * @var text
   */
  public $post_data;
  /**
   * Data returned from the AI server.
   *
   * @var text
   */
  public $return_data;
  /**
   * the token for prompt
   *
   * @var int unsigned
   */
  public $prompt_token;
  /**
   * the token for completion
   *
   * @var int unsigned
   */
  public $completion_token;
  /**
   * 1=completed, 2=pending, 3=cancel, 4=failed
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_aicompletion
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
        'contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'is_template' => array(
          'name' => 'is_template',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'required' => true,
        ) ,
        'template_title' => array(
          'name' => 'template_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Template Title') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'tone_style' => array(
          'name' => 'tone_style',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Tone Style') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'ai_role' => array(
          'name' => 'ai_role',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Ai Role') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'context' => array(
          'name' => 'context',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Context') ,
          'required' => true,
        ) ,
        'output_text' => array(
          'name' => 'output_text',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Output Text') ,
          'required' => true,
        ) ,
        'is_share_with_others' => array(
          'name' => 'is_share_with_others',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'required' => true,
        ) ,
        'created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Created Date') ,
          'required' => true,
        ) ,
        'prompt' => array(
          'name' => 'prompt',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Prompt') ,
          'required' => true,
        ) ,
        'component' => array(
          'name' => 'component',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Component') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'field' => array(
          'name' => 'field',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Field') ,
          'required' => true,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ) ,
        'temperature' => array(
          'name' => 'temperature',
          'type' => CRM_Utils_Type::T_FLOAT,
          'title' => ts('Temperature') ,
          'required' => true,
        ) ,
        'post_data' => array(
          'name' => 'post_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Post Data') ,
          'required' => true,
        ) ,
        'return_data' => array(
          'name' => 'return_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Return Data') ,
          'required' => true,
        ) ,
        'prompt_token' => array(
          'name' => 'prompt_token',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Prompt Token') ,
          'required' => true,
        ) ,
        'completion_token' => array(
          'name' => 'completion_token',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Completion Token') ,
          'required' => true,
        ) ,
        'status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
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
            self::$_import['aicompletion'] = &$fields[$name];
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
            self::$_export['aicompletion'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
