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
        class CRM_Contribute_DAO_PremiumsCombination extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_premiums_combination';
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
   * Foreign key to civicrm_premiums table
   *
   * @var int unsigned
   */
  public $premiums_id;
  /**
   * Name of the premium combination
   *
   * @var string
   */
  public $combination_name;
  /**
   * Optional description of the product/premium.
   *
   * @var text
   */
  public $description;
  /**
   * Optional product sku or code.
   *
   * @var string
   */
  public $sku;
  /**
   * Minimum non-recurring contribution amount required for this combination
   *
   * @var float
   */
  public $min_contribution;
  /**
   * Minimum recurring contribution amount threshold for this combination
   *
   * @var float
   */
  public $min_contribution_recur;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * Whether this combination is active
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Order for displaying combinations
   *
   * @var int unsigned
   */
  public $weight;
  /**
   * Calculate by accumulate or by min-amount of contribution
   *
   * @var string
   */
  public $calculate_mode;
  /**
   * Num of installments when calculate mode is accumulate.
   *
   * @var int
   */
  public $installments;
  /**
   * Full or relative URL to uploaded image - fullsize.
   *
   * @var string
   */
  public $image;
  /**
   * Full or relative URL to image thumbnail.
   *
   * @var string
   */
  public $thumbnail;
  /**
   * When was this combination created
   *
   * @var timestamp
   */
  public $created_date;
  /**
   * When was this combination last modified
   *
   * @var timestamp
   */
  public $modified_date;
   /**
   * class constructor
   *
   * @access public
   * @return civicrm_premiums_combination
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
        'premiums_id' => 'civicrm_premiums:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'premiums_id', 'civicrm_premiums', 'id');
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
        'premiums_id' => [
          'name' => 'premiums_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
                    'FKClassName' => 'CRM_Contribute_DAO_Premium',
        ] ,
        'combination_name' => [
          'name' => 'combination_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Combination Name') ,
          'required' => true,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Description') ,
                  ] ,
        'sku' => [
          'name' => 'sku',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('SKU') ,
           'maxlength' => 50,
           'size' => CRM_Utils_Type::BIG,
              'export' => true,
          'where' => 'civicrm_premiums_combination.sku',
          'headerPattern' => '',
          'dataPattern' => '',
            ] ,
        'min_contribution' => [
          'name' => 'min_contribution',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Min Contribution') ,
          'required' => true,
                  ] ,
        'min_contribution_recur' => [
          'name' => 'min_contribution_recur',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Min Contribution Recur') ,
                  ] ,
        'currency' => [
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
           'maxlength' => 3,
           'size' => CRM_Utils_Type::FOUR,
                'default' => 'UL',
          ] ,
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
                  'default' => '',
          ] ,
        'weight' => [
          'name' => 'weight',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Weight') ,
                  ] ,
        'calculate_mode' => [
          'name' => 'calculate_mode',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Calculate Mode') ,
           'maxlength' => 32,
           'size' => CRM_Utils_Type::MEDIUM,
                'default' => 'UL',
          ] ,
        'installments' => [
          'name' => 'installments',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Installments') ,
                  ] ,
        'image' => [
          'name' => 'image',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Image') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'thumbnail' => [
          'name' => 'thumbnail',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Thumbnail') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'created_date' => [
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_TIMESTAMP,
          'title' => ts('Created Date') ,
          'required' => true,
                  'default' => 'URRENT_TIMESTAM',
          ] ,
        'modified_date' => [
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_TIMESTAMP,
          'title' => ts('Modified Date') ,
          'required' => true,
                  'default' => 'URRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAM',
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
      self::$_import = [];
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['premiums_combination'] = &$fields[$name];
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
            self::$_export['premiums_combination'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
                                  }
    return self::$_export;
  }
}
