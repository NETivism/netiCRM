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

























class CRM_Contact_BAO_Contact extends CRM_Contact_DAO_Contact {

  /**
   * the types of communication preferences
   *
   * @var array
   */
  static $_commPrefs = ['do_not_phone', 'do_not_email', 'do_not_mail', 'do_not_sms', 'do_not_trade', 'do_not_notify', 'is_opt_out'];

  /**
   * types of greetings
   *
   * @var array
   */
  static $_greetingTypes = ['addressee', 'email_greeting', 'postal_greeting'];

  /**
   * static field for all the contact information that we can potentially import
   *
   * @var array
   * @static
   */
  static $_importableFields = NULL;

  /**
   * static field for all the contact information that we can potentially export
   *
   * @var array
   * @static
   */
  static $_exportableFields = NULL;
  function __construct() {
    parent::__construct();
  }

  /**
   * takes an associative array and creates a contact object
   *
   * the function extract all the params it needs to initialize the create a
   * contact object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Contact_BAO_Contact object
   * @access public
   * @static
   */
  static function add(&$params) {
    $contact = new CRM_Contact_DAO_Contact();

    if (empty($params)) {
      return;
    }

    //fix for validate contact sub type CRM-5143
    $subType = CRM_Utils_Array::value('contact_sub_type', $params);
    if ($subType && !(CRM_Contact_BAO_ContactType::isExtendsContactType($subType, $params['contact_type'], TRUE))) {
      return;
    }

    //fixed contact source
    if (isset($params['contact_source'])) {
      $params['source'] = $params['contact_source'];
    }

    //fix for preferred communication method
    $prefComm = CRM_Utils_Array::value('preferred_communication_method', $params);
    if ($prefComm && is_array($prefComm)) {
      unset($params['preferred_communication_method']);
      $newPref = [];

      foreach ($prefComm as $k => $v) {
        if ($v) {
          $newPref[$k] = $v;
        }
      }

      $prefComm = $newPref;
      if (is_array($prefComm) && !empty($prefComm)) {
        $prefComm = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, array_keys($prefComm)) . CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
        $contact->preferred_communication_method = $prefComm;
      }
      else {
        $contact->preferred_communication_method = '';
      }
    }

    $allNull = $contact->copyValues($params);

    $contact->id = CRM_Utils_Array::value('contact_id', $params);

    if ($contact->contact_type == 'Individual') {
      $allNull = FALSE;

      //format individual fields

      CRM_Contact_BAO_Individual::format($params, $contact);
    }
    elseif ($contact->contact_type == 'Household') {
      if (isset($params['household_name'])) {
        $allNull = FALSE;
        $contact->display_name = $contact->sort_name = CRM_Utils_Array::value('household_name', $params, '');
      }
    }
    elseif ($contact->contact_type == 'Organization') {
      if (isset($params['organization_name'])) {
        $allNull = FALSE;
        $contact->display_name = $contact->sort_name = CRM_Utils_Array::value('organization_name', $params, '');
      }
    }

    // privacy block
    $privacy = CRM_Utils_Array::value('privacy', $params);
    if ($privacy &&
      is_array($privacy) &&
      !empty($privacy)
    ) {
      $allNull = FALSE;
      foreach (self::$_commPrefs as $name) {
        $contact->$name = CRM_Utils_Array::value($name, $privacy, FALSE);
      }
    }

    // since hash was required, make sure we have a 0 value for it, CRM-1063
    // fixed in 1.5 by making hash optional
    // only do this in create mode, not update
    if (empty($contact->hash) && !$contact->id) {
      $allNull = FALSE;
      $contact->hash = md5(uniqid((string)rand(), TRUE));
    }

    if (!$allNull) {
      $contact->modified_date = date('YmdHis');
      $contact->save();
      $message = !empty($params['log_data']) ? $params['log_data'] : ts('Updated contact');
      CRM_Core_BAO_Log::register($contact->id, 'civicrm_contact', $contact->id, NULL, $message);
    }

