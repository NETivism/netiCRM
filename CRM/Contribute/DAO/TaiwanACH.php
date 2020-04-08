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
class CRM_Contribute_DAO_TaiwanACH extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_taiwanach';
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
   * Taiwan ACH  ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to Contact ID
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Conditional foreign key to civicrm_contribution_recur id. Each contribution made in connection with a recurring contribution carries a foreign key to the recurring contribution record. This assumes we can track these processor initiated events.
   *
   * @var int unsigned
   */
  public $contribution_recur_id;
  /**
   * The Contribution Page which triggered this contribution
   *
   * @var int unsigned
   */
  public $contribution_page_id;
  /**
   * Total amount of this contribution. Use market value for non-monetary gifts.
   *
   * @var float
   */
  public $total_amount;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * Payment Type of ACH.
   *
   * @var string
   */
  public $payment_type;
  /**
   * Bank Code of ACH Account.
   *
   * @var string
   */
  public $bank_code;
  /**
   * Post Office Account Type of ACH Account.
   *
   * @var string
   */
  public $postoffice_acc_type;
  /**
   * Bank Branch of ACH Account.
   *
   * @var string
   */
  public $bank_branch;
  /**
   * Bank Account of ACH Account.
   *
   * @var string
   */
  public $bank_account;
  /**
   * Identifier Number of ACH Account.
   *
   * @var string
   */
  public $identifier_number;
  /**
   * Authenticate Code in Reply File.
   *
   * @var string
   */
  public $authenticate_code;
  /**
   * Some Custom Record Data
   *
   * @var text
   */
  public $data;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_taiwanach
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
        'contribution_recur_id' => 'civicrm_contribution_recur:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'contribution_recur_id', 'civicrm_contribution_recur', 'id');
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
  function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'taiwanach_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Taiwan ACH ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_taiwanach.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'contribution_contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_taiwanach.contact_id',
          'headerPattern' => '/contact(.?id)?/i',
          'dataPattern' => '/^\d+$/',
          'export' => true,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'contribution_recur_id' => array(
          'name' => 'contribution_recur_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionRecur',
        ) ,
        'contribution_page_id' => array(
          'name' => 'contribution_page_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribution Page ID') ,
          'import' => true,
          'where' => 'civicrm_taiwanach.contribution_page_id',
          'headerPattern' => '',
          'dataPattern' => '/^\d+$/',
          'export' => true,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionPage',
        ) ,
        'total_amount' => array(
          'name' => 'total_amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Total Amount') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_taiwanach.total_amount',
          'headerPattern' => '/^total|(.?^am(ou)?nt)/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
        ) ,
        'currency' => array(
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'import' => true,
          'where' => 'civicrm_taiwanach.currency',
          'headerPattern' => '/cur(rency)?/i',
          'dataPattern' => '/^[A-Z]{3}$/i',
          'export' => true,
          'default' => 'UL',
        ) ,
        'payment_type' => array(
          'name' => 'payment_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Type') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'bank_code' => array(
          'name' => 'bank_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bank Code') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'postoffice_acc_type' => array(
          'name' => 'postoffice_acc_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Post Office Account Type') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'bank_branch' => array(
          'name' => 'bank_branch',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bank Branch') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'bank_account' => array(
          'name' => 'bank_account',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bank Account') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'identifier_number' => array(
          'name' => 'identifier_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Identifier Number') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'authenticate_code' => array(
          'name' => 'authenticate_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Authenticate Code') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
        ) ,
        'data' => array(
          'name' => 'data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Custom Data') ,
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
            self::$_import['taiwanach'] = & $fields[$name];
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
            self::$_export['taiwanach'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
