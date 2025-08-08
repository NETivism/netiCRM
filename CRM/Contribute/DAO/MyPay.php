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
              class CRM_Contribute_DAO_MyPay extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contribution_mypay';
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
   * MyPay ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Contribuution ID
   *
   * @var int unsigned
   */
  public $contribution_id;
  /**
   * Contribuution Recur ID
   *
   * @var int unsigned
   */
  public $contribution_recur_id;
  /**
   * Payment Hub User Order ID
   *
   * @var string
   */
  public $uid;
  /**
   * Transaction verification code, identified by key in return parameters.
   *
   * @var string
   */
  public $uid_key;
  /**
   * Used date time of this coupon track.
   *
   * @var date
   */
  public $expired_date;
  /**
   * The post data when send request.
   *
   * @var text
   */
  public $create_post_data;
  /**
   * The result data after sending request.
   *
   * @var text
   */
  public $create_result_data;
  /**
   * The post data as IPN sending to us.
   *
   * @var text
   */
  public $ipn_result_data;
   /**
   * class constructor
   *
   * @access public
   * @return civicrm_contribution_mypay
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
        'contribution_id' => 'civicrm_contribution:id',
        'contribution_recur_id' => 'civicrm_contribution_recur:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_id', 'civicrm_contribution', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_recur_id', 'civicrm_contribution_recur', 'id');
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
        'mypay_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('MyPay ID') ,
          'required' => true,
                  ] ,
        'contribution_id' => [
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribuution ID') ,
                  'default' => 'UL',
            'FKClassName' => 'CRM_Contribute_DAO_Contribution',
        ] ,
        'contribution_recur_id' => [
          'name' => 'contribution_recur_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribuution Recur ID') ,
                    'FKClassName' => 'CRM_Contribute_DAO_ContributionRecur',
        ] ,
        'uid' => [
          'name' => 'uid',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('User Order ID') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'uid_key' => [
          'name' => 'uid_key',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Uid Key') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'expired_date' => [
          'name' => 'expired_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Expired Date') ,
                  ] ,
        'create_post_data' => [
          'name' => 'create_post_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Create Post Data') ,
                  ] ,
        'create_result_data' => [
          'name' => 'create_result_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Create Result Data') ,
                  ] ,
        'ipn_result_data' => [
          'name' => 'ipn_result_data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Ipn Result Data') ,
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
            self::$_import['contribution_mypay'] = &$fields[$name];
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
            self::$_export['contribution_mypay'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
                                              }
    return self::$_export;
  }
}