    if ($contact->contact_type == 'Individual' &&
      (CRM_Utils_Array::arrayKeyExists('current_employer', $params) || CRM_Utils_Array::arrayKeyExists('employer_id', $params))) {
      // create current employer
      if ($params['employer_id']) {
        CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contact->id, $params['employer_id']);
      }
      elseif ($params['current_employer']) {
        CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contact->id, $params['current_employer']);
      }
      else {
        //unset if employer id exits
        if ($employerId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contact->id, 'employer_id')) {
          CRM_Contact_BAO_Contact_Utils::clearCurrentEmployer($contact->id, $employerId);
        }
      }
    }
    //update cached employee name
    elseif ($contact->contact_type == 'Organization') {
      CRM_Contact_BAO_Contact_Utils::updateCurrentEmployer($contact->id);
    }

    return $contact;
  }

  /**
   * Function to create contact
   * takes an associative array and creates a contact object and all the associated
   * derived objects (i.e. individual, location, email, phone etc)
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array   $params      (reference ) an assoc array of name/value pairs
   * @param boolean $fixAddress  if we need to fix address
   * @param boolean $invokeHooks if we need to invoke hooks
   *
   * @return object CRM_Contact_BAO_Contact object
   * @access public
   * @static
   */
  static function &create(&$params, $fixAddress = TRUE, $invokeHooks = TRUE, $skipDelete = FALSE) {
    $contact = NULL;
    if (!CRM_Utils_Array::value('contact_type', $params) &&
      !CRM_Utils_Array::value('contact_id', $params)
    ) {
      return $contact;
    }

    $isEdit = TRUE;
    if ($invokeHooks) {
      if (CRM_Utils_Array::value('contact_id', $params)) {
        CRM_Utils_Hook::pre('edit', $params['contact_type'], $params['contact_id'], $params);
      }
      else {
        CRM_Utils_Hook::pre('create', $params['contact_type'], NULL, $params);
        $isEdit = FALSE;
      }
    }

    $config = &CRM_Core_Config::singleton();

    // CRM-6942: set preferred language to the current language if it’s unset (and we’re creating a contact)
    if ((!isset($params['id']) or !$params['id']) and (!isset($params['preferred_language']) or !$params['preferred_language'])) {
      $params['preferred_language'] = $config->lcMessages;
    }
    // CRM-9739: set greeting & addressee if unset and we’re creating a contact
    if (empty($params['contact_id'])) {
      foreach (self::$_greetingTypes as $greeting) {
        if (empty($params[$greeting . '_id'])) {
          if ($defaultGreetingTypeId =
            CRM_Contact_BAO_Contact_Utils::defaultGreeting($params['contact_type'], $greeting)
          ) {
            $params[$greeting . '_id'] = $defaultGreetingTypeId;
          }
        }
      }
    }

    $transaction = new CRM_Core_Transaction();
    $contact = self::add($params);
    if (is_a($contact, 'CRM_Core_Error') || empty($contact->id)) {
      // fatal error will rollback record
      return CRM_Core_Error::fatal(ts("Data not saved."));
    }
    // refs #22380, trying to solve deadlock
    // The transaction should small to prevent deadlock.
    $transaction->commit();

    $params['contact_id'] = $contact->id;

    if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
      // in order to make sure that every contact must be added to a group (CRM-4613) -
      $domainGroupID = CRM_Core_BAO_Domain::getGroupId();
      if (CRM_Utils_Array::value('group', $params) && is_array($params['group'])) {
        $grpFlp = array_flip($params['group']);
        if (!CRM_Utils_Array::arrayKeyExists(1, $grpFlp)) {
          $params['group'][$domainGroupID] = 1;
        }
      }
      else {
        $params['group'] = [$domainGroupID => 1];
      }
    }

    if (isset($params['group']) && !empty($params['group'])) {
      $contactIds = [$params['contact_id']];
      foreach ($params['group'] as $groupId => $flag) {
        if ($flag == 1) {
          CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId);
        }
        elseif ($flag == -1) {
          CRM_Contact_BAO_GroupContact::removeContactsFromGroup($contactIds, $groupId);
        }
      }
    }

    if (!$config->doNotResetCache) {
      // Note: doNotResetCache flag is currently set by import contact process, since resetting and
      // rebuilding cache could be expensive (for many contacts). We might come out with better
      // approach in future.

      // clear acl cache if any.
      CRM_ACL_BAO_Cache::resetCache();
    }

    // refs #22380, trying to solve deadlock
    // Use READ COMMITTED isolation level to prevent race condition of index lock
    $transaction = new CRM_Core_Transaction('READ COMMITTED');

    // add location Block data
    $blocks = CRM_Core_BAO_Location::create($params, $fixAddress);
    foreach ($blocks as $name => $value) {
      $contact->$name = $value;
    }

    // add website
    CRM_Core_BAO_Website::create($params['website'], $contact->id, $skipDelete);

    // add custom values
    if (CRM_Utils_Array::value('custom', $params) && is_array($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contact', $contact->id);
    }
    $transaction->commit(TRUE);

    //get userID from session
    $session = CRM_Core_Session::singleton();
    $userID = $session->get('userID');

    // add notes
    if (CRM_Utils_Array::value('note', $params)) {
      $transaction = new CRM_Core_Transaction();
      if (is_array($params['note'])) {
        foreach ($params['note'] as $note) {
          $contactId = $contact->id;
          if (isset($note['contact_id'])) {
            $contactId = $note['contact_id'];
          }
          //if logged in user, overwrite contactId
          if ($userID) {
            $contactId = $userID;
          }

          $noteParams = [
            'entity_id' => $contact->id,
            'entity_table' => 'civicrm_contact',
            'note' => $note['note'],
            'subject' => $note['subject'],
            'contact_id' => $contactId,
          ];
          CRM_Core_BAO_Note::add($noteParams, CRM_Core_DAO::$_nullArray);
        }
      }
      else {
        $contactId = $contact->id;
        if (isset($note['contact_id'])) {
          $contactId = $note['contact_id'];
        }
        //if logged in user, overwrite contactId
        if ($userID) {
          $contactId = $userID;
        }

        $noteParams = [
          'entity_id' => $contact->id,
          'entity_table' => 'civicrm_contact',
          'note' => $params['note'],
          'subject' => CRM_Utils_Array::value('subject', $params),
          'contact_id' => $contactId,
        ];
        CRM_Core_BAO_Note::add($noteParams, CRM_Core_DAO::$_nullArray);
      }
      $transaction->commit();
    }

    // CRM-6367: fetch the right label for contact type’s display
    $contact->contact_type_display = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_ContactType', $contact->contact_type, 'label', 'name');

    // #30818, we have serious deadlock issue, should never doing cache reset every contact create
    if (empty($config->doNotResetGroupContactCache)) {
      // check previous purge date from this source, trigger cache remove at least over 3 minutes
      $createdTime = CRM_REQUEST_TIME - CRM_Contact_BAO_GroupContactCache::SMARTGROUP_CACHE_TIMEOUT_MINIMAL;
      $alreadyPurged = CRM_Core_BAO_Cache::getItem('GroupContactCache', 'CreateContactPurgeFlag', $createdTime);
      if (empty($alreadyPurged)) {
        CRM_Contact_BAO_GroupContactCache::remove();
        $alreadyPurged = 1;
        CRM_Core_BAO_Cache::setItem($alreadyPurged, 'GroupContactCache', 'CreateContactPurgeFlag');
      }
    }

    if ($invokeHooks) {
      if ($isEdit) {
        CRM_Utils_Hook::post('edit', $params['contact_type'], $contact->id, $contact);
      }
      else {
        CRM_Utils_Hook::post('create', $params['contact_type'], $contact->id, $contact);
      }
    }

    // process greetings CRM-4575, cache greetings
    self::processGreetings($contact);

    return $contact;
  }

  /**
   * Get the display name and image of a contact
   *
   * @param int $id the contactId
   *
   * @return array the displayName and contactImage for this contact
   * @access public
   * @static
   */
  static function getDisplayAndImage($id, $type = FALSE) {
    if (empty($id)) {
      return NULL;
    }
    $sql = "
SELECT    civicrm_contact.display_name as display_name,
          civicrm_contact.contact_type as contact_type,
          civicrm_contact.contact_sub_type as contact_sub_type,
          civicrm_email.email          as email       
FROM      civicrm_contact
LEFT JOIN civicrm_email ON civicrm_email.contact_id = civicrm_contact.id
     AND  civicrm_email.is_primary = 1
WHERE     civicrm_contact.id = " . CRM_Utils_Type::escape($id, 'Integer');
    $dao = new CRM_Core_DAO();
    $dao->query($sql);
    if ($dao->fetch()) {

      $image = CRM_Contact_BAO_Contact_Utils::getImage($dao->contact_sub_type ?
        $dao->contact_sub_type : $dao->contact_type, FALSE, $id
      );
      $imageUrl = CRM_Contact_BAO_Contact_Utils::getImage($dao->contact_sub_type ?
        $dao->contact_sub_type : $dao->contact_type, TRUE, $id
      );

      // use email if display_name is empty
      if (empty($dao->display_name)) {
        $dao->display_name = $dao->email;
      }
      return $type ? [$dao->display_name, $image,
        $dao->contact_type, $dao->contact_sub_type, $imageUrl,
      ] : [$dao->display_name, $image, $imageUrl];
    }
    return NULL;
  }

  /**
   *
   * Get the values for pseudoconstants for name->value and reverse.
   *
   * @param array   $defaults (reference) the default values, some of which need to be resolved.
   * @param boolean $reverse  true if we want to resolve the values in the reverse direction (value -> name)
   *
   * @return none
   * @access public
   * @static
   */
  static function resolveDefaults(&$defaults, $reverse = FALSE) {
    // hack for birth_date
    if (CRM_Utils_Array::value('birth_date', $defaults)) {
      if (is_array($defaults['birth_date'])) {
        $defaults['birth_date'] = CRM_Utils_Date::format($defaults['birth_date'], '-');
      }
    }

    CRM_Utils_Array::lookupValue($defaults, 'prefix', CRM_Core_PseudoConstant::individualPrefix(), $reverse);
    CRM_Utils_Array::lookupValue($defaults, 'suffix', CRM_Core_PseudoConstant::individualSuffix(), $reverse);
    CRM_Utils_Array::lookupValue($defaults, 'gender', CRM_Core_PseudoConstant::gender(), $reverse);

    //lookup value of email/postal greeting, addressee, CRM-4575
    foreach (self::$_greetingTypes as $greeting) {
      $filterCondition = ['contact_type' => CRM_Utils_Array::value('contact_type', $defaults),
        'greeting_type' => $greeting,
      ];
      CRM_Utils_Array::lookupValue($defaults, $greeting,
        CRM_Core_PseudoConstant::greeting($filterCondition), $reverse
      );
    }

    $blocks = ['address', 'im', 'phone'];
    foreach ($blocks as $name) {
      if (!CRM_Utils_Array::arrayKeyExists($name, $defaults) || !is_array($defaults[$name])) {
        continue;
      }
      foreach ($defaults[$name] as $count => & $values) {

        //get location type id.
        CRM_Utils_Array::lookupValue($values, 'location_type', CRM_Core_PseudoConstant::locationType(), $reverse);

        if ($name == 'address') {
          // FIXME: lookupValue doesn't work for vcard_name
          if (CRM_Utils_Array::value('location_type_id', $values)) {
            $vcardNames = &CRM_Core_PseudoConstant::locationVcardName();
            $values['vcard_name'] = $vcardNames[$values['location_type_id']];
          }

          if (!CRM_Utils_Array::lookupValue($values,
              'state_province',
              CRM_Core_PseudoConstant::stateProvince(),
              $reverse
            ) && $reverse) {

            CRM_Utils_Array::lookupValue($values,
              'state_province',
              CRM_Core_PseudoConstant::stateProvinceAbbreviation(),
              $reverse
            );
          }

          if (!CRM_Utils_Array::lookupValue($values,
              'country',
              CRM_Core_PseudoConstant::country(),
              $reverse
            ) && $reverse) {

            CRM_Utils_Array::lookupValue($values,
              'country',
              CRM_Core_PseudoConstant::countryIsoCode(),
              $reverse
            );
          }
          CRM_Utils_Array::lookupValue($values,
            'county',
            CRM_Core_PseudoConstant::county(),
            $reverse
          );
        }

        if ($name == 'im') {
          CRM_Utils_Array::lookupValue($values,
            'provider',
            CRM_Core_PseudoConstant::IMProvider(),
            $reverse
          );
        }

        if ($name == 'phone') {
          CRM_Utils_Array::lookupValue($values,
            'phone_type',
            CRM_Core_PseudoConstant::phoneType(),
            $reverse
          );
        }

        //kill the reference.
        unset($values);
      }
    }
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array   $params   (reference ) an assoc array of name/value pairs
   * @param array   $defaults (reference ) an assoc array to hold the name / value pairs
   *                        in a hierarchical manner
   * @param array   $ids      (reference) the array that holds all the db ids
   * @param boolean $microformat  for location in microformat
   *
   * @return object CRM_Contact_BAO_Contact object
   * @access public
   * @static
   */
  static function &retrieve(&$params, &$defaults, $microformat = FALSE) {
    if (CRM_Utils_Array::arrayKeyExists('contact_id', $params)) {
      $params['id'] = $params['contact_id'];
    }
    elseif (CRM_Utils_Array::arrayKeyExists('id', $params)) {
      $params['contact_id'] = $params['id'];
    }

    $contact = self::_getValues($params, $defaults);

    unset($params['id']);

    //get the block information for this contact
    $entityBlock = ['contact_id' => $params['contact_id']];
    $blocks = CRM_Core_BAO_Location::getValues($entityBlock, $microformat);
    $defaults = array_merge($defaults, $blocks);
    foreach ($blocks as $block => $value) $contact->$block = $value;

    if (!isset($params['noNotes'])) {
      $contact->notes = &CRM_Core_BAO_Note::getValues($params, $defaults);
    }

    if (!isset($params['noRelationships'])) {
      $contact->relationship = &CRM_Contact_BAO_Relationship::getValues($params, $defaults);
    }

    if (!isset($params['noGroups'])) {
      $contact->groupContact = &CRM_Contact_BAO_GroupContact::getValues($params, $defaults);
    }

    if (!isset($params['noWebsite'])) {

      $contact->website = &CRM_Core_BAO_Website::getValues($params, $defaults);
    }

    return $contact;
  }

  /**
   * function to get the display name of a contact
   *
   * @param  int    $id id of the contact
   *
   * @return null|string     display name of the contact if found
   * @static
   * @access public
   */
  static function displayName($id) {
    $displayName = NULL;
    if ($id) {
      $displayName = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'display_name');
    }

    return $displayName;
  }

  /**
   * Delete a contact and all its associated records
   *
   * @param  int  $id id of the contact to delete
   * @param  bool $restore       whether to actually restore, not delete
   * @param  bool $skipUndelete  whether to force contact delete or not
   *
   * @return boolean true if contact deleted, false otherwise
   * @access public
   * @static
   */
  static function deleteContact($id, $restore = FALSE, $skipUndelete = FALSE, $reason = NULL) {


    if (!$id) {
      return FALSE;
    }

    // make sure we have edit permission for this contact
    // before we delete

    if (!CRM_Core_Permission::check('delete contacts')) {
      return FALSE;
    }

    // make sure this contact_id does not have any membership types
    $membershipTypeID = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
      $id,
      'id',
      'member_of_contact_id'
    );
    if ($membershipTypeID) {
      return FALSE;
    }

    $contact = new CRM_Contact_DAO_Contact();
    $contact->id = $id;
    if (!$contact->find(TRUE)) {
      return FALSE;
    }

    if ($restore) {
      self::contactTrashRestore($contact->id, TRUE, $reason);
      return TRUE;
    }

    $contactType = $contact->contact_type;

    // currently we only clear employer cache.
    // we are not deleting inherited membership if any.
    if ($contact->contact_type == 'Organization') {

      CRM_Contact_BAO_Contact_Utils::clearAllEmployee($id);
    }


    CRM_Utils_Hook::pre('delete', $contactType, $id, CRM_Core_DAO::$_nullArray);

    // start a new transaction

    $transaction = new CRM_Core_Transaction();

    $config = &CRM_Core_Config::singleton();
    if ($skipUndelete) {
      //delete billing address if exists.

      CRM_Contribute_BAO_Contribution::deleteAddress(NULL, $id);

      // delete the log entries since we dont have triggers enabled as yet

      $logDAO = new CRM_Core_DAO_Log();
      $logDAO->entity_table = 'civicrm_contact';
      $logDAO->entity_id = $id;
      $logDAO->delete();

      // register log entry to indicate who delete this contact
      $session = CRM_Core_Session::singleton();
      $userID = $session->get('userID');
      $ufID = CRM_Core_BAO_UFMatch::getUFId($userID);
      $hiddenSortname = mb_substr($contact->sort_name, 0, 1).'***'.mb_substr($contact->sort_name, -1);
      $data = ts('Permanently Delete Contact')." $hiddenSortname by UFID:$ufID, ContactID:$userID";
      CRM_Core_BAO_Log::register($id, 'civicrm_contact', $id, NULL, $data);

      // do activity cleanup, CRM-5604

      CRM_Activity_BAO_Activity::cleanupActivity($id);

      $contact->delete();
    }
    else {
      self::contactTrashRestore($contact->id, FALSE, $reason);
    }

    //delete the contact id from recently view

    CRM_Utils_Recent::delContact($id);

    // reset the group contact cache for this group

    CRM_Contact_BAO_GroupContactCache::remove();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', $contactType, $contact->id, $contact);

    // also reset the DB_DO global array so we can reuse the memory
    // http://issues.civicrm.org/jira/browse/CRM-4387
    CRM_Core_DAO::freeResult();

    return TRUE;
  }

  /**
   * function to delete the image of a contact
   *
   * @param  int $id id of the contact
   *
   * @return boolean true if contact image is deleted
   */
  public static function deleteContactImage($id) {
    if (!$id) {
      return FALSE;
    }
    $query = "
UPDATE civicrm_contact
SET image_URL=NULL
WHERE id={$id}; ";
    CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    return TRUE;
  }

  /**
   * function to return relative path
   *
   * @param String $absPath absolute path
   *
   * @return String $relativePath Relative url of uploaded image
   */
  public static function getRelativePath($absolutePath) {
    $relativePath = NULL;
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Joomla') {
      $userFrameworkBaseURL = trim(str_replace('/administrator/', '', $config->userFrameworkBaseURL));
      $customFileUploadDirectory = strstr(str_replace('\\', '/', $absolutePath), '/media');
      $relativePath = $userFrameworkBaseURL . $customFileUploadDirectory;
    }
    elseif ($config->userFramework == 'Drupal') {

      $rootPath = CRM_Utils_System_Drupal::cmsRootPath();
      $baseUrl = CIVICRM_UF_BASEURL;
      $relativePath = str_replace("$rootPath/", $baseUrl, str_replace('\\', '/', $absolutePath));
    }
    elseif ($config->userFramework == 'Standalone') {
      $absolutePathStr = strstr($absolutePath, 'files');
      $relativePath = $config->userFrameworkBaseURL . str_replace('\\', '/', $absolutePathStr);
    }

    return $relativePath;
  }

  /**
   * function to validate type of contact image
   *
   * @param  Array  $param      array of contact/profile field to be edited/added
   *
   * @param  String $imageIndex index of image field
   *
   * @param  String $statusMsg  status message to be set after operation
   *
   * @opType String $opType     type of operation like fatal, bounce etc
   *
   * @return boolean true if valid image extension
   */
  public static function processImageParams(&$params,
    $imageIndex = 'image_URL',
    $statusMsg = NULL,
    $opType = 'status'
  ) {
    $mimeType = ['image/jpeg',
      'image/jpg',
      'image/png',
      'image/bmp',
      'image/p-jpeg',
      'image/gif',
      'image/x-png',
    ];

    if (in_array($params[$imageIndex]['type'], $mimeType)) {
      $params[$imageIndex] = CRM_Contact_BAO_Contact::getRelativePath($params[$imageIndex]['name']);
      return TRUE;
    }
    else {
      unset($params[$imageIndex]);
      if (!$statusMsg) {
        $statusMsg = ts('Image could not be uploaded due to invalid type extension.');
      }
      if ($opType == 'status') {
        CRM_Core_Session::setStatus($statusMsg);
      }
      // FIXME: additional support for fatal, bounce etc could be added.
      return FALSE;
    }
  }

  /**
   * function to extract contact id from url for deleting contact image
   */
  public static function processImage() {



    $action = CRM_Utils_Request::retrieve('action', 'String', CRM_Core_DAO::$_nullObject);
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', CRM_Core_DAO::$_nullObject);
    // retrieve contact id in case of Profile context
    $id = CRM_Utils_Request::retrieve('id', 'Positive', CRM_Core_DAO::$_nullObject);
    $cid = $cid ? $cid : $id;
    if ($action & CRM_Core_Action::DELETE) {
      if (CRM_Utils_Request::retrieve('confirmed', 'Boolean',CRM_Core_DAO::$_nullObject)) {
        CRM_Contact_BAO_Contact::deleteContactImage($cid);
        CRM_Core_Session::setStatus(ts('Contact Image is deleted successfully'));
        $session = CRM_Core_Session::singleton();
        $toUrl = $session->popUserContext();
        CRM_Utils_System::redirect($toUrl);
      }
    }
  }

  /**
   *  Function to set is_delete true or restore deleted contact
   *
   *  @param int     $contactId  contact id
   *  @param boolean $restore true to set the is_delete = 1 else false to restore deleted contact,
   *                                i.e. is_delete = 0
   *
   *  @return void
   */
  static function contactTrashRestore($contactId, $restore = FALSE, $reason = NULL) {

    $params = [1 => [$contactId, 'Integer']];
    $isDelete = ' is_deleted = 1 ';
    $reason = $reason ? $reason : ($restore ? ts('Restore Contacts') : ts('Delete Contact'));
    if ($restore) {
      $isDelete = ' is_deleted = 0 ';
      CRM_Core_BAO_Log::register($contactId, 'civicrm_contact', $contactId, NULL, $reason);
    }
    else {
      $query = "DELETE FROM civicrm_uf_match WHERE contact_id = %1";
      CRM_Core_DAO::executeQuery($query, $params);
      CRM_Core_BAO_Log::register($contactId, 'civicrm_contact', $contactId, NULL, $reason);
    }


    $query = "UPDATE civicrm_contact SET {$isDelete} WHERE id = %1";
    CRM_Core_DAO::executeQuery($query, $params);
  }

  /**
   * Get contact type for a contact.
   *
   * @param int $id - id of the contact whose contact type is needed
   *
   * @return string contact_type if $id found else null ""
   *
   * @access public
   *
   * @static
   *
   */
  public static function getContactType($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'contact_type');
  }

  /**
   * Get contact sub type for a contact.
   *
   * @param int $id - id of the contact whose contact sub type is needed
   *
   * @return string contact_sub_type if $id found else null ""
   *
   * @access public
   *
   * @static
   *
   */
  public static function getContactSubType($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $id, 'contact_sub_type');
  }

  /**
   * Get pair of contact-type and sub-type for a contact.
   *
   * @param int $id - id of the contact whose contact sub/contact type is needed
   *
   * @return array
   *
   * @access public
   *
   * @static
   *
   */
  public static function getContactTypes($id) {
    $params = ['id' => $id];
    $details = [];
    $contact = CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Contact',
      $params,
      $details,
      ['contact_type', 'contact_sub_type']
    );
    if ($contact) {
      return [$contact->contact_type,
        $contact->contact_sub_type,
      ];
    }
    else {
      CRM_Core_Error::fatal();
    }
  }

  /**
   * combine all the importable fields from the lower levels object
   *
   * The ordering is important, since currently we do not have a weight
   * scheme. Adding weight is super important and should be done in the
   * next week or so, before this can be called complete.
   *
   * @param int     $contactType contact Type
   * @param boolean $status  status is used to manipulate first title
   * @param boolean $showAll if true returns all fields (includes disabled fields)
   * @param boolean $isProfile if its profile mode
   *
   * @return array array of importable Fields
   * @access public
   */
  static function &importableFields($contactType = 'Individual', $status = FALSE, $showAll = FALSE,
    $isProfile = FALSE
  ) {
    if (empty($contactType)) {
      $contactType = 'All';
    }

    $cacheKeyString = "importableFields $contactType";
    $cacheKeyString .= $status ? "_1" : "_0";
    $cacheKeyString .= $showAll ? "_1" : "_0";
    $cacheKeyString .= $isProfile ? "_1" : "_0";

    if (!self::$_importableFields || !CRM_Utils_Array::value($cacheKeyString, self::$_importableFields)) {
      if (!self::$_importableFields) {
        self::$_importableFields = [];
      }

      // check if we can retrieve from database cache

      $fields = &CRM_Core_BAO_Cache::getItem('contact fields', $cacheKeyString);

      if (!$fields) {
        $fields = CRM_Contact_DAO_Contact::import();


        // get the fields thar are meant for contact types
        if (in_array($contactType, ['Individual', 'Household', 'Organization', 'All'])) {
          $fields = array_merge($fields, CRM_Core_OptionValue::getFields('', $contactType));
        }

        $locationFields = array_merge(CRM_Core_DAO_Address::import(),
          CRM_Core_DAO_Phone::import(),
          CRM_Core_DAO_Email::import(),
          CRM_Core_DAO_IM::import(TRUE),
          CRM_Core_DAO_OpenID::import()
        );

        $locationFields = array_merge($locationFields,
          CRM_Core_BAO_CustomField::getFieldsForImport('Address')
        );

        foreach ($locationFields as $key => $field) {
          $locationFields[$key]['hasLocationType'] = TRUE;
        }

        $fields = array_merge($fields, $locationFields);

        $fields = array_merge($fields,
          CRM_Contact_DAO_Contact::import()
        );
        $fields = array_merge($fields,
          CRM_Core_DAO_Note::import()
        );

        // website fields
        $fields = array_merge($fields, CRM_Core_DAO_Website::import());
        $fields['url']['hasWebsiteType'] = TRUE;

        // group field, #18274
        $groupField = CRM_Contact_DAO_Group::import();
        if (!empty($groupField['title'])) {
          $groupField['group_name'] = $groupField['title'];
          $groupField['group_name']['title'] = ts('Group Name');
          unset($groupField['title']);
          $fields = array_merge($fields, $groupField);
        }

        // tag field, #18274
        $tagField = CRM_Core_DAO_Tag::import();
        if (!empty($tagField['name'])) {
          $tagField['tag_name'] = $tagField['name'];
          $tagField['tag_name']['title'] = ts('Tag Name');
          unset($tagField['name']);
          $fields = array_merge($fields, $tagField);
        }

        if ($contactType != 'All') {
          $fields = array_merge($fields,
            CRM_Core_BAO_CustomField::getFieldsForImport($contactType, $showAll, TRUE)
          );
          //unset the fields, which are not related to their
          //contact type.
          $commonValues = ['Individual' => ['household_name', 'legal_name', 'sic_code', 'organization_name'],
            'Household' => ['first_name', 'middle_name', 'last_name', 'job_title',
              'gender_id', 'birth_date', 'organization_name', 'legal_name',
              'legal_identifier', 'sic_code', 'home_URL', 'is_deceased',
              'deceased_date',
            ],
            'Organization' => ['first_name', 'middle_name', 'last_name', 'job_title',
              'gender_id', 'birth_date', 'household_name', 'email_greeting',
              'email_greeting_custom', 'postal_greeting',
              'postal_greeting_custom', 'is_deceased', 'deceased_date',
            ],
          ];
          foreach ($commonValues[$contactType] as $value) {
            unset($fields[$value]);
          }
        }
        else {
          foreach (['Individual', 'Household', 'Organization'] as $type) {
            $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport($type, $showAll));
          }
        }

        if ($isProfile) {
          $fields = array_merge($fields, ['group' => ['title' => ts('Group(s)')],
              'tag' => ['title' => ts('Tag(s)')],
              'note' => ['title' => ts('Note(s)')],
            ]);
        }

        //Sorting fields in alphabetical order(CRM-1507)
        foreach ($fields as $k => $v) {
          $sortArray[$k] = $v['title'];
        }
        asort($sortArray);
        $fields = array_merge($sortArray, $fields);
        $fields = CRM_Core_FieldHierarchy::arrange($fields);

        CRM_Core_BAO_Cache::setItem($fields, 'contact fields', $cacheKeyString);
      }

      self::$_importableFields[$cacheKeyString] = $fields;
    }

    if (!$isProfile) {
      if (!$status) {
        $fields = array_merge(['do_not_import' => ['title' => ts('- do not import -')]],
          self::$_importableFields[$cacheKeyString]
        );
      }
      else {
        $fields = array_merge(['' => ['title' => ts('- Contact Fields -')]],
          self::$_importableFields[$cacheKeyString]
        );
      }
    }
    return $fields;
  }

  /**
   * combine all the exportable fields from the lower levels object
   *
   * currentlty we are using importable fields as exportable fields
   *
   * @param int     $contactType contact Type
   * $param boolean $status true while exporting primary contacts
   * $param boolean $export true when used during export
   *
   * @return array array of exportable Fields
   * @access public
   */
  static function &exportableFields($contactType = 'Individual', $status = FALSE, $export = FALSE) {
    if (empty($contactType)) {
      $contactType = 'All';
    }

    $cacheKeyString = "exportableFields $contactType";
    $cacheKeyString .= $export ? "_1" : "_0";
    $cacheKeyString .= $status ? "_1" : "_0";

    if (!self::$_exportableFields || !CRM_Utils_Array::value($cacheKeyString, self::$_exportableFields)) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }

      // check if we can retrieve from database cache

      $fields = &CRM_Core_BAO_Cache::getItem('contact fields', $cacheKeyString);

      $masterAddress['master_address_belongs_to'] = ['name' => 'master_id',
        'title' => ts('Master Address Belongs To'),
      ];

      if (!$fields) {
        $fields = [];
        $fields = array_merge($fields, CRM_Contact_DAO_Contact::export());

        // add master address display name for individual
        $fields = array_merge($fields, $masterAddress);
        // add age field for individual
        $ageField['age'] = ['name' => 'age', 'title' => ts('Age'), 'where' => 'civicrm_contact.birth_date'];
        $fields = array_merge($fields, $ageField);
        // the fields are meant for contact types
        if (in_array($contactType, ['Individual', 'Household', 'Organization', 'All'])) {

          $fields = array_merge($fields, CRM_Core_OptionValue::getFields('', $contactType));
        }
        // add current employer for individuals
        $fields = array_merge($fields, ['current_employer' =>
            ['name' => 'organization_name',
              'title' => ts('Current Employer'),
            ],
          ]);

        $locationType = [];
        if ($status) {
          $locationType['location_type'] = ['name' => 'location_type',
            'where' => 'civicrm_location_type.name',
            'title' => ts('Location Type'),
          ];
        }

        $IMProvider = [];
        if ($status) {
          $IMProvider['im_provider'] = ['name' => 'im_provider',
            'where' => 'im_provider.name',
            'title' => ts('IM Provider'),
          ];
        }

        $locationFields = array_merge($locationType,
          CRM_Core_DAO_Address::export(),
          CRM_Core_DAO_Phone::export(),
          CRM_Core_DAO_Email::export(),
          $IMProvider,
          CRM_Core_DAO_IM::export(TRUE),
          CRM_Core_DAO_OpenID::export()
        );

        $locationFields = array_merge($locationFields,
          CRM_Core_BAO_CustomField::getFieldsForImport('Address')
        );

        foreach ($locationFields as $key => $field) {
          $locationFields[$key]['hasLocationType'] = TRUE;
        }

        $fields = array_merge($fields, $locationFields);

        //add world region

        $fields = array_merge($fields,
          CRM_Core_DAO_Worldregion::export()
        );


        $fields = array_merge($fields,
          CRM_Contact_DAO_Contact::export()
        );

        //website fields
        $fields = array_merge($fields, CRM_Core_DAO_Website::export());

        if ($contactType != 'All') {
          $fields = array_merge($fields,
            CRM_Core_BAO_CustomField::getFieldsForImport($contactType, $status, TRUE)
          );
        }
        else {
          foreach (['Individual', 'Household', 'Organization'] as $type) {
            $fields = array_merge($fields,
              CRM_Core_BAO_CustomField::getFieldsForImport($type)
            );
          }
        }

        //fix for CRM-791
        if ($export) {
          $fields = array_merge($fields, ['groups' => ['title' => ts('Group(s)')],
              'tags' => ['title' => ts('Tag(s)')],
              'notes' => ['title' => ts('Note(s)')],
            ]);
        }
        else {
          $fields = array_merge($fields, ['group' => ['title' => ts('Group(s)')],
              'tag' => ['title' => ts('Tag(s)')],
              'note' => ['title' => ts('Note(s)')],
            ]);
        }

        //Sorting fields in alphabetical order(CRM-1507)
        foreach ($fields as $k => $v) {
          $sortArray[$k] = CRM_Utils_Array::value('title', $v);
        }

        $fields = array_merge($sortArray, $fields);
        //unset the field which are not related to their contact type.
        if ($contactType != 'All') {
          $commonValues = ['Individual' => ['household_name', 'legal_name', 'sic_code', 'organization_name',
              'email_greeting_custom', 'postal_greeting_custom',
              'addressee_custom',
            ],
            'Household' => ['first_name', 'middle_name', 'last_name', 'job_title',
              'gender_id', 'birth_date', 'organization_name', 'legal_name',
              'legal_identifier', 'sic_code', 'home_URL', 'is_deceased',
              'deceased_date', 'current_employer', 'email_greeting_custom',
              'postal_greeting_custom', 'addressee_custom',
              'individual_prefix', 'individual_suffix', 'gender','age',
            ],
            'Organization' => ['first_name', 'middle_name', 'last_name', 'job_title',
              'gender_id', 'birth_date', 'household_name', 'email_greeting',
              'postal_greeting', 'email_greeting_custom',
              'postal_greeting_custom', 'individual_prefix',
              'individual_suffix', 'gender', 'addressee_custom',
              'is_deceased', 'deceased_date', 'current_employer','age',
            ],
          ];
          foreach ($commonValues[$contactType] as $value) {
            unset($fields[$value]);
          }
        }

        CRM_Core_BAO_Cache::setItem($fields, 'contact fields', $cacheKeyString);
      }
      self::$_exportableFields[$cacheKeyString] = $fields;
    }

    if (!$status) {
      $fields = self::$_exportableFields[$cacheKeyString];
    }
    else {
      $fields = array_merge(['' => ['title' => ts('- Contact Fields -')]],
        self::$_exportableFields[$cacheKeyString]
      );
    }

    return $fields;
  }

  /**
   * Function to get the all contact details(Hierarchical)
   *
   * @param int   $contactId contact id
   * @param array $fields fields array
   *
   * @return $values array contains the contact details
   * @static
   * @access public
   */
  static function getHierContactDetails($contactId, &$fields) {
    $params = [['contact_id', '=', $contactId, 0, 0]];
    $options = [];

    $returnProperties = &self::makeHierReturnProperties($fields, $contactId);

    // we dont know the contents of return properties, but we need the lower level ids of the contact
    // so add a few fields
    $returnProperties['first_name'] = $returnProperties['organization_name'] = $returnProperties['household_name'] = $returnProperties['contact_type'] = 1;
    return list($query, $options) = CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, $options);
  }

  /**
   * given a set of flat profile style field names, create a hierarchy
   * for query to use and crete the right sql
   *
   * @param array $properties a flat return properties name value array
   * @param int   $contactId contact id
   *
   * @return array a hierarchical property tree if appropriate
   * @access public
   * @static
   */
  static function &makeHierReturnProperties($fields, $contactId = NULL) {

    $locationTypes = CRM_Core_PseudoConstant::locationType(NULL, 'name');

    $returnProperties = [];
    $locationIds = [];
    $multipleFields = ['website' => 'url'];
    foreach ($fields as $name => $dontCare) {
      if (strpos($name, '-') !== FALSE) {
        list($fieldName, $id, $type) = CRM_Utils_System::explode('-', $name, 3);

        if (!in_array($fieldName, $multipleFields)) {
          if ($id == 'Primary') {
            $locationTypeName = 1;
          }
          else {
            $locationTypeName = CRM_Utils_Array::value($id, $locationTypes);
            if (!$locationTypeName) {
              continue;
            }
          }

          if (!CRM_Utils_Array::value('location', $returnProperties)) {
            $returnProperties['location'] = [];
          }
          if (!CRM_Utils_Array::value($locationTypeName, $returnProperties['location'])) {
            $returnProperties['location'][$locationTypeName] = [];
            $returnProperties['location'][$locationTypeName]['location_type'] = $id;
          }
          if (in_array($fieldName, ['phone', 'im', 'email', 'openid'])) {
            if ($type) {
              $returnProperties['location'][$locationTypeName][$fieldName . '-' . $type] = 1;
            }
            else {
              $returnProperties['location'][$locationTypeName][$fieldName] = 1;
            }
          }
          elseif (substr($fieldName, 0, 14) === 'address_custom') {
            $returnProperties['location'][$locationTypeName][substr($fieldName, 8)] = 1;
          }
          else {
            $returnProperties['location'][$locationTypeName][$fieldName] = 1;
          }
        }
        else {
          $returnProperties['website'][$id][$fieldName] = 1;
        }
      }
      else {
        $returnProperties[$name] = 1;
      }
    }

    return $returnProperties;
  }

  /**
   * Function to return the primary location type of a contact
   *
   * $params int     $contactId contact_id
   * $params boolean $isPrimaryExist if true, return primary contact location type otherwise null
   * $params boolean $skipDefaultPriamry if true, return primary contact location type otherwise null
   *
   * @return int $locationType location_type_id
   * @access public
   * @static
   */
  static function getPrimaryLocationType($contactId, $skipDefaultPriamry = FALSE, $block = NULL) {
    if ($block) {
      $entityBlock = ['contact_id' => $contactId];
      $blocks = CRM_Core_BAO_Location::getValues($entityBlock);
      foreach ($blocks[$block] as $key => $value) {
        if (!empty($value['is_primary'])) {
          $locationType = CRM_Utils_Array::value('location_type_id', $value);
          return $locationType;
        }
      }
    }
    else {
      $query = "
SELECT
 IF ( civicrm_email.location_type_id IS NULL,
    IF ( civicrm_address.location_type_id IS NULL, 
        IF ( civicrm_phone.location_type_id IS NULL,
           IF ( civicrm_im.location_type_id IS NULL, 
               IF ( civicrm_openid.location_type_id IS NULL, null, civicrm_openid.location_type_id)
           ,civicrm_im.location_type_id)
        ,civicrm_phone.location_type_id)
     ,civicrm_address.location_type_id)
  ,civicrm_email.location_type_id)  as locationType
FROM civicrm_contact
     LEFT JOIN civicrm_email   ON ( civicrm_email.is_primary   = 1 AND civicrm_email.contact_id = civicrm_contact.id )
     LEFT JOIN civicrm_address ON ( civicrm_address.is_primary = 1 AND civicrm_address.contact_id = civicrm_contact.id)
     LEFT JOIN civicrm_phone   ON ( civicrm_phone.is_primary   = 1 AND civicrm_phone.contact_id = civicrm_contact.id)
     LEFT JOIN civicrm_im      ON ( civicrm_im.is_primary      = 1 AND civicrm_im.contact_id = civicrm_contact.id)
     LEFT JOIN civicrm_openid  ON ( civicrm_openid.is_primary  = 1 AND civicrm_openid.contact_id = civicrm_contact.id)
WHERE  civicrm_contact.id = %1 ";
    
      $params = [1 => [$contactId, 'Integer']];

      $dao = CRM_Core_DAO::executeQuery($query, $params);

      $locationType = NULL;
      if ($dao->fetch()) {
        $locationType = $dao->locationType;
      }
    }

    if ($locationType) {
      return $locationType;
    }
    elseif ($skipDefaultPriamry) {
      // if there is no primary contact location then return null
      return NULL;
    }
    else {
      $defaultLocationType = CRM_Core_BAO_LocationType::getDefault();
      return $defaultLocationType->id;
    }
  }

  /**
   * function to get the sort name, primary email and should we email of a contact
   *
   * @param  int    $id id of the contact
   *
   * @return array
   *   sort_name, email, do_not_email(bool), on_hold(bool), deceased(bool), do_not_notify(bool)
   * @static
   * @access public
   */
  static function getContactDetails($id) {
    // check if the contact type
    $contactType = self::getContactType($id);

    $nameFields = ($contactType == 'Individual') ? "civicrm_contact.first_name, civicrm_contact.last_name, civicrm_contact.sort_name" : "civicrm_contact.sort_name";

    $sql = "
SELECT $nameFields, civicrm_email.email, civicrm_contact.do_not_email, civicrm_contact.do_not_notify, civicrm_email.on_hold, civicrm_contact.is_deceased
FROM   civicrm_contact LEFT JOIN civicrm_email ON (civicrm_contact.id = civicrm_email.contact_id)
WHERE  civicrm_contact.id = %1
ORDER BY civicrm_email.is_primary DESC";
    $params = [1 => [$id, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      $name = $dao->sort_name;
      $email = $dao->email;
      $doNotEmail = $dao->do_not_email ? TRUE : FALSE;
      $onHold = $dao->on_hold ? TRUE : FALSE;
      $isDeceased = $dao->is_deceased ? TRUE : FALSE;
      $doNotNotify = $dao->do_not_notify ? TRUE : FALSE;
      return [$name, $email, $doNotEmail, $onHold, $isDeceased, $doNotNotify];
    }
    return [NULL, NULL, NULL, NULL, NULL, NULL];
  }

  /**
   * function to add/edit/register contacts through profile.
   *
   * @params  array  $params        Array of profile fields to be edited/added.
   * @params  int    $contactID     contact_id of the contact to be edited/added.
   * @params  array  $fields        array of fields from UFGroup
   * @params  int    $addToGroupID  specifies the default group to which contact is added.
   * @params  int    $ufGroupId     uf group id (profile id)
   * @param   string $ctype         contact type
   *
   * @return  int                   contact id created/edited
   * @static
   * @access public
   */
  static function createProfileContact(&$params, &$fields, $contactID = NULL,
    $addToGroupID = NULL, $ufGroupId = NULL,
    $ctype = NULL,
    $visibility = FALSE
  ) {
    // add ufGroupID to params array ( CRM-2012 )
    if ($ufGroupId) {
      $params['uf_group_id'] = $ufGroupId;
    }


    if ($contactID) {
      $editHook = TRUE;
      CRM_Utils_Hook::pre('edit', 'Profile', $contactID, $params);
    }
    else {
      $editHook = FALSE;
      CRM_Utils_Hook::pre('create', 'Profile', NULL, $params);
    }

    list($data, $contactDetails) = self::formatProfileContactParams($params, $fields, $contactID, $ufGroupId, $ctype);

    // manage is_opt_out
    if (CRM_Utils_Array::arrayKeyExists('is_opt_out', $fields) && CRM_Utils_Array::arrayKeyExists('is_opt_out', $params)) {
      $wasOptOut = CRM_Utils_Array::value('is_opt_out', $contactDetails, FALSE);
      $isOptOut = CRM_Utils_Array::value('is_opt_out', $params, FALSE);
      $data['is_opt_out'] = $isOptOut;
      // on change, create new civicrm_subscription_history entry
      if (($wasOptOut != $isOptOut) && CRM_Utils_Array::value('contact_id', $contactDetails)) {
        $shParams = [
          'contact_id' => $contactDetails['contact_id'],
          'status' => $isOptOut ? 'Removed' : 'Added',
          'method' => 'Web',
        ];
        CRM_Contact_BAO_SubscriptionHistory::create($shParams);
      }
    }


    $config = CRM_Core_Config::singleton();
    $config->doNotResetCache = TRUE;
    if ($data['contact_type'] != 'Student') {
      if(!empty($params['log_data'])) {
        $data['log_data'] = $params['log_data'];
      }
      $contact = &self::create($data);
    }

    // contact is null if the profile does not have any contact fields
    if ($contact) {
      $contactID = $contact->id;
    }

    if (!$contactID) {
      CRM_Core_Error::fatal('Cannot proceed without a valid contact id');
    }

    // Process group and tag
    if (CRM_Utils_Array::value('group', $fields) && !empty($params['group'])) {
      $contactIds = [$contactID];
      $method = 'Admin';
      // this for sure means we are coming in via profile since i added it to fix
      // removing contacts from user groups -- lobo
      if ($visibility) {
        $method = 'Web';
      }
      foreach($params['group'] as $groupId => $add) {
        if ($add) {
          CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId, $method);
        }
      }
    }

    if (CRM_Utils_Array::value('tag', $fields) && !empty($params['tag'])) {

      CRM_Core_BAO_EntityTag::create($params['tag'], 'civicrm_contact', $contactID);
    }

    //to add profile in default group
    if (is_array($addToGroupID)) {
      $contactIds = [$contactID];
      foreach ($addToGroupID as $groupId) {
        CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $groupId, 'Web');
      }
    }
    elseif ($addToGroupID) {
      $contactIds = [$contactID];
      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIds, $addToGroupID, 'Web');
    }


    //to update student record
    if (CRM_Core_Permission::access('Quest') && $studentFieldPresent) {
      $ids = [];
      $dao = new CRM_Quest_DAO_Student();
      $dao->contact_id = $contactID;
      if ($dao->find(TRUE)) {
        $ids['id'] = $dao->id;
      }

      $ssids = [];
      $studentSummary = new CRM_Quest_DAO_StudentSummary();
      $studentSummary->contact_id = $contactID;
      if ($studentSummary->find(TRUE)) {
        $ssids['id'] = $studentSummary->id;
      }

      $params['contact_id'] = $contactID;
      //fixed for check boxes

      $specialFields = ['educational_interest', 'college_type', 'college_interest', 'test_tutoring'];
      foreach ($specialFields as $field) {
        if ($params[$field]) {
          $params[$field] = CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, array_keys($params[$field]));
        }
      }

      CRM_Quest_BAO_Student::create($params, $ids);
      CRM_Quest_BAO_Student::createStudentSummary($params, $ssids);
    }

    // reset the group contact cache for this group

    if (!$config->doNotResetGroupContactCache) {
      CRM_Contact_BAO_GroupContactCache::remove();
    }

    if ($editHook) {
      CRM_Utils_Hook::post('edit', 'Profile', $contactID, $params);
    }
    else {
      CRM_Utils_Hook::post('create', 'Profile', $contactID, $params);
    }
    $config->doNotResetCache = FALSE;
    return $contactID;
  }

  static function formatProfileContactParams(&$params, &$fields, $contactID = NULL,
    $ufGroupId = NULL, $ctype = NULL, $skipCustom = FALSE
  ) {

    $data = $contactDetails = [];

    // get the contact details (hier)
    if ($contactID) {
      list($details, $options) = self::getHierContactDetails($contactID, $fields);

      $contactDetails = $details[$contactID];
      $data['contact_type'] = CRM_Utils_Array::value('contact_type', $contactDetails);
      $data['contact_sub_type'] = CRM_Utils_Array::value('contact_sub_type', $contactDetails);
    }
    else {
      //we should get contact type only if contact
      if ($ufGroupId) {
        $data['contact_type'] = CRM_Core_BAO_UFField::getProfileType($ufGroupId);

        //special case to handle profile with only contact fields
        if ($data['contact_type'] == 'Contact') {
          $data['contact_type'] = 'Individual';
        }
        elseif (CRM_Contact_BAO_ContactType::isaSubType($data['contact_type'])) {
          $data['contact_type'] = CRM_Contact_BAO_ContactType::getBasicType($data['contact_type']);
        }
      }
      elseif ($ctype) {
        $data['contact_type'] = $ctype;
      }
      else {
        $data['contact_type'] = 'Individual';
      }
    }

    //fix contact sub type CRM-5125
    if ( $subType = CRM_Utils_Array::value('contact_sub_type', $params) ) {
      $data['contact_sub_type'] = $subType;
    }
    elseif ( $subType = CRM_Utils_Array::value('contact_sub_type_hidden', $params ) ) {
      // if profile was used, and had any subtype, we obtain it from there 
      $data['contact_sub_type'] = $subType;
    }

    if ($ctype == 'Organization') {
      $data['organization_name'] = CRM_Utils_Array::value('organization_name', $contactDetails);
    }
    elseif ($ctype == 'Household') {
      $data['household_name'] = CRM_Utils_Array::value('household_name', $contactDetails);
    }

    $locationType = [];
    $count = 1;

    if ($contactID) {
      //add contact id
      $data['contact_id'] = $contactID;
      $primaryLocationType = self::getPrimaryLocationType($contactID);
    }
    else {
      $defaultLocation = CRM_Core_BAO_LocationType::getDefault();
      $defaultLocationId = $defaultLocation->id;
    }

    // get the billing location type
    $locationTypes = CRM_Core_PseudoConstant::locationType(TRUE, 'name');
    $billingLocationTypeId = array_search('Billing', $locationTypes);

    $blocks = ['email', 'phone', 'im', 'openid'];

    $multiplFields = ['url'];
    // prevent overwritten of formatted array, reset all block from
    // params if it is not in valid format (since import pass valid format)
    foreach ($blocks as $blk) {
      if (CRM_Utils_Array::arrayKeyExists($blk, $params) &&
        !is_array($params[$blk])
      ) {
        unset($params[$blk]);
      }
    }

    $primaryPhoneLoc = NULL;
    foreach ($params as $key => $value) {
      $fieldName = $locTypeId = $typeId = NULL;
      list($fieldName, $locTypeId, $typeId) = CRM_Utils_System::explode('-', $key, 3);

      //store original location type id
      $actualLocTypeId = $locTypeId;

      if ($locTypeId == 'Primary') {
        if ($contactID) {
          $locTypeId = $primaryLocationType;
        }
        else {
          $locTypeId = $defaultLocationId;
        }
      }

      if (is_numeric($locTypeId) &&
        !in_array($fieldName, $multiplFields) &&
        substr($fieldName, 0, 7) != 'custom_'
      ) {
        $index = $locTypeId;

        if (is_numeric($typeId)) {
          $index .= '-' . $typeId;
        }
        if (!in_array($index, $locationType)) {
          $locationType[$count] = $index;
          $count++;
        }

        $loc = CRM_Utils_Array::key($index, $locationType);

        $blockName = 'address';
        if (in_array($fieldName, $blocks)) {
          $blockName = $fieldName;
        }

        $data[$blockName][$loc]['location_type_id'] = $locTypeId;

        //set is_billing true, for location type "Billing"
        if ($locTypeId == $billingLocationTypeId) {
          $data[$blockName][$loc]['is_billing'] = 1;
        }

        if ($contactID) {
          //get the primary location type
          if ($locTypeId == $primaryLocationType) {
            $data[$blockName][$loc]['is_primary'] = 1;
          }
        }
        elseif (($locTypeId == $defaultLocationId || $locTypeId == $billingLocationTypeId) &&
          ($loc == 1 || !CRM_Utils_Array::retrieveValueRecursive($data['location'][$loc - 1], 'is_primary'))
        ) {
          $data[$blockName][$loc]['is_primary'] = 1;
        }

        if ($fieldName == 'phone') {
          if ($typeId) {
            $data['phone'][$loc]['phone_type_id'] = $typeId;
          }
          else {
            $data['phone'][$loc]['phone_type_id'] = '';
          }
          $data['phone'][$loc]['phone'] = $value;

          //special case to handle primary phone with different phone types
          // in this case we make first phone type as primary
          if (isset($data['phone'][$loc]['is_primary']) && !$primaryPhoneLoc) {
            $primaryPhoneLoc = $loc;
          }

          if ($loc != $primaryPhoneLoc) {
            unset($data['phone'][$loc]['is_primary']);
          }
        }
        elseif ($fieldName == 'email') {
          $data['email'][$loc]['email'] = $value;
        }
        elseif ($fieldName == 'im') {
          if (isset($params[$key . '-provider_id'])) {
            $data['im'][$loc]['provider_id'] = $params[$key . '-provider_id'];
          }
          if (strpos($key, '-provider_id') !== FALSE) {
            $data['im'][$loc]['provider_id'] = $params[$key];
          }
          else {
            $data['im'][$loc]['name'] = $value;
          }
        }
        elseif ($fieldName == 'openid') {
          $data['openid'][$loc]['openid'] = $value;
        }
        else {
          if ($fieldName === 'state_province') {
            // CRM-3393
            if (is_numeric($value) &&
              ((int ) $value) >= 1000
            ) {
              $data['address'][$loc]['state_province_id'] = $value;
            }
            else {
              $data['address'][$loc]['state_province'] = $value;
            }
          }
          elseif ($fieldName === 'country') {
            // CRM-3393
            if (is_numeric($value) &&
              ((int ) $value) >= 1000
            ) {
              $data['address'][$loc]['country_id'] = $value;
            }
            else {
              $data['address'][$loc]['country'] = $value;
            }
          }
          elseif ($fieldName === 'county') {
            $data['address'][$loc]['county_id'] = $value;
          }
          elseif ($fieldName == 'address_name') {
            $data['address'][$loc]['name'] = $value;
          }
          elseif (substr($fieldName, 0, 14) === 'address_custom') {
            $data['address'][$loc][substr($fieldName, 8)] = $value;
          }
          else {
            $data['address'][$loc][$fieldName] = $value;
          }
        }
      }
      else {
        if (substr($key, 0, 4) === 'url-') {
          $websiteField = explode('-', $key);
          $data['website'][$websiteField[1]]['website_type_id'] = $websiteField[1];
          $data['website'][$websiteField[1]]['url'] = $value;
        }
        elseif ($key === 'individual_suffix') {
          $data['suffix_id'] = $value;
        }
        elseif ($key === 'individual_prefix') {
          $data['prefix_id'] = $value;
        }
        elseif ($key === 'gender') {
          $data['gender_id'] = $value;
        }
        //save email/postal greeting and addressee values if any, CRM-4575
        elseif (in_array($key, self::$_greetingTypes, TRUE)) {
          $data[$key . '_id'] = $value;
        }
        elseif (!$skipCustom && ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key))) {
          // for autocomplete transfer hidden value instead of label
          if ($params[$key] && isset($params[$key . '_id'])) {
            $value = $params[$key . '_id'];
          }

          // we need to append time with date
          if ($params[$key] && isset($params[$key . '_time'])) {
            $value .= ' ' . $params[$key . '_time'];
          }

          $type = CRM_Utils_Array::value('contact_sub_type', $data) ? $data['contact_sub_type'] : $data['contact_type'];

          CRM_Core_BAO_CustomField::formatCustomField($customFieldId,
            $data['custom'],
            $value,
            $type,
            NULL,
            $contactID
          );
        }
        elseif ($key == 'edit') {
          continue;
        }
        else {
          if ($key == 'location') {
            foreach ($value as $locationTypeId => $field) {
              foreach ($field as $block => $val) {
                if ($block == 'address' && CRM_Utils_Array::arrayKeyExists('address_name', $val)) {
                  $value[$locationTypeId][$block]['name'] = $value[$locationTypeId][$block]['address_name'];
                }
              }
            }
          }
          $data[$key] = $value;
        }
      }
    }

    if (!isset($data['contact_type'])) {
      $data['contact_type'] = 'Individual';
    }
    if (is_array($data['image_URL']) && !empty($data['image_URL']['name'])) {
      self::processImageParams($data);
    }

    if (CRM_Core_Permission::access('Quest')) {
      $studentFieldPresent = 0;
      foreach ($fields as $name => $field) {
        // check if student fields present
        if ((!$studentFieldPresent) && CRM_Utils_Array::arrayKeyExists($name, CRM_Quest_BAO_Student::exportableFields())) {
          $studentFieldPresent = 1;
        }
      }
    }

    //set the values for checkboxes (do_not_email, do_not_mail, do_not_trade, do_not_phone)
    $privacy = CRM_Core_SelectValues::privacy();
    foreach ($privacy as $key => $value) {
      if (CRM_Utils_Array::arrayKeyExists($key, $fields)) {
        if (CRM_Utils_Array::arrayKeyExists($key, $params)) {
          $data[$key] = $params[$key];
          // dont reset it for existing contacts
        }
        elseif (!$contactID) {
          $data[$key] = 0;
        }
      }
    }

    // prepare saved greeting values
    if ($contactID) {
      $contactParams = ['id' => $contactID];
      $returnProperties = $retrieved = [];
      foreach(self::$_greetingTypes as $greeting) {
        if (empty($data[$greeting.'_id'])) {
          $returnProperties[] = $greeting.'_id';
        }
        if (empty($data[$greeting.'_custom'])) {
          $returnProperties[] = $greeting.'_custom';
        }
      }
      CRM_Core_DAO::commonRetrieve('CRM_Contact_DAO_Contact', $contactParams, $retrieved, $returnProperties);
      foreach($retrieved as $key => $value) {
        if ($key != 'id') {
          $data[$key] = $value;
        }
      }
    }

    // log
    if (isset($params['log_data'])) {
      $data['log_data'] = $params['log_data'];
    }

    return [$data, $contactDetails];
  }

  /**
   * Function to find the get contact details
   * does not respect ACLs for now, which might need to be rectified at some
   * stage based on how its used
   *
   * @param string $mail  primary email address of the contact
   * @param string $ctype contact type
   *
   * @return object $dao contact details
   * @static
   */
  static function &matchContactOnEmail($mail, $ctype = NULL) {
    $mail = mb_strtolower(trim($mail), 'UTF-8');
    $query = "
SELECT     civicrm_contact.id as contact_id,
           civicrm_contact.hash as hash,
           civicrm_contact.contact_type as contact_type,
           civicrm_contact.contact_sub_type as contact_sub_type
FROM       civicrm_contact
INNER JOIN civicrm_email    ON ( civicrm_contact.id = civicrm_email.contact_id )";

    if (defined('CIVICRM_UNIQ_EMAIL_PER_SITE') && CIVICRM_UNIQ_EMAIL_PER_SITE) {
      // try to find a match within a site (multisite).

      $groups = CRM_Core_BAO_Domain::getChildGroupIds();
      if (!empty($groups)) {
        $query .= "
INNER JOIN civicrm_group_contact gc ON 
(civicrm_contact.id = gc.contact_id AND gc.status = 'Added' AND gc.group_id IN (" . CRM_Utils_Array::implode(',', $groups) . "))";
      }
    }

    $query .= " 
WHERE      civicrm_email.email = %1 AND civicrm_contact.is_deleted=0";
    $p = [1 => [$mail, 'String']];

    if ($ctype) {
      $query .= " AND civicrm_contact.contact_type = %3";
      $p[3] = [$ctype, 'String'];
    }

    $query .= " ORDER BY civicrm_email.is_primary DESC";

    $dao = &CRM_Core_DAO::executeQuery($query, $p);

    if ($dao->fetch()) {
      return $dao;
    }
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * Function to find the contact details associated with an OpenID
   *
   * @param string $openId openId of the contact
   * @param string $ctype  contact type
   *
   * @return object $dao contact details
   * @static
   */
  static function &matchContactOnOpenId($openId, $ctype = NULL) {
    $openId = mb_strtolower(trim($openId), 'UTF-8');
    $query = "
SELECT     civicrm_contact.id as contact_id,
           civicrm_contact.hash as hash,
           civicrm_contact.contact_type as contact_type,
           civicrm_contact.contact_sub_type as contact_sub_type
FROM       civicrm_contact
INNER JOIN civicrm_openid    ON ( civicrm_contact.id = civicrm_openid.contact_id )
WHERE      civicrm_openid.openid = %1";
    $p = [1 => [$openId, 'String']];

    if ($ctype) {
      $query .= " AND civicrm_contact.contact_type = %3";
      $p[3] = [$ctype, 'String'];
    }

    $query .= " ORDER BY civicrm_openid.is_primary DESC";

    $dao = &CRM_Core_DAO::executeQuery($query, $p);

    if ($dao->fetch()) {
      return $dao;
    }
    return CRM_Core_DAO::$_nullObject;
  }

  /**
   * Funtion to get primary email of the contact
   *
   * @param int $contactID contact id
   *
   * @return string $dao->email  email address if present else null
   * @static
   * @access public
   */
  public static function getPrimaryEmail($contactID) {
    // fetch the primary email
    $query = "
   SELECT civicrm_email.email as email
     FROM civicrm_contact
LEFT JOIN civicrm_email ON ( civicrm_contact.id = civicrm_email.contact_id )
    WHERE civicrm_contact.id = %1
      ORDER BY civicrm_email.is_primary DESC";
    $p = [1 => [$contactID, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);

    $email = NULL;
    if ($dao->fetch()) {
      $email = $dao->email;
    }
    $dao->free();
    return $email;
  }

  /**
   * Funtion to get primary OpenID of the contact
   *
   * @param int $contactID contact id
   *
   * @return string $dao->openid   OpenID if present else null
   * @static
   * @access public
   */
  public static function getPrimaryOpenId($contactID) {
    // fetch the primary OpenID
    $query = "
SELECT    civicrm_openid.openid as openid
FROM      civicrm_contact
LEFT JOIN civicrm_openid ON ( civicrm_contact.id = civicrm_openid.contact_id )
WHERE     civicrm_contact.id = %1
AND       civicrm_openid.is_primary = 1";
    $p = [1 => [$contactID, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($query, $p);

    $openId = NULL;
    if ($dao->fetch()) {
      $openId = $dao->openid;
    }
    $dao->free();
    return $openId;
  }

  /**
   * Function to get the count of  contact loctions
   *
   * @param int $contactId contact id
   *
   * @return int $locationCount max locations for the contact
   * @static
   * @access public
   */
  static function getContactLocations($contactId) {
    // find the system config related location blocks

    $locationCount = CRM_Core_BAO_Preferences::value('location_count');

    $contactLocations = [];

    // find number of location blocks for this contact and adjust value accordinly
    // get location type from email
    $query = "
( SELECT location_type_id FROM civicrm_email   WHERE contact_id = {$contactId} )
UNION
( SELECT location_type_id FROM civicrm_phone   WHERE contact_id = {$contactId} )
UNION
( SELECT location_type_id FROM civicrm_im      WHERE contact_id = {$contactId} )
UNION
( SELECT location_type_id FROM civicrm_address WHERE contact_id = {$contactId} )
";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    $locCount = $dao->N;
    if ($locCount && $locationCount < $locCount) {
      $locationCount = $locCount;
    }

    return $locationCount;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params input parameters to find object
   * @param array $values output values of the object
   *
   * @return CRM_Contact_BAO_Contact|null the found object or null
   * @access public
   * @static
   */
  private static function _getValues(&$params, &$values) {
    $contact = new CRM_Contact_BAO_Contact();

    $contact->copyValues($params);

    if ($contact->find(TRUE)) {

      CRM_Core_DAO::storeValues($contact, $values);

      $privacy = [];
      foreach (self::$_commPrefs as $name) {
        if (isset($contact->$name)) {
          $privacy[$name] = $contact->$name;
        }
      }

      if (!empty($privacy)) {
        $values['privacy'] = $privacy;
      }

      // communication Prefferance
      $preffComm = $comm = [];
      $comm = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $contact->preferred_communication_method);
      foreach ($comm as $value) {
        $preffComm[$value] = 1;
      }
      $temp = ['preferred_communication_method' => $contact->preferred_communication_method];

      $names = ['preferred_communication_method' => ['newName' => 'preferred_communication_method_display',
          'groupName' => 'preferred_communication_method',
        ]];


      CRM_Core_OptionGroup::lookupValues($temp, $names, FALSE);

      $values['preferred_communication_method'] = $preffComm;
      $values['preferred_communication_method_display'] = CRM_Utils_Array::value('preferred_communication_method_display', $temp);

      CRM_Contact_DAO_Contact::addDisplayEnums($values);

      // get preferred languages
      if (!empty($contact->preferred_language)) {
        $languages = &CRM_Core_PseudoConstant::languages();
        $values['preferred_language'] = CRM_Utils_Array::value($contact->preferred_language, $languages);
      }

      // Calculating Year difference
      if ($contact->birth_date) {
        $birthDate = CRM_Utils_Date::customFormat($contact->birth_date, '%Y%m%d');
        if ($birthDate < date('Ymd')) {
          $age = CRM_Utils_Date::calculateAge($birthDate);
          $values['age']['y'] = CRM_Utils_Array::value('years', $age);
          $values['age']['m'] = CRM_Utils_Array::value('months', $age);
        }

        list($values['birth_date']) = CRM_Utils_Date::setDateDefaults($contact->birth_date, 'birth');
        $values['birth_date_display'] = $contact->birth_date;
      }

      if ($contact->deceased_date) {
        list($values['deceased_date']) = CRM_Utils_Date::setDateDefaults($contact->deceased_date, 'birth');
        $values['deceased_date_display'] = $contact->deceased_date;
      }

      $contact->contact_id = $contact->id;

      return $contact;
    }
    return NULL;
  }

  /**
   * Given the component name and returns
   * the count of participation of contact
   *
   * @param string  $component input component name
   * @param integer $contactId input contact id
   * @param string  $tableName optional tableName if component is custom group
   *
   * @return total number of count of occurence in database
   * @access public
   * @static
   */

  static function getCountComponent($component, $contactId, $tableName = NULL) {
    $object = NULL;
    switch ($component) {
      case 'tag':

        return CRM_Core_BAO_EntityTag::getContactTags($contactId, TRUE);

      case 'rel':

        return count(CRM_Contact_BAO_Relationship::getRelationship($contactId));

      case 'group':

        return CRM_Contact_BAO_GroupContact::getContactGroup($contactId, NULL, NULL, TRUE);

      case 'log':

        return CRM_Core_BAO_Log::getContactLogCount($contactId);

      case 'note':

        return CRM_Core_BAO_Note::getContactNoteCount($contactId);

      case 'contribution':

        return CRM_Contribute_BAO_Contribution::contributionCount($contactId);

      case 'membership':

        return CRM_Member_BAO_Membership::getContactMembershipCount($contactId);

      case 'participant':

        return CRM_Event_BAO_Participant::getContactParticipantCount($contactId);

      case 'pledge':

        return CRM_Pledge_BAO_Pledge::getContactPledgeCount($contactId);

      case 'case':

        return CRM_Case_BAO_Case::caseCount($contactId);

      case 'grant':

        return CRM_Grant_BAO_Grant::getContactGrantCount($contactId);

      case 'activity':

        return CRM_Activity_BAO_Activity::getActivitiesCount($contactId, FALSE, NULL, NULL);

      default:
        $custom = explode('_', $component);
        if ($custom['0'] = 'custom') {

          if (!$tableName) {
            $tableName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $custom['1'], 'table_name');
          }
          $queryString = "SELECT count(id) FROM {$tableName} WHERE entity_id = {$contactId}";
          return CRM_Core_DAO::singleValueQuery($queryString);
        }
    }
  }

  /**
   * Function to process greetings and cache
   *
   */
  static function processGreetings(&$contact) {
    // store object values to an array
    $contactDetails = [];
    CRM_Core_DAO::storeValues($contact, $contactDetails);
    $contactDetails = [[$contact->id => $contactDetails]];

    $emailGreetingString = $postalGreetingString = $addresseeString = NULL;
    $updateQueryString = [];


    //email greeting
    if ($contact->contact_type == 'Individual' || $contact->contact_type == 'Household') {
      if ($contact->email_greeting_custom != 'null' && $contact->email_greeting_custom) {
        $emailGreetingString = $contact->email_greeting_custom;
      }
      elseif ($contact->email_greeting_id != 'null' && $contact->email_greeting_id) {
        // the filter value for Individual contact type is set to 1
        $filter = ['contact_type' => $contact->contact_type,
          'greeting_type' => 'email_greeting',
        ];

        $emailGreeting = CRM_Core_PseudoConstant::greeting($filter);
        $emailGreetingString = $emailGreeting[$contact->email_greeting_id];
        $updateQueryString[] = " email_greeting_custom = NULL ";
      }
      elseif ($contact->email_greeting_custom) {
        $updateQueryString[] = " email_greeting_display = NULL ";
      }

      if ($emailGreetingString) {
        CRM_Utils_Token::replaceGreetingTokens($emailGreetingString, $contactDetails, $contact->id);
        $emailGreetingString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($emailGreetingString));
        $updateQueryString[] = " email_greeting_display = '{$emailGreetingString}'";
      }

      //postal greetings
      if ($contact->postal_greeting_custom != 'null' && $contact->postal_greeting_custom) {
        $postalGreetingString = $contact->postal_greeting_custom;
      }
      elseif ($contact->postal_greeting_id != 'null' && $contact->postal_greeting_id) {
        $filter = ['contact_type' => $contact->contact_type,
          'greeting_type' => 'postal_greeting',
        ];
        $postalGreeting = CRM_Core_PseudoConstant::greeting($filter);
        $postalGreetingString = $postalGreeting[$contact->postal_greeting_id];
        $updateQueryString[] = " postal_greeting_custom = NULL ";
      }
      elseif ($contact->postal_greeting_custom) {
        $updateQueryString[] = " postal_greeting_display = NULL ";
      }

      if ($postalGreetingString) {
        CRM_Utils_Token::replaceGreetingTokens($postalGreetingString, $contactDetails, $contact->id);
        $postalGreetingString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($postalGreetingString));
        $updateQueryString[] = " postal_greeting_display = '{$postalGreetingString}'";
      }
    }

    // addressee
    if ($contact->addressee_custom != 'null' && $contact->addressee_custom) {
      $addresseeString = $contact->addressee_custom;
    }
    elseif ($contact->addressee_id != 'null' && $contact->addressee_id) {
      $filter = ['contact_type' => $contact->contact_type,
        'greeting_type' => 'addressee',
      ];

      $addressee = CRM_Core_PseudoConstant::greeting($filter);
      $addresseeString = $addressee[$contact->addressee_id];
      $updateQueryString[] = " addressee_custom = NULL ";
    }
    elseif ($contact->addressee_custom) {
      $updateQueryString[] = " addressee_display = NULL ";
    }

    if ($addresseeString) {
      CRM_Utils_Token::replaceGreetingTokens($addresseeString, $contactDetails, $contact->id);
      $addresseeString = CRM_Core_DAO::escapeString(CRM_Utils_String::stripSpaces($addresseeString));
      $updateQueryString[] = " addressee_display = '{$addresseeString}'";
    }

    if (!empty($updateQueryString)) {
      $updateQueryString = CRM_Utils_Array::implode(',', $updateQueryString);
      $queryString = "UPDATE civicrm_contact SET {$updateQueryString} WHERE id = {$contact->id}";
      CRM_Core_DAO::executeQuery($queryString);
    }
  }

  /**
   * Function to retrieve loc block ids w/ given condition.
   *
   * @param  int    $contactId    contact id.
   * @param  array  $criteria     key => value pair which should be
   *                              fulfill by return record ids.
   * @param  string $condOperator operator use for grouping multiple conditions.
   *
   * @return array  $locBlockIds  loc block ids which fulfill condition.
   * @static
   */
  static function getLocBlockIds($contactId, $criteria = [], $condOperator = "AND") {
    $locBlockIds = [];
    if (!$contactId) {
      return $locBlockIds;
    }

    foreach (['Email', 'OpenID', 'Phone', 'Address', 'IM'] as $block) {
      $name = strtolower($block);
      $daoName = "CRM_Core_DAO_{$block}";
      $blockDAO = new $daoName();

      // build the condition.
      if (is_array($criteria)) {
        $fields =& $daoName::fields( );
        $conditions = [];
        foreach ($criteria as $field => $value) {
          if (CRM_Utils_Array::arrayKeyExists($field, $fields)) {
            $cond = "( $field = $value )";
            // value might be zero or null.
            if (!$value || strtolower($value) == 'null') {
              $cond = "( $field = 0 OR $field IS NULL )";
            }
            $conditions[] = $cond;
          }
        }
        if (!empty($conditions)) {
          $blockDAO->whereAdd(CRM_Utils_Array::implode(" $condOperator ", $conditions));
        }
      }

      $blockDAO->contact_id = $contactId;
      $blockDAO->find();
      while ($blockDAO->fetch()) {
        $locBlockIds[$name][] = $blockDAO->id;
      }
      $blockDAO->free();
    }

    return $locBlockIds;
  }

  /**
   * Function to build context menu items.
   *
   * @return array of context menu for logged in user.
   * @static
   */
  static function contextMenu() {
    $menu = [
      'view' => ['title' => ts('View Contact'),
        'weight' => 0,
        'ref' => 'view-contact',
        'key' => 'view',
        'permissions' => ['view all contacts'],
      ],
      'add' => ['title' => ts('Edit Contact'),
        'weight' => 0,
        'ref' => 'edit-contact',
        'key' => 'add',
        'permissions' => ['edit all contacts'],
      ],
      'delete' => ['title' => ts('Delete'),
        'weight' => 1,
        'weight' => 0,
        'ref' => 'delete-contact',
        'key' => 'delete',
        'permissions' => ['delete contacts', 'edit all contacts'],
      ],
      'contribution' => ['title' => ts('Add Contribution'),
        'weight' => 5,
        'ref' => 'new-contribution',
        'key' => 'contribution',
        'component' => 'CiviContribute',
        'href' => CRM_Utils_System::url('civicrm/contact/view/contribution',
          'reset=1&action=add&context=contribution'
        ),
        'permissions' => ['access CiviContribute',
          'edit contributions',
        ],
      ],
      'participant' => ['title' => ts('Register for Event'),
        'weight' => 10,
        'ref' => 'new-participant',
        'key' => 'participant',
        'component' => 'CiviEvent',
        'href' => CRM_Utils_System::url('civicrm/contact/view/participant', 'reset=1&action=add&context=participant'),
        'permissions' => ['access CiviEvent',
          'edit event participants',
        ],
      ],
      'activity' => ['title' => ts('Record Activity'),
        'weight' => 35,
        'ref' => 'new-activity',
        'key' => 'activity',
        'permissions' => ['edit all contacts'],
      ],
      'pledge' => ['title' => ts('Add Pledge'),
        'weight' => 15,
        'ref' => 'new-pledge',
        'key' => 'pledge',
        'href' => CRM_Utils_System::url('civicrm/contact/view/pledge',
          'reset=1&action=add&context=pledge'
        ),
        'component' => 'CiviPledge',
        'permissions' => ['access CiviPledge',
          'edit pledges',
        ],
      ],
      'membership' => ['title' => ts('Add Membership'),
        'weight' => 20,
        'ref' => 'new-membership',
        'key' => 'membership',
        'component' => 'CiviMember',
        'href' => CRM_Utils_System::url('civicrm/contact/view/membership',
          'reset=1&action=add&context=membership'
        ),
        'permissions' => ['access CiviMember',
          'edit memberships',
        ],
      ],
      'case' => ['title' => ts('Add Case'),
        'weight' => 25,
        'ref' => 'new-case',
        'key' => 'case',
        'component' => 'CiviCase',
        'href' => CRM_Utils_System::url('civicrm/contact/view/case',
          'reset=1&action=add&context=case'
        ),
        'permissions' => ['access all cases and activities'],
      ],
      'grant' => ['title' => ts('Add Grant'),
        'weight' => 26,
        'ref' => 'new-grant',
        'key' => 'grant',
        'component' => 'CiviGrant',
        'href' => CRM_Utils_System::url('civicrm/contact/view/grant',
          'reset=1&action=add&context=grant'
        ),
        'permissions' => ['edit grants'],
      ],
      'rel' => ['title' => ts('Add Relationship'),
        'weight' => 30,
        'ref' => 'new-relationship',
        'key' => 'rel',
        'href' => CRM_Utils_System::url('civicrm/contact/view/rel',
          'reset=1&action=add'
        ),
        'permissions' => ['edit all contacts'],
      ],
      'note' => ['title' => ts('Add Note'),
        'weight' => 40,
        'ref' => 'new-note',
        'key' => 'note',
        'href' => CRM_Utils_System::url('civicrm/contact/view/note',
          'reset=1&action=add'
        ),
        'permissions' => ['edit all contacts'],
      ],
      'email' => ['title' => ts('Send an Email'),
        'weight' => 45,
        'ref' => 'new-email',
        'key' => 'email',
        'permissions' => ['view all contacts'],
      ],
      'group' => ['title' => ts('Add to Group'),
        'weight' => 50,
        'ref' => 'group-add-contact',
        'key' => 'group',
        'permissions' => ['edit groups'],
      ],
      'tag' => ['title' => ts('Tag'),
        'weight' => 55,
        'ref' => 'tag-contact',
        'key' => 'tag',
        'permissions' => ['edit all contacts'],
      ],
    ];


    $providersCount = CRM_SMS_BAO_Provider::activeProviderCount();
    if ($providersCount) {
      $menu['sms'] = [
        'title' => ts('Send SMS'),
        'weight' => 46,
        'ref' => 'new-sms',
        'key' => 'sms',
        'permissions' => ['view all contacts'],
      ];
    }

    //1. check for component is active.
    //2. check for user permissions.
    //3. check for acls.
    //3. edit and view contact are directly accessible to user.


    $aclPermissionedTasks = ['view-contact', 'edit-contact', 'new-activity',
      'new-email', 'group-add-contact', 'tag-contact', 'delete-contact',
    ];
    $corePermission = CRM_Core_Permission::getPermission();

    $config = CRM_Core_Config::singleton();

    $contextMenu = [];
    foreach ($menu as $key => $values) {
      $componentName = CRM_Utils_Array::value('component', $values);

      // if component action - make sure component is enable.
      if ($componentName && !in_array($componentName, $config->enableComponents)) {
        continue;
      }

      // make sure user has all required permissions.
      $hasAllPermissions = FALSE;

      $permissions = CRM_Utils_Array::value('permissions', $values);
      if (!is_array($permissions) || empty($permissions)) {
        $hasAllPermissions = TRUE;
      }

      // iterate for required permissions in given permissions array.
      if (!$hasAllPermissions) {
        $hasPermissions = 0;
        foreach ($permissions as $permission) {
          if (CRM_Core_Permission::check($permission)) {
            $hasPermissions++;
          }
        }

        if (count($permissions) == $hasPermissions) {
          $hasAllPermissions = TRUE;
        }

        // if still user does not have required permissions, check acl.
        if (!$hasAllPermissions) {
          if (in_array($values['ref'], $aclPermissionedTasks) &&
            $corePermission == CRM_Core_Permission::EDIT
          ) {
            $hasAllPermissions = TRUE;
          }
          elseif (in_array($values['ref'], ['new-email'])) {
            // grant permissions for these tasks.
            $hasAllPermissions = TRUE;
          }
        }
      }

      // user does not have necessary permissions.
      if (!$hasAllPermissions) {
        continue;
      }

      // build directly accessible action menu.
      if (in_array($values['ref'], ['view-contact', 'edit-contact'])) {
        $contextMenu['primaryActions'][$key] = ['title' => $values['title'],
          'ref' => $values['ref'],
          'key' => $values['key'],
        ];
        continue;
      }

      // finally get menu item for -more- action widget.
      $contextMenu['moreActions'][$values['weight']] = ['title' => $values['title'],
        'ref' => $values['ref'],
        'href' => CRM_Utils_Array::value('href', $values),
        'key' => $values['key'],
      ];
    }

    ksort($contextMenu['moreActions']);

    return $contextMenu;
  }

  /**
   * Function to retrieve display name of contact that address is shared
   * based on $masterAddressId or $contactId .
   *
   * @param  int    $masterAddressId    master id.
   * @param  int    $contactId   contact id.
   *
   * @return display name |null the found display name or null.
   * @access public
   * @static
   */
  static function getMasterDisplayName($masterAddressId = NULL, $contactId = NULL) {
    $masterDisplayName = NULL;
    $sql = NULL;
    if (!$masterAddressId && !$contactId) {
      return $masterDisplayName;
    }

    if ($masterAddressId) {
      $sql = "
   SELECT display_name from civicrm_contact
LEFT JOIN civicrm_address ON ( civicrm_address.contact_id = civicrm_contact.id )
    WHERE civicrm_address.id = " . $masterAddressId;
    }
    elseif ($contactId) {
      $sql = "
   SELECT display_name from civicrm_contact cc, civicrm_address add1
LEFT JOIN civicrm_address add2 ON ( add1.master_id = add2.id )
    WHERE cc.id = add2.contact_id AND add1.contact_id = " . $contactId;
    }

    $masterDisplayName = CRM_Core_DAO::singleValueQuery($sql);
    return $masterDisplayName;
  }

  static function redirectPreferredLanguage($id, $url = NULL){
    if($_SERVER['REQUEST_METHOD'] == 'GET'){
      $contact = new CRM_Contact_DAO_Contact();
      $contact->id = $id;
      if ($contact->find(TRUE)) {
        $config = CRM_Core_Config::singleton();
        $ufLocale = $config->userSystem->getUFLocale();
        if(!empty($contact->preferred_language) && $contact->preferred_language != $ufLocale){
          if(empty($url)){
            $uri = parse_url($_SERVER['REQUEST_URI']);
          }
          else{
            $uri = parse_url($url);
          }

          // switch language
          $config->userSystem->switchUFLocale($contact->preferred_language);

          // make url in different language
          $base = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');
          $baseLang = $config->userSystem->languageNegotiationURL($base);
          if ($base != $baseLang) {
            $redirect = $baseLang.$_GET['q'].'?'.$uri['query'];
            if ($redirect !== $url) {
              CRM_Utils_System::redirect($redirect);
            }
          }
        }
      }
    }
  }
}

