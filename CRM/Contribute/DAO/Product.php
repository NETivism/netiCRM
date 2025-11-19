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
class CRM_Contribute_DAO_Product extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_product';
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
   * Required product/premium name
   *
   * @var string
   */
  public $name;
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
   * Store comma-delimited list of color, size, etc. options for the product.
   *
   * @var text
   */
  public $options;
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
   * Sell price or market value for premiums. For tax-deductible contributions, this will be stored as non_deductible_amount in the contribution record.
   *
   * @var float
   */
  public $price;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
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
   * Minimum contribution required to be eligible to select this premium.
   *
   * @var float
   */
  public $min_contribution;
  /**
   * Minimum recurring contribution amount required to be eligible to select this premium.
   *
   * @var float
   */
  public $min_contribution_recur;
  /**
   * Actual cost of this product. Useful to determine net return from sale or using this as an incentive.
   *
   * @var float
   */
  public $cost;
  /**
   * Disabling premium removes it from the premiums_premium join table below.
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Rolling means we set start/end based on current day, fixed means we set start/end for current year or month
(e.g. 1 year + fixed -> we would set start/end for 1/1/06 thru 12/31/06 for any premium chosen in 2006) 
   *
   * @var enum('rolling', 'fixed')
   */
  public $period_type;
  /**
   * Month and day (MMDD) that fixed period type subscription or membership starts.
   *
   * @var int
   */
  public $fixed_period_start_day;
  /**
   *
   * @var enum('day', 'month', 'week', 'year')
   */
  public $duration_unit;
  /**
   * Number of units for total duration of subscription, service, membership (e.g. 12 Months).
   *
   * @var int
   */
  public $duration_interval;
  /**
   * Frequency unit and interval allow option to store actual delivery frequency for a subscription or service.
   *
   * @var enum('day', 'month', 'week', 'year')
   */
  public $frequency_unit;
  /**
   * Number of units for delivery frequency of subscription, service, membership (e.g. every 3 Months).
   *
   * @var int
   */
  public $frequency_interval;
  /**
   * Stock management status for this product.
   *
   * @var boolean
   */
  public $stock_status;
  /**
   * Available stock quantity for this product.
   *
   * @var int
   */
  public $stock_qty;
  /**
   * Number of products already sent or delivered.
   *
   * @var int
   */
  public $send_qty;
  /**
   * When was the product was created.
   *
   * @var datetime
   */
  public $created_date;
  /**
   * When was the product was created or modified or deleted.
   *
   * @var datetime
   */
  public $modified_date;
   /**
   * class constructor
   *
   * @access public
   * @return civicrm_product
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
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
                  ] ,
        'product_name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Product Name') ,
          'required' => true,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
              'export' => true,
          'where' => 'civicrm_product.name',
          'headerPattern' => '',
          'dataPattern' => '',
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
          'where' => 'civicrm_product.sku',
          'headerPattern' => '',
          'dataPattern' => '',
            ] ,
        'options' => [
          'name' => 'options',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Options') ,
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
        'price' => [
          'name' => 'price',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Price') ,
                  ] ,
        'currency' => [
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
           'maxlength' => 3,
           'size' => CRM_Utils_Type::FOUR,
                'default' => 'UL',
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
        'min_contribution' => [
          'name' => 'min_contribution',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Minimum Contribution') ,
                  ] ,
        'min_contribution_recur' => [
          'name' => 'min_contribution_recur',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Minimum Contribution of Recurring') ,
                  ] ,
        'cost' => [
          'name' => 'cost',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Cost') ,
                  ] ,
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Active') ,
          'required' => true,
                  ] ,
        'period_type' => [
          'name' => 'period_type',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Period Type') ,
                  'default' => 'rolling',
           'enumValues' => 'rolling,fixed',
         ] ,
        'fixed_period_start_day' => [
          'name' => 'fixed_period_start_day',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Fixed Period Start Day') ,
                  'default' => '10',
          ] ,
        'duration_unit' => [
          'name' => 'duration_unit',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Duration Unit') ,
                  'default' => 'year',
           'enumValues' => 'day,month,week,year',
         ] ,
        'duration_interval' => [
          'name' => 'duration_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Duration Interval') ,
                  ] ,
        'frequency_unit' => [
          'name' => 'frequency_unit',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Frequency Unit') ,
                  'default' => 'month',
           'enumValues' => 'day,month,week,year',
         ] ,
        'frequency_interval' => [
          'name' => 'frequency_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Frequency Interval') ,
                  ] ,
        'stock_status' => [
          'name' => 'stock_status',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Stock Status') ,
                  ] ,
        'stock_qty' => [
          'name' => 'stock_qty',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Stock Quantity') ,
                  ] ,
        'send_qty' => [
          'name' => 'send_qty',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Sent Quantity') ,
                  ] ,
        'product_created_date' => [
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Product Created Date') ,
          'required' => false,
                  'default' => 'URRENT_TIMESTAM',
          ] ,
        'product_modified_date' => [
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Product Modified Date') ,
          'required' => false,
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
            self::$_import['product'] = &$fields[$name];
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
            self::$_export['product'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
          }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_product table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = [
                                                                                                                                                                                                        'period_type',
                                      'duration_unit',
                                      'frequency_unit',
                                                                                        ];
    return $enums;
  }
  /**
   * returns a ts()-translated enum value for display purposes
   *
   * @param string $field  the enum field in question
   * @param string $value  the enum value up for translation
   *
   * @return string  the display value of the enum
   */
  static function tsEnum($field, $value)
  {
    static $translations = null;
    if (!$translations) {
      $translations = [
                                                                                                                                                                                                        'period_type' => [
                  'rolling' => ts('rolling'),
                  'fixed' => ts('fixed'),
                ],
                                      'duration_unit' => [
                  'day' => ts('day'),
                  'month' => ts('month'),
                  'week' => ts('week'),
                  'year' => ts('year'),
                ],
                                      'frequency_unit' => [
                  'day' => ts('day'),
                  'month' => ts('month'),
                  'week' => ts('week'),
                  'year' => ts('year'),
                ],
                                                                                          ];
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_product
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Contribute_DAO_Product::getEnums();
    foreach ($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Contribute_DAO_Product::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
