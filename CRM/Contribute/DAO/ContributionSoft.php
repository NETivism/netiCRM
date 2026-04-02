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

class CRM_Contribute_DAO_ContributionSoft extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_contribution_soft';
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
   * Soft Contribution ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * FK to contribution table.
   *
   * @var int unsigned
   */
  public $contribution_id;
  /**
   * FK to Contact ID
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Amount of this soft contribution.
   *
   * @var float
   */
  public $amount;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * FK to civicrm_pcp.id
   *
   * @var int unsigned
   */
  public $pcp_id;
  /**
   *
   * @var boolean
   */
  public $pcp_display_in_roll;
  /**
   *
   * @var string
   */
  public $pcp_roll_nickname;
  /**
   *
   * @var string
   */
  public $pcp_personal_note;
  /**
   * class constructor
   *
   * @return civicrm_contribution_soft
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
        'contribution_id' => 'civicrm_contribution:id',
        'contact_id' => 'civicrm_contact:id',
        'pcp_id' => 'civicrm_pcp:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contribution_id', 'civicrm_contribution', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'pcp_id', 'civicrm_pcp', 'id');
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
        'contribution_soft_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Soft Contribution ID') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_contribution_soft.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'contribution_id' => [
          'name' => 'contribution_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'FKClassName' => 'CRM_Contribute_DAO_Contribution',
        ],
        'contribution_soft_contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_contribution_soft.contact_id',
          'headerPattern' => '/contact(.?id)?/i',
          'dataPattern' => '/^\d+$/',
          'export' => TRUE,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ],
        'amount' => [
          'name' => 'amount',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Amount') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_contribution_soft.amount',
          'headerPattern' => '/total(.?am(ou)?nt)?/i',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => TRUE,
        ],
        'currency' => [
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Currency') ,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'default' => 'UL',
        ],
        'pcp_id' => [
          'name' => 'pcp_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => 'UL',
          'FKClassName' => 'CRM_Contribute_DAO_PCP',
        ],
        'pcp_display_in_roll' => [
          'name' => 'pcp_display_in_roll',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Pcp Display In Roll') ,
        ],
        'pcp_roll_nickname' => [
          'name' => 'pcp_roll_nickname',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Pcp Roll Nickname') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'default' => 'UL',
        ],
        'pcp_personal_note' => [
          'name' => 'pcp_personal_note',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Pcp Personal Note') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'default' => 'UL',
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
            self::$_import['contribution_soft'] = &$fields[$name];
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
            self::$_export['contribution_soft'] = &$fields[$name];
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
