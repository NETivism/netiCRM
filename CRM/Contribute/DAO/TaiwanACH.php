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
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Contribute_DAO_TaiwanACH extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_contribution_taiwanach';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  public static $_fields = NULL;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  public static $_links = NULL;
  /**
   * static instance to hold the values that can
   * be imported / apu
   *
   * @var array
   * @static
   */
  public static $_import = NULL;
  /**
   * static instance to hold the values that can
   * be exported / apu
   *
   * @var array
   * @static
   */
  public static $_export = NULL;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  public static $_log = TRUE;
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
   * Order number of ACH Account.
   *
   * @var string
   */
  public $order_number;
  /**
   * Stamp Verification Status
   *
   * @var int unsigned
   */
  public $stamp_verification;
  /**
   * Some Custom Record Data
   *
   * @var text
   */
  public $data;
  /**
   * class constructor
   *
   * @return civicrm_contribution_taiwanach
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * return foreign links
   *
   * @return array
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'contact_id' => 'civicrm_contact:id',
        'contribution_recur_id' => 'civicrm_contribution_recur:id',
        'contribution_page_id' => 'civicrm_contribution_page:id',
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
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contribution_recur_id', 'civicrm_contribution_recur', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contribution_page_id', 'civicrm_contribution_page', 'id');
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'taiwanach_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Taiwan ACH ID') ,
          'required' => TRUE,
        ],
        'contribution_contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => TRUE,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ],
        'contribution_recur_id' => [
          'name' => 'contribution_recur_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionRecur',
        ],
        'contribution_page_id' => [
          'name' => 'contribution_page_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contribution Page ID') ,
          'FKClassName' => 'CRM_Contribute_DAO_ContributionPage',
        ],
        'payment_type' => [
          'name' => 'payment_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Payment Type') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.payment_type',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'bank_code' => [
          'name' => 'bank_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bank Code') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.bank_code',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'postoffice_acc_type' => [
          'name' => 'postoffice_acc_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Post Office Account Type') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.postoffice_acc_type',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'bank_branch' => [
          'name' => 'bank_branch',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bank Branch') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.bank_branch',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'bank_account' => [
          'name' => 'bank_account',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Bank Account') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.bank_account',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'identifier_number' => [
          'name' => 'identifier_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Identifier Number') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.identifier_number',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'order_number' => [
          'name' => 'order_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Order Number') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.order_number',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'stamp_verification' => [
          'name' => 'stamp_verification',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Stamp Verification') ,
          'export' => TRUE,
          'where' => 'civicrm_contribution_taiwanach.stamp_verification',
          'headerPattern' => '',
          'dataPattern' => '',
        ],
        'data' => [
          'name' => 'data',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Custom Data') ,
        ],
      ];
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @return boolean
   */
  public function getLog() {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['contribution_taiwanach'] = &$fields[$name];
          }
          else {
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
   * @return array
   */
  public static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['contribution_taiwanach'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
