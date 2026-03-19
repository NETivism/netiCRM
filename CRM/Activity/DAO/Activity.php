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

class CRM_Activity_DAO_Activity extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  public static $_tableName = 'civicrm_activity';
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
   * Unique  Other Activity ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Contact ID of the person scheduling or logging this Activity. Usually the authenticated user.
   *
   * @var int unsigned
   */
  public $source_contact_id;
  /**
   * Artificial FK to original transaction (e.g. contribution) IF it is not an Activity. Table can be figured out through activity_type_id, and further through component registry.
   *
   * @var int unsigned
   */
  public $source_record_id;
  /**
   * FK to civicrm_option_value.id, that has to be valid, registered activity type.
   *
   * @var int unsigned
   */
  public $activity_type_id;
  /**
   * The subject/purpose/short description of the activity.
   *
   * @var string
   */
  public $subject;
  /**
   * Date and time this activity is scheduled to occur. Formerly named scheduled_date_time.
   *
   * @var datetime
   */
  public $activity_date_time;
  /**
   * Planned or actual duration of activity expressed in minutes. Conglomerate of former duration_hours and duration_minutes.
   *
   * @var int unsigned
   */
  public $duration;
  /**
   * Location of the activity (optional, open text).
   *
   * @var string
   */
  public $location;
  /**
   * Phone ID of the number called (optional - used if an existing phone number is selected).
   *
   * @var int unsigned
   */
  public $phone_id;
  /**
   * Phone number in case the number does not exist in the civicrm_phone table.
   *
   * @var string
   */
  public $phone_number;
  /**
   * Details about the activity (agenda, notes, etc).
   *
   * @var text
   */
  public $details;
  /**
   * ID of the status this activity is currently in. Foreign key to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * ID of the priority given to this activity. Foreign key to civicrm_option_value.
   *
   * @var int unsigned
   */
  public $priority_id;
  /**
   * Parent meeting ID (if this is a follow-up item). This is not currently implemented
   *
   * @var int unsigned
   */
  public $parent_id;
  /**
   *
   * @var boolean
   */
  public $is_test;
  /**
   * Activity Medium, Implicit FK to civicrm_option_value where option_group = encounter_medium.
   *
   * @var int unsigned
   */
  public $medium_id;
  /**
   *
   * @var boolean
   */
  public $is_auto;
  /**
   * FK to Relationship ID
   *
   * @var int unsigned
   */
  public $relationship_id;
  /**
   *
   * @var boolean
   */
  public $is_current_revision;
  /**
   * Activity ID of the first activity record in versioning chain.
   *
   * @var int unsigned
   */
  public $original_id;
  /**
   * Currently being used to store result id for survey activity, FK to option value.
   *
   * @var string
   */
  public $result;
  /**
   *
   * @var boolean
   */
  public $is_deleted;
  /**
   * class constructor
   *
   * @return civicrm_activity
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
        'source_contact_id' => 'civicrm_contact:id',
        'phone_id' => 'civicrm_phone:id',
        'parent_id' => 'civicrm_activity:id',
        'relationship_id' => 'civicrm_relationship:id',
        'original_id' => 'civicrm_activity:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'source_contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'phone_id', 'civicrm_phone', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'parent_id', 'civicrm_activity', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'relationship_id', 'civicrm_relationship', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'original_id', 'civicrm_activity', 'id');
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
        'activity_id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Activity ID') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_activity.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'source_contact_id' => [
          'name' => 'source_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Source Contact') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.source_contact_id',
          'headerPattern' => '/(activity.)?source(.contact(.id)?)?/i',
          'dataPattern' => '',
          'export' => TRUE,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ],
        'source_record_id' => [
          'name' => 'source_record_id',
          'type' => CRM_Utils_Type::T_INT,
        ],
        'activity_type_id' => [
          'name' => 'activity_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Activity Type ID') ,
          'required' => TRUE,
          'import' => TRUE,
          'where' => 'civicrm_activity.activity_type_id',
          'headerPattern' => '/(activity.)?type(.id$)/i',
          'dataPattern' => '',
          'export' => FALSE,
          'default' => '',
        ],
        'activity_subject' => [
          'name' => 'subject',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Subject') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => TRUE,
          'where' => 'civicrm_activity.subject',
          'headerPattern' => '/(activity.)?subject/i',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'activity_date_time' => [
          'name' => 'activity_date_time',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Activity Date') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.activity_date_time',
          'headerPattern' => '/(activity.)?date(.time$)?/i',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'activity_duration' => [
          'name' => 'duration',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Duration') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.duration',
          'headerPattern' => '/(activity.)?duration(s)?$/i',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'activity_location' => [
          'name' => 'location',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Location') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => TRUE,
          'where' => 'civicrm_activity.location',
          'headerPattern' => '/(activity.)?location$/i',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'phone_id' => [
          'name' => 'phone_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Core_DAO_Phone',
        ],
        'phone_number' => [
          'name' => 'phone_number',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Phone Number') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
        ],
        'activity_details' => [
          'name' => 'details',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Details') ,
          'rows' => 8,
          'cols' => 60,
          'import' => TRUE,
          'where' => 'civicrm_activity.details',
          'headerPattern' => '/(activity.)?detail(s)?$/i',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'activity_status_id' => [
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Activity Status Id') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.status_id',
          'headerPattern' => '/(activity.)?status(.label$)?/i',
          'dataPattern' => '',
          'export' => FALSE,
        ],
        'priority_id' => [
          'name' => 'priority_id',
          'type' => CRM_Utils_Type::T_INT,
        ],
        'parent_id' => [
          'name' => 'parent_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Activity_DAO_Activity',
        ],
        'activity_is_test' => [
          'name' => 'is_test',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Test') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.is_test',
          'headerPattern' => '/(is.)?test(.activity)?/i',
          'dataPattern' => '',
          'export' => TRUE,
        ],
        'activity_medium_id' => [
          'name' => 'medium_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Activity Medium') ,
          'default' => 'UL',
        ],
        'is_auto' => [
          'name' => 'is_auto',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Auto') ,
        ],
        'relationship_id' => [
          'name' => 'relationship_id',
          'type' => CRM_Utils_Type::T_INT,
          'default' => 'UL',
          'FKClassName' => 'CRM_Contact_DAO_Relationship',
        ],
        'is_current_revision' => [
          'name' => 'is_current_revision',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is this activity a current revision in versioning chain?') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.is_current_revision',
          'headerPattern' => '/(is.)?(current.)?(revision|version(ing)?)/i',
          'dataPattern' => '',
          'export' => TRUE,
          'default' => '',
        ],
        'original_id' => [
          'name' => 'original_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Activity_DAO_Activity',
        ],
        'activity_result' => [
          'name' => 'result',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Result') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'is_deleted' => [
          'name' => 'is_deleted',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Activity is in the Trash') ,
          'import' => TRUE,
          'where' => 'civicrm_activity.is_deleted',
          'headerPattern' => '/(activity.)?(trash|deleted)/i',
          'dataPattern' => '',
          'export' => TRUE,
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
            self::$_import['activity'] = &$fields[$name];
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
            self::$_export['activity'] = &$fields[$name];
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
