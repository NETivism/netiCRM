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
class CRM_Contact_DAO_Contact extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_contact';
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
   * Unique Contact ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Type of Contact.
   *
   * @var string
   */
  public $contact_type;
  /**
   * May be used to over-ride contact view and edit templates.
   *
   * @var string
   */
  public $contact_sub_type;
  /**
   *
   * @var boolean
   */
  public $do_not_email;
  /**
   *
   * @var boolean
   */
  public $do_not_phone;
  /**
   *
   * @var boolean
   */
  public $do_not_mail;
  /**
   *
   * @var boolean
   */
  public $do_not_sms;
  /**
   *
   * @var boolean
   */
  public $do_not_trade;
  /**
   * Has the contact opted out from receiving all bulk email from the organization or site domain?
   *
   * @var boolean
   */
  public $is_opt_out;
  /**
   * May be used for SSN, EIN/TIN, Household ID (census) or other applicable unique legal/government ID.
   *
   * @var string
   */
  public $legal_identifier;
  /**
   * Unique trusted external ID (generally from a legacy app/datasource). Particularly useful for deduping operations.
   *
   * @var string
   */
  public $external_identifier;
  /**
   * Name used for sorting different contact types
   *
   * @var string
   */
  public $sort_name;
  /**
   * Formatted name representing preferred format for display/print/other output.
   *
   * @var string
   */
  public $display_name;
  /**
   * Nick Name.
   *
   * @var string
   */
  public $nick_name;
  /**
   * Legal Name.
   *
   * @var string
   */
  public $legal_name;
  /**
   * optional URL for preferred image (photo, logo, etc.) to display for this contact.
   *
   * @var string
   */
  public $image_URL;
  /**
   * What is the preferred mode of communication.
   *
   * @var string
   */
  public $preferred_communication_method;
  /**
   * Which language is preferred for communication. FK to languages in civicrm_option_value.
   *
   * @var string
   */
  public $preferred_language;
  /**
   * What is the preferred mode of sending an email.
   *
   * @var enum('Text', 'HTML', 'Both')
   */
  public $preferred_mail_format;
  /**
   * Key for validating requests related to this contact.
   *
   * @var string
   */
  public $hash;
  /**
   * API Key for validating requests related to this contact.
   *
   * @var string
   */
  public $api_key;
  /**
   * where contact come from, e.g. import, donate module insert...
   *
   * @var string
   */
  public $source;
  /**
   * First Name.
   *
   * @var string
   */
  public $first_name;
  /**
   * Middle Name.
   *
   * @var string
   */
  public $middle_name;
  /**
   * Last Name.
   *
   * @var string
   */
  public $last_name;
  /**
   * Prefix or Title for name (Ms, Mr...). FK to prefix ID
   *
   * @var int unsigned
   */
  public $prefix_id;
  /**
   * Suffix for name (Jr, Sr...). FK to suffix ID
   *
   * @var int unsigned
   */
  public $suffix_id;
  /**
   * FK to civicrm_option_value.id, that has to be valid registered Email Greeting.
   *
   * @var int unsigned
   */
  public $email_greeting_id;
  /**
   * Custom Email Greeting.
   *
   * @var string
   */
  public $email_greeting_custom;
  /**
   * Cache Email Greeting.
   *
   * @var string
   */
  public $email_greeting_display;
  /**
   * FK to civicrm_option_value.id, that has to be valid registered Postal Greeting.
   *
   * @var int unsigned
   */
  public $postal_greeting_id;
  /**
   * Custom Postal greeting.
   *
   * @var string
   */
  public $postal_greeting_custom;
  /**
   * Cache Postal greeting.
   *
   * @var string
   */
  public $postal_greeting_display;
  /**
   * FK to civicrm_option_value.id, that has to be valid registered Addressee.
   *
   * @var int unsigned
   */
  public $addressee_id;
  /**
   * Custom Addressee.
   *
   * @var string
   */
  public $addressee_custom;
  /**
   * Cache Addressee.
   *
   * @var string
   */
  public $addressee_display;
  /**
   * Job Title
   *
   * @var string
   */
  public $job_title;
  /**
   * FK to gender ID
   *
   * @var int unsigned
   */
  public $gender_id;
  /**
   * Date of birth
   *
   * @var date
   */
  public $birth_date;
  /**
   *
   * @var boolean
   */
  public $is_deceased;
  /**
   * Date of deceased
   *
   * @var date
   */
  public $deceased_date;
  /**
   * Household Name.
   *
   * @var string
   */
  public $household_name;
  /**
   * Optional FK to Primary Contact for this household.
   *
   * @var int unsigned
   */
  public $primary_contact_id;
  /**
   * Organization Name.
   *
   * @var string
   */
  public $organization_name;
  /**
   * Standard Industry Classification Code.
   *
   * @var string
   */
  public $sic_code;
  /**
   * the OpenID (or OpenID-style http://username.domain/) unique identifier for this contact mainly used for logging in to CiviCRM
   *
   * @var string
   */
  public $user_unique_id;
  /**
   * OPTIONAL FK to civicrm_contact record.
   *
   * @var int unsigned
   */
  public $employer_id;
  /**
   *
   * @var boolean
   */
  public $is_deleted;
  /**
   * When was the contact was created.
   *
   * @var datetime
   */
  public $created_date;
  /**
   * When was the contact (or closely related entity) was created or modified or deleted.
   *
   * @var datetime
   */
  public $modified_date;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_contact
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
        'primary_contact_id' => 'civicrm_contact:id',
        'employer_id' => 'civicrm_contact:id',
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
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'primary_contact_id', 'civicrm_contact', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName() , 'employer_id', 'civicrm_contact', 'id');
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
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Internal Contact ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_contact.id',
          'headerPattern' => '/internal|contact?|id$/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'contact_type' => array(
          'name' => 'contact_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contact Type') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'export' => true,
          'where' => 'civicrm_contact.contact_type',
          'headerPattern' => '',
          'dataPattern' => '',
          'usage' => 'System',
        ) ,
        'contact_sub_type' => array(
          'name' => 'contact_sub_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Contact Subtype') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_contact.contact_sub_type',
          'headerPattern' => '/C(ontact )?(subtype|sub-type|sub type)/i',
          'dataPattern' => '',
          'export' => true,
          'usage' => 'System',
        ) ,
        'do_not_email' => array(
          'name' => 'do_not_email',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Do Not Email') ,
          'import' => true,
          'where' => 'civicrm_contact.do_not_email',
          'headerPattern' => '/d(o )?(not )?(email)/i',
          'dataPattern' => '/^\d{1,}$/',
          'export' => true,
        ) ,
        'do_not_phone' => array(
          'name' => 'do_not_phone',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Do Not Phone') ,
          'import' => true,
          'where' => 'civicrm_contact.do_not_phone',
          'headerPattern' => '/d(o )?(not )?(call|phone)/i',
          'dataPattern' => '/^\d{1,}$/',
          'export' => true,
        ) ,
        'do_not_mail' => array(
          'name' => 'do_not_mail',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Do Not Mail') ,
          'import' => true,
          'where' => 'civicrm_contact.do_not_mail',
          'headerPattern' => '/^(d(o\s)?n(ot\s)?mail)|(\w*)?bulk\s?(\w*)$/i',
          'dataPattern' => '/^\d{1,}$/',
          'export' => true,
        ) ,
        'do_not_sms' => array(
          'name' => 'do_not_sms',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Do Not Sms') ,
          'import' => true,
          'where' => 'civicrm_contact.do_not_sms',
          'headerPattern' => '/d(o )?(not )?(sms)/i',
          'dataPattern' => '/^\d{1,}$/',
          'export' => true,
        ) ,
        'do_not_trade' => array(
          'name' => 'do_not_trade',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Do Not Trade') ,
          'import' => true,
          'where' => 'civicrm_contact.do_not_trade',
          'headerPattern' => '/d(o )?(not )?(trade)/i',
          'dataPattern' => '/^\d{1,}$/',
          'export' => true,
        ) ,
        'is_opt_out' => array(
          'name' => 'is_opt_out',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('No Bulk Emails (User Opt Out)') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_contact.is_opt_out',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'legal_identifier' => array(
          'name' => 'legal_identifier',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Legal Identifier') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'import' => true,
          'where' => 'civicrm_contact.legal_identifier',
          'headerPattern' => '/legal\s?id/i',
          'dataPattern' => '/\w+?\d{5,}/',
          'export' => true,
        ) ,
        'external_identifier' => array(
          'name' => 'external_identifier',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('External Identifier') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.external_identifier',
          'headerPattern' => '/external\s?id/i',
          'dataPattern' => '/^\d{11,}$/',
          'export' => true,
          'usage' => 'System',
        ) ,
        'sort_name' => array(
          'name' => 'sort_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Sort Name') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'export' => true,
          'where' => 'civicrm_contact.sort_name',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'display_name' => array(
          'name' => 'display_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Display Name') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'export' => true,
          'where' => 'civicrm_contact.display_name',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'nick_name' => array(
          'name' => 'nick_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Nick Name') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.nick_name',
          'headerPattern' => '/n(ick\s)name|nick$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'legal_name' => array(
          'name' => 'legal_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Legal Name') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.legal_name',
          'headerPattern' => '/^legal|(l(egal\s)?name)$/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'image_URL' => array(
          'name' => 'image_URL',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Image Url') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.image_URL',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'preferred_communication_method' => array(
          'name' => 'preferred_communication_method',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Preferred Communication Method') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.preferred_communication_method',
          'headerPattern' => '/^p(ref\w*\s)?c(omm\w*)|( meth\w*)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'preferred_language' => array(
          'name' => 'preferred_language',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Preferred Language') ,
          'maxlength' => 5,
          'size' => CRM_Utils_Type::EIGHT,
          'import' => true,
          'where' => 'civicrm_contact.preferred_language',
          'headerPattern' => '/^lang/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'preferred_mail_format' => array(
          'name' => 'preferred_mail_format',
          'type' => CRM_Utils_Type::T_ENUM,
          'title' => ts('Preferred Mail Format') ,
          'import' => true,
          'where' => 'civicrm_contact.preferred_mail_format',
          'headerPattern' => '/^p(ref\w*\s)?m(ail\s)?f(orm\w*)$/i',
          'dataPattern' => '',
          'export' => true,
          'default' => 'Both',
          'enumValues' => 'Text, HTML, Both',
        ) ,
        'hash' => array(
          'name' => 'hash',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Hash') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'api_key' => array(
          'name' => 'api_key',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Api Key') ,
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
        ) ,
        'contact_source' => array(
          'name' => 'source',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Source of Contact Data') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.source',
          'headerPattern' => '/(S(ource\s)?o(f\s)?C(ontact\s)?Data)$/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'first_name' => array(
          'name' => 'first_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('First Name') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_contact.first_name',
          'headerPattern' => '/^first|(f(irst\s)?name)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'middle_name' => array(
          'name' => 'middle_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Middle Name') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_contact.middle_name',
          'headerPattern' => '/^middle|(m(iddle\s)?name)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'last_name' => array(
          'name' => 'last_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Last Name') ,
          'maxlength' => 64,
          'size' => CRM_Utils_Type::BIG,
          'import' => true,
          'where' => 'civicrm_contact.last_name',
          'headerPattern' => '/^last|(l(ast\s)?name)$/i',
          'dataPattern' => '/^\w+(\s\w+)?+$/',
          'export' => true,
        ) ,
        'prefix_id' => array(
          'name' => 'prefix_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'suffix_id' => array(
          'name' => 'suffix_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'email_greeting_id' => array(
          'name' => 'email_greeting_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'email_greeting_custom' => array(
          'name' => 'email_greeting_custom',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Email Greeting Custom') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.email_greeting_custom',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'email_greeting_display' => array(
          'name' => 'email_greeting_display',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Email Greeting') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'postal_greeting_id' => array(
          'name' => 'postal_greeting_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'postal_greeting_custom' => array(
          'name' => 'postal_greeting_custom',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Postal Greeting Custom') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.postal_greeting_custom',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'postal_greeting_display' => array(
          'name' => 'postal_greeting_display',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Postal Greeting') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'addressee_id' => array(
          'name' => 'addressee_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'addressee_custom' => array(
          'name' => 'addressee_custom',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Addressee Custom') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.addressee_custom',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'addressee_display' => array(
          'name' => 'addressee_display',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Addressee') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'job_title' => array(
          'name' => 'job_title',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Job Title') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.job_title',
          'headerPattern' => '/^job|(j(ob\s)?title)$/i',
          'dataPattern' => '//',
          'export' => true,
        ) ,
        'gender_id' => array(
          'name' => 'gender_id',
          'type' => CRM_Utils_Type::T_INT,
        ) ,
        'birth_date' => array(
          'name' => 'birth_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Birth Date') ,
          'import' => true,
          'where' => 'civicrm_contact.birth_date',
          'headerPattern' => '/^birth|(b(irth\s)?date)|D(\W*)O(\W*)B(\W*)$/i',
          'dataPattern' => '/\d{4}-?\d{2}-?\d{2}/',
          'export' => true,
        ) ,
        'is_deceased' => array(
          'name' => 'is_deceased',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Deceased') ,
          'import' => true,
          'where' => 'civicrm_contact.is_deceased',
          'headerPattern' => '/i(s\s)?d(eceased)$/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'deceased_date' => array(
          'name' => 'deceased_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Deceased Date') ,
          'import' => true,
          'where' => 'civicrm_contact.deceased_date',
          'headerPattern' => '/^deceased|(d(eceased\s)?date)$/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'household_name' => array(
          'name' => 'household_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Household Name') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.household_name',
          'headerPattern' => '/^household|(h(ousehold\s)?name)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'primary_contact_id' => array(
          'name' => 'primary_contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'organization_name' => array(
          'name' => 'organization_name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Organization Name') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_contact.organization_name',
          'headerPattern' => '/^organization|(o(rganization\s)?name)$/i',
          'dataPattern' => '/^\w+$/',
          'export' => true,
        ) ,
        'sic_code' => array(
          'name' => 'sic_code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Sic Code') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
          'import' => true,
          'where' => 'civicrm_contact.sic_code',
          'headerPattern' => '/^sic|(s(ic\s)?code)$/i',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'user_unique_id' => array(
          'name' => 'user_unique_id',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Unique ID (OpenID)') ,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
          'rule' => 'url',
        ) ,
        'current_employer_id' => array(
          'name' => 'employer_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Current Employer ID') ,
          'export' => true,
          'where' => 'civicrm_contact.employer_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'contact_is_deleted' => array(
          'name' => 'is_deleted',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Contact is in Trash') ,
          'required' => true,
          'export' => true,
          'where' => 'civicrm_contact.is_deleted',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'contact_created_date' => array(
          'name' => 'created_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Contact Created Date') ,
          'required' => false,
          'import' => true,
          'where' => 'civicrm_contact.created_date',
          'headerPattern' => '/created(.?date)?/i',
          'dataPattern' => '/^\d{4}-?\d{2}-?\d{2} ?(\d{2}:?\d{2}:?(\d{2})?)?$/',
          'export' => true,
          'default' => 'URRENT_TIMESTAM',
          'usage' => 'System',
        ) ,
        'contact_modified_date' => array(
          'name' => 'modified_date',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => ts('Contact Modified Date') ,
          'required' => false,
          'default' => 'URRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAM',
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
      self::$_import = array();
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['contact'] = &$fields[$name];
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
      self::$_export = array();
      $fields = &self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['contact'] = &$fields[$name];
          } else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
  /**
   * returns an array containing the enum fields of the civicrm_contact table
   *
   * @return array (reference)  the array of enum fields
   */
  static function &getEnums()
  {
    static $enums = array(
      'preferred_mail_format',
    );
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
      $translations = array(
        'preferred_mail_format' => array(
          'Text' => ts('Text') ,
          'HTML' => ts('HTML') ,
          'Both' => ts('Both') ,
        ) ,
      );
    }
    return $translations[$field][$value];
  }
  /**
   * adds $value['foo_display'] for each $value['foo'] enum from civicrm_contact
   *
   * @param array $values (reference)  the array up for enhancing
   * @return void
   */
  static function addDisplayEnums(&$values)
  {
    $enumFields = &CRM_Contact_DAO_Contact::getEnums();
    foreach($enumFields as $enum) {
      if (isset($values[$enum])) {
        $values[$enum . '_display'] = CRM_Contact_DAO_Contact::tsEnum($enum, $values[$enum]);
      }
    }
  }
}
