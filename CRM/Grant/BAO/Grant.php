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


class CRM_Grant_BAO_Grant extends CRM_Grant_DAO_Grant {

  /**
   * the name of option value group from civicrm_option_group table
   * that stores grant statuses
   */
  static $statusGroupName = 'grant_status';

  /**
   * the name of option value group from civicrm_option_group table
   * that stores grant statuses
   */
  static $typeGroupName = 'grant_type';

  /**
   * static field for all the grant information that we can potentially export
   * @var array
   * @static
   */
  static $_exportableFields = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Function to get events Summary
   *
   * @static
   *
   * @return array Array of event summary values
   */
  static function getGrantSummary($admin = FALSE) {
    $query = "
            SELECT status_id, count(id) as status_total 
            FROM civicrm_grant  GROUP BY status_id";

    $dao = &CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);


    $status = [];
    $summary = [];
    $summary['total_grants'] = NULL;
    $status = CRM_Grant_PseudoConstant::grantStatus();

    foreach ($status as $id => $name) {
      $stats[$id] = ['label' => $name,
        'total' => 0,
      ];
    }

    while ($dao->fetch()) {
      $stats[$dao->status_id] = ['label' => $status[$dao->status_id],
        'total' => $dao->status_total,
      ];
      $summary['total_grants'] += $dao->status_total;
    }

