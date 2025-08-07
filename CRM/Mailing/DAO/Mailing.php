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
                                                        class CRM_Mailing_DAO_Mailing extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_mailing';
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
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Which site is this mailing for
   *
   * @var int unsigned
   */
  public $domain_id;
  /**
   * FK to the header component.
   *
   * @var int unsigned
   */
  public $header_id;
  /**
   * FK to the footer component.
   *
   * @var int unsigned
   */
  public $footer_id;
  /**
   * FK to the auto-responder component.
   *
   * @var int unsigned
   */
  public $reply_id;
  /**
   * FK to the unsubscribe component.
   *
   * @var int unsigned
   */
  public $unsubscribe_id;
  /**
   *
   * @var int unsigned
   */
  public $resubscribe_id;
  /**
   * FK to the opt-out component.
   *
   * @var int unsigned
   */
  public $optout_id;
  /**
   * Mailing Name.
   *
   * @var string
   */
  public $name;
  /**
   * From Header of mailing
   *
   * @var string
   */
  public $from_name;
  /**
   * From Email of mailing
   *
   * @var string
   */
  public $from_email;
  /**
   * Reply-To Email of mailing
   *
   * @var string
   */
  public $replyto_email;
  /**
   * Subject of mailing
   *
   * @var string
   */
  public $subject;
  /**
   * Preview of body on mail listing page.
   *
   * @var string
   */
  public $body_preview;
  /**
   * Body of the mailing in text format.
   *
   * @var longtext
   */
  public $body_text;
  /**
   * Body of the mailing in html format.
   *
   * @var longtext
   */
  public $body_html;
  /**
   * Body of the mailing in json format.
   *
   * @var longtext
   */
  public $body_json;
  /**
   * Should we track URL click-throughs for this mailing?
   *
   * @var boolean
   */
  public $url_tracking;
  /**
   * Should we forward replies back to the author?
   *
   * @var boolean
   */
  public $forward_replies;
  /**
   * Should we enable the auto-responder?
   *
   * @var boolean
   */
  public $auto_responder;
  /**
   * Should we track when recipients open/read this mailing?
   *
   * @var boolean
   */
  public $open_tracking;
  /**
   * Has at least one job associated with this mailing finished?
   *
   * @var boolean
   */
  public $is_completed;
  /**
   * FK to the message template.
   *
   * @var int unsigned
   */
  public $msg_template_id;
  /**
   * Should we overrite VERP address in Reply-To
   *
   * @var boolean
   */
  public $override_verp;
  /**
   * FK to Contact ID who first created this mailing
   *
   * @var int unsigned
   */
  public $created_id;
  /**
   * Date and time this mailing was created.
   *
   * @var datetime
   */
  public $created_date;
  /**
   * FK to Contact ID who scheduled this mailing
   *
   * @var int unsigned
   */
  public $scheduled_id;
  /**
   * Date and time this mailing was scheduled.
   *
   * @var datetime
   */
  public $scheduled_date;
  /**
   * Is this mailing archived?
   *
   * @var boolean
   */
  public $is_archived;
  /**
   * In what context(s) is the mailing contents visible (online viewing)
   *
   * @var enum('User and User Admin Only', 'Public Pages')
   */
  public $visibility;
  /**
   * Remove duplicate emails?
   *
   * @var boolean
   */
  public $dedupe_email;
  /**
   *
   * @var int unsigned
   */
  public $sms_provider_id;
  /**
   * Is this mailing hidden?
   *
   * @var boolean
   */
  public $is_hidden;
   /**
   * class constructor
   *
   * @access public
   * @return civicrm_mailing
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
        'domain_id' => 'civicrm_domain:id',
        'header_id' => 'civicrm_mailing_component:id',
        'footer_id' => 'civicrm_mailing_component:id',
        'reply_id' => 'civicrm_mailing_component:id',
        'unsubscribe_id' => 'civicrm_mailing_component:id',
        'optout_id' => 'civicrm_mailing_component:id',
        'msg_template_id' => 'civicrm_msg_template:id',
        'created_id' => 'civicrm_contact:id',
        'scheduled_id' => 'civicrm_contact:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'domain_id', 'civicrm_domain', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'header_id', 'civicrm_mailing_component', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'footer_id', 'civicrm_mailing_component', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'reply_id', 'civicrm_mailing_component', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'unsubscribe_id', 'civicrm_mailing_component', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'optout_id', 'civicrm_mailing_component', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'msg_template_id', 'civicrm_msg_template', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'created_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'scheduled_id', 'civicrm_contact', 'id');
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
        'domain_id' => [
          'name' => 'domain_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Core_DAO_Domain',
        ] ,
        'header_id' => [
          'name' => 'header_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Mailing_DAO_Component',
        ] ,
        'footer_id' => [
          'name' => 'footer_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Mailing_DAO_Component',
        ] ,
        'reply_id' => [
          'name' => 'reply_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Mailing_DAO_Component',
        ] ,
        'unsubscribe_id' => [
          'name' => 'unsubscribe_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Mailing_DAO_Component',
        ] ,
        'resubscribe_id' => [
          'name' => 'resubscribe_id',
          'type' => CRM_Utils_Type::T_INT,
                  ] ,
        'optout_id' => [
          'name' => 'optout_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Mailing_DAO_Component',
        ] ,
        'name' => [
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Name') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'from_name' => [
          'name' => 'from_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('From Name') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'from_email' => [
          'name' => 'from_email',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('From Email') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'replyto_email' => [
          'name' => 'replyto_email',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Replyto Email') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'subject' => [
          'name' => 'subject',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Subject') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'body_preview' => [
          'name' => 'body_preview',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Body Preview') ,
           'maxlength' => 128,
           'size' => CRM_Utils_Type::HUGE,
                ] ,
        'body_text' => [
          'name' => 'body_text',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Body Text') ,
                  ] ,
        'body_html' => [
          'name' => 'body_html',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Body Html') ,
                  ] ,
        'body_json' => [
          'name' => 'body_json',
          'type' => CRM_Utils_Type::T_LONGTEXT,
          'title' => ts('Body Json') ,
                  ] ,
        'url_tracking' => [
          'name' => 'url_tracking',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Url Tracking') ,
                  ] ,
        'forward_replies' => [
          'name' => 'forward_replies',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Forward Replies') ,
                  ] ,
        'auto_responder' => [
          'name' => 'auto_responder',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Auto Responder') ,
                  ] ,
        'open_tracking' => [
          'name' => 'open_tracking',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Open Tracking') ,
                  ] ,
        'is_completed' => [
          'name' => 'is_completed',
          'type' => CRM_Utils_Type::T_BOOLEAN,
                  ] ,
        'msg_template_id' => [
          'name' => 'msg_template_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Core_DAO_MessageTemplates',
        ] ,
        'override_verp' => [
          'name' => 'override_verp',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Override Verp') ,
                  ] ,
        'created_id' => [
          'name' => 'created_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Contact_DAO_Contact',
        ] ,
        'created_date' => [
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Mailing Created Date') ,
                  ] ,
        'scheduled_id' => [
          'name' => 'scheduled_id',
          'type' => CRM_Utils_Type::T_INT,
                    'FKClassName' => 'CRM_Contact_DAO_Contact',
        ] ,
        'scheduled_date' => [
          'name' => 'scheduled_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Mailing Scheduled Date') ,
                  ] ,
        'is_archived' => [
          'name' => 'is_archived',
          'type' => CRM_Utils_Type::T_BOOLEAN,
                  ] ,
        'visibility' => [
          'name' => 'visibility',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Visibility') ,
                  'default' => 'User and User Admin Only',
           'enumValues' => 'User and User Admin Only,Public Pages',
         ] ,
        'dedupe_email' => [
          'name' => 'dedupe_email',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Dedupe Email') ,
                  ] ,
        'sms_provider_id' => [
          'name' => 'sms_provider_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Mailing SMS Provider') ,
                  ] ,
        'is_hidden' => [
          'name' => 'is_hidden',
          'type' => CRM_Utils_Type::T_BOOLEAN,
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
            self::$_import['mailing'] = &$fields[$name];
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
            self::$_export['mailing'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
                                                                                                                                  }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_mailing table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = [
                                                                                                                                                                                                                                                                                                                                                                                'visibility',
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
                                                                                                                                                                                                                                                                                                                                                                                'visibility' => [
                  'User and User Admin Only' => ts('User and User Admin Only'),
                  'Public Pages' => ts('Public Pages'),
                ],
                                                      ];
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_mailing
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Mailing_DAO_Mailing::getEnums();
    foreach ($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Mailing_DAO_Mailing::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
