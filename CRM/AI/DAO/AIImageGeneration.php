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
        class CRM_AI_DAO_AIImageGeneration extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_aiimagegeneration';
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
   * AIImageGeneration ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to civicrm_aicompletion
   *
   * @var int unsigned
   */
  public $aicompletion_id;
  /**
   * Original user input prompt
   *
   * @var text
   */
  public $original_prompt;
  /**
   * Translated and optimized prompt
   *
   * @var text
   */
  public $translated_prompt;
  /**
   * Selected image style
   *
   * @var string
   */
  public $image_style;
  /**
   * Image aspect ratio (e.g., 16:9, 1:1)
   *
   * @var string
   */
  public $image_ratio;
  /**
   * Generated image file path
   *
   * @var string
   */
  public $image_path;
  /**
   * Image generation date
   *
   * @var datetime
   */
  public $created_date;
  /**
   * 1=success, 2=pending, 3=failed
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Error message if generation failed
   *
   * @var text
   */
  public $error_message;
   /**
   * class constructor
   *
   * @access public
   * @return civicrm_aiimagegeneration
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
        'aicompletion_id' => 'civicrm_aicompletion:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'aicompletion_id', 'civicrm_aicompletion', 'id');
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
          'required' => true,
                  ] ,
        'aicompletion_id' => [
          'name' => 'aicompletion_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_AI_DAO_AICompletion',
        ] ,
        'original_prompt' => [
          'name' => 'original_prompt',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Original Prompt') ,
          'required' => true,
                  ] ,
        'translated_prompt' => [
          'name' => 'translated_prompt',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Translated Prompt') ,
          'required' => true,
                  ] ,
        'image_style' => [
          'name' => 'image_style',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Image Style') ,
          'required' => true,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'image_ratio' => [
          'name' => 'image_ratio',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Image Ratio') ,
          'required' => true,
           'maxlength' => 20,
           'size' => CRM_Utils_Type::MEDIUM,
                ] ,
        'image_path' => [
          'name' => 'image_path',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Image Path') ,
          'required' => true,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'created_date' => [
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Created Date') ,
          'required' => true,
                  ] ,
        'status_id' => [
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
                  ] ,
        'error_message' => [
          'name' => 'error_message',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Error Message') ,
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
            self::$_import['aiimagegeneration'] = &$fields[$name];
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
            self::$_export['aiimagegeneration'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
                                  }
    return self::$_export;
  }
}