    $summary['per_status'] = $stats;
    return $summary;
  }

  /**
   * Function to get events Summary
   *
   * @static
   *
   * @return array Array of event summary values
   */
  static function getGrantStatusOptGroup() {


    $params = [];
    $params['name'] = CRM_Grant_BAO_Grant::$statusGroupName;

    $defaults = [];

    $bao = new CRM_Core_BAO_OptionGroup();
    $og = $bao->retrieve($params, $defaults);

    if (!$og) {
      CRM_Core_Error::fatal('No option group for grant statuses - database discrepancy! Make sure you loaded civicrm_data.mysql');
    }

    return $og;
  }

  static function getGrantStatuses() {
    $og = CRM_Grant_BAO_Grant::getGrantStatusOptGroup();


    $dao = new CRM_Core_DAO_OptionValue();

    $dao->option_group_id = $og->id;
    $dao->find();

    $statuses = [];

    while ($dao->fetch()) {
      $statuses[$dao->id] = $dao->label;
    }

    return $statuses;
  }

  /**
   * Function to retrieve grant types.
   *
   * @static
   *
   * @return array Array of grant summary statistics
   */
  static function getGrantTypes() {

    return CRM_Core_OptionGroup::values(CRM_Grant_BAO_Grant::$typeGroupName);
  }

  /**
   * Function to retrieve statistics for grants.
   *
   * @static
   *
   * @return array Array of grant summary statistics
   */
  static function getGrantStatistics($admin = FALSE) {
    $grantStatuses = [];
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Grant_BAO_ManageGrant object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $grant = new CRM_Grant_DAO_Grant();
    $grant->copyValues($params);
    if ($grant->find(TRUE)) {
      CRM_Core_DAO::storeValues($grant, $defaults);
      return $grant;
    }
    return NULL;
  }

  /**
   * function to add grant
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params, &$ids) {


    if (CRM_Utils_Array::value('grant', $ids)) {
      CRM_Utils_Hook::pre('edit', 'Grant', $ids['grant_id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Grant', NULL, $params);
    }

    // first clean up all the money fields
    $moneyFields = ['amount_total',
      'amount_granted',
      'amount_requested',
    ];
    foreach ($moneyFields as $field) {
      if (isset($params[$field])) {
        $params[$field] = CRM_Utils_Rule::cleanMoney($params[$field]);
      }
    }
    $grant = new CRM_Grant_DAO_Grant();
    $grant->id = CRM_Utils_Array::value('grant', $ids);

    $grant->copyValues($params);

    // set currency for CRM-1496
    if (!isset($grant->currency)) {
      $config = &CRM_Core_Config::singleton();
      $grant->currency = $config->defaultCurrency;
    }

    $result = $grant->save();




    $url = CRM_Utils_System::url('civicrm/contact/view/grant',
      "action=view&reset=1&id={$grant->id}&cid={$grant->contact_id}&context=home"
    );

    $grantTypes = CRM_Grant_PseudoConstant::grantType();
    $title = CRM_Contact_BAO_Contact::displayName($grant->contact_id) . ' - ' . ts('Grant') . ': ' . $grantTypes[$grant->grant_type_id];

    $recentOther = [];
    if (CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/grant',
        "action=update&reset=1&id={$grant->id}&cid={$grant->contact_id}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviGrant', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/grant',
        "action=delete&reset=1&id={$grant->id}&cid={$grant->contact_id}&context=home"
      );
    }

    // add the recently created Grant
    CRM_Utils_Recent::add($title,
      $url,
      $grant->id,
      'Grant',
      $grant->contact_id,
      NULL,
      $recentOther
    );

    if (CRM_Utils_Array::value('grant', $ids)) {
      CRM_Utils_Hook::post('edit', 'Grant', $grant->id, $grant);
    }
    else {
      CRM_Utils_Hook::post('create', 'Grant', $grant->id, $grant);
    }

    return $result;
  }

  /**
   * function to create the event
   *
   * @param array $params reference array contains the values submitted by the form
   * @param array $ids    reference array contains the id
   *
   * @access public
   * @static
   *
   */
  public static function create(&$params, &$ids) {

    $transaction = new CRM_Core_Transaction();

    $grant = self::add($params, $ids);

    if (is_a($grant, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $grant;
    }

    $session = &CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (!$id) {
      $id = $params['contact_id'];
    }
    if (CRM_Utils_Array::value('note', $params) || CRM_Utils_Array::value('id', $ids['note'])) {

      $noteParams = [
        'entity_table' => 'civicrm_grant',
        'note' => $params['note'] = $params['note'] ? $params['note'] : "null",
        'entity_id' => $grant->id,
        'contact_id' => $id,
        'modified_date' => date('Ymd'),
      ];

      CRM_Core_BAO_Note::add($noteParams, $ids['note']);
    }
    // Log the information on successful add/edit of Grant

    $logParams = [
      'entity_table' => 'civicrm_grant',
      'entity_id' => $grant->id,
      'modified_id' => $id,
      'modified_date' => date('Ymd'),
    ];

    CRM_Core_BAO_Log::add($logParams);

    // add custom field values
    if (CRM_Utils_Array::value('custom', $params) && is_array($params['custom'])) {

      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_grant', $grant->id);
    }

    // check and attach and files as needed

    CRM_Core_BAO_File::processAttachment($params,
      'civicrm_grant',
      $grant->id
    );

    $transaction->commit();

    return $grant;
  }

  /**
   * Function to delete the Contact
   *
   * @param int $cid  contact id
   *
   * @access public
   * @static
   *
   */
  static function deleteContact($id) {

    $grant = new CRM_Grant_DAO_Grant();
    $grant->contact_id = $id;
    $grant->delete();
    return FALSE;
  }

  /**
   * Function to delete the grant
   *
   * @param int $id  grant id
   *
   * @access public
   * @static
   *
   */
  static function del($id) {

    $grant = new CRM_Grant_DAO_Grant();
    $grant->id = $id;

    $grant->find();

    // delete the recently created Grant

    $grantRecent = [
      'id' => $id,
      'type' => 'Grant',
    ];
    CRM_Utils_Recent::del($grantRecent);

    while ($grant->fetch()) {
      return $grant->delete();
    }
    return FALSE;
  }

  /**
   * combine all the exportable fields from the lower levels object
   *
   * @return array array of exportable Fields
   * @access public
   */
  static function &exportableFields() {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }

      $grantFields = ['grant_status' => [
          'title' => 'Grant Status',
          'name' => 'grant_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
        'grant_type' => [
          'title' => 'Grant Type',
          'name' => 'grant_type',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
        'grant_money_transfer_date' => [
          'title' => 'Grant Money Transfer Date',
          'name' => 'grant_money_transfer_date',
          'data_type' => CRM_Utils_Type::T_DATE,
        ],
        'grant_amount_requested' => [
          'title' => 'Grant Amount Requested',
          'name' => 'grant_amount_requested',
          'data_type' => CRM_Utils_Type::T_FLOAT,
        ],
        'grant_application_received_date' => [
          'title' => 'Grant Application Recieved Date',
          'name' => 'grant_application_received_date',
          'data_type' => CRM_Utils_Type::T_DATE,
        ],
      ];


      $fields = CRM_Grant_DAO_Grant::export();
      $grantNote = ['grant_note' => ['title' => ts('Grant Note'),
          'name' => 'grant_note',
          'data_type' => CRM_Utils_Type::T_TEXT,
        ]];
      $fields = array_merge($fields, $grantFields, $grantNote,
        CRM_Core_BAO_CustomField::getFieldsForImport('Grant')
      );
      self::$_exportableFields = $fields;
    }

    return self::$_exportableFields;
  }

  /**
   * Function to get grant record count for a Contact
   *
   * @param int $contactId Contact ID
   *
   * @return int count of grant records
   * @access public
   * @static
   */
  static function getContactGrantCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_grant WHERE civicrm_grant.contact_id = {$contactID} ";
    return CRM_Core_DAO::singleValueQuery($query);
  }
}

