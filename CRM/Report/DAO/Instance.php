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
class CRM_Report_DAO_Instance extends CRM_Core_DAO {
  /**
   * Database table name for this DAO.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_report_instance';
  /**
   * Cached field definitions for this table.
   *
   * @var array|null
   */
  public static $_fields = NULL;
  /**
   * Cached foreign key relationship definitions.
   *
   * @var array|null
   */
  public static $_links = NULL;
  /**
   * Cached list of importable field names.
   *
   * @var array|null
   */
  public static $_import = NULL;
  /**
   * Cached list of exportable field names.
   *
   * @var array|null
   */
  public static $_export = NULL;
  /**
   * Whether modifications to this table are logged to civicrm_log.
   *
   * @var bool
   */
  public static $_log = FALSE;
  /**
   * Report Instance ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Which Domain is this instance for
   *
   * @var int unsigned
   */
  public $domain_id;
  /**
   * Report Instance Title.
   *
   * @var string
   */
  public $title;
  /**
   * FK to civicrm_option_value for the report template
   *
   * @var string
   */
  public $report_id;
  /**
   * when combined with report_id/template uniquely identifies the instance
   *
   * @var string
   */
  public $name;
  /**
   * arguments that are passed in the url when invoking the instance
   *
   * @var string
   */
  public $args;
  /**
   * Report Instance description.
   *
   * @var string
   */
  public $description;
  /**
   * permission required to be able to run this instance
   *
   * @var string
   */
  public $permission;
  /**
   * Submitted form values for this report
   *
   * @var text
   */
  public $form_values;
  /**
   * Is this entry active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Subject of email
   *
   * @var string
   */
  public $email_subject;
  /**
   * comma-separated list of email addresses to send the report to
   *
   * @var text
   */
  public $email_to;
  /**
   * comma-separated list of email addresses to send the report to
   *
   * @var text
   */
  public $email_cc;
  /**
   * HTML header prepended to the report output (e.g. opening html/head/body tags, CSS imports).
   *
   * @var string
   */
  public $header;
  /**
   * HTML footer appended to the report output (e.g. closing body/html tags, powered-by image).
   *
   * @var string
   */
  public $footer;
  /**
   * FK to navigation ID
   *
   * @var int unsigned
   */
  public $navigation_id;
  /**
   * Class constructor. Calls parent CRM_Core_DAO constructor.
   */
  public function __construct() {
    parent::__construct();
  }
  /**
   * Returns the foreign key relationship map for this table.
   *
   * @return array Associative array of local_column => foreign_table:foreign_column.
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'domain_id' => 'civicrm_domain:id',
        'navigation_id' => 'civicrm_navigation:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'domain_id', 'civicrm_domain', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'navigation_id', 'civicrm_navigation', 'id');
    }
    return Civi::$statics[__CLASS__]['links'];
  }
  /**
   * Returns all field definitions for this table.
   *
   * @return array Associative array of field_name => field definition array.
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
                  ] ,
        'domain_id' => [
          'name' => 'domain_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
                    'FKClassName' => 'CRM_Core_DAO_Domain',
        ] ,
        'title' => [
          'name' => 'title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Report Instance Title') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'report_id' => [
          'name' => 'report_id',
          'type' => CRM_Utils_Type::T_STRING,
          'required' => TRUE,
           'maxlength' => 64,
           'size' => CRM_Utils_Type::BIG,
                ] ,
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'args' => [
          'name' => 'args',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Args') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Description') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'permission' => [
          'name' => 'permission',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Permission') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'form_values' => [
          'name' => 'form_values',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Submitted Form Values') ,
               'import' => TRUE,
          'where' => 'civicrm_report_instance.form_values',
          'headerPattern' => '',
          'dataPattern' => '',
           'export' => TRUE,
            ] ,
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
                  ] ,
        'email_subject' => [
          'name' => 'email_subject',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Email Subject') ,
           'maxlength' => 255,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'email_to' => [
          'name' => 'email_to',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Email To') ,
                  ] ,
        'email_cc' => [
          'name' => 'email_cc',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Email Cc') ,
                  ] ,
        'header' => [
          'name' => 'header',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Header') ,
             'rows' => 4,
           'cols' => 60,
              ] ,
        'footer' => [
          'name' => 'footer',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Footer') ,
             'rows' => 4,
           'cols' => 60,
              ] ,
        'navigation_id' => [
          'name' => 'navigation_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Navigation ID') ,
               'import' => TRUE,
          'where' => 'civicrm_report_instance.navigation_id',
          'headerPattern' => '',
          'dataPattern' => '',
           'export' => TRUE,
              'FKClassName' => 'CRM_Core_DAO_Navigation',
        ] ,
      ];
    }
    return self::$_fields;
  }
  /**
   * Returns the database table name for this DAO.
   *
   * @return string The table name 'civicrm_report_instance'.
   */
  public static function getTableName() {
    return self::$_tableName;
  }
  /**
   * Returns whether changes to this table are logged to civicrm_log.
   *
   * @return bool Always FALSE for report instances.
   */
  public function getLog() {
    return self::$_log;
  }
  /**
   * Returns the list of fields marked as importable.
   *
   * @param bool $prefix If TRUE, keys the result under 'report_instance' instead of field name.
   *
   * @return array Associative array of importable field definitions.
   */
  public static function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['report_instance'] = &$fields[$name];
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
   * Returns the list of fields marked as exportable.
   *
   * @param bool $prefix If TRUE, keys the result under 'report_instance' instead of field name.
   *
   * @return array Associative array of exportable field definitions.
   */
  public static function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = &self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['report_instance'] = &$fields[$name];
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
