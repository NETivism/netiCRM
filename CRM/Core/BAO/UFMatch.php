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

/**
 * The basic class that interfaces with the external user framework
 */
class CRM_Core_BAO_UFMatch extends CRM_Core_DAO_UFMatch {

  /**
   * Save UFMatch data.
   *
   * @param array &$params associative array of UFMatch data
   *
   * @return CRM_Core_DAO_UFMatch|null matching object
   */
  public static function create(&$params) {
    if ($params['contact_id'] && $params['uf_id'] && $params['uf_name']) {
      $ufmatch = new CRM_Core_DAO_UFMatch();
      $ufmatch->copyValues($params);
      if (empty($ufmatch->domain_id)) {
        $ufmatch->domain_id = CRM_Core_Config::domainID();
      }
      $ufmatch->find(TRUE);
      $ufmatch->save();
      return $ufmatch;
    }
    return NULL;
  }

  /**
   * Ensure the account has a contact association and synchronize CRM Session identity.
   *
   * Use this entry point when handling login or synchronizing an account in a
   * session context. synchronizeUFMatch() handles the database association only;
   * this method also manages Session ufID/userID and recent-contact items.
   * Validate the current user's persisted UFMatch before any early return,
   * ensure the supplied account has an association when needed, then read the
   * current user's UFMatch again before setting Session identity. Synchronizing
   * another account never switches the logged-in user's Session identity.
   *
   * @param object $user CMS account whose contact association should exist.
   * @param bool $update Whether to bypass the already-synchronized early return; this does not apply profile edits.
   * @param string $uf CMS framework name forwarded to synchronizeUFMatch().
   * @param string|null $ctype Contact type to use if a new contact is needed.
   * @param bool $isLogin Legacy login-context argument; it does not enable registration dedupe.
   *
   * @return void
   */
  public static function synchronize(&$user, $update, $uf, $ctype, $isLogin = FALSE) {
    $system = CRM_Core_Config::singleton()->userSystem;
    $uid = (int) $system->getBestUFID($user);
    $currentUID = (int) CRM_Utils_System::getLoggedInUfID();
    $ufMatch = self::refreshSession();
    if (!$update && $uid === $currentUID && $ufMatch) {
      return;
    }
    if ($uid > 0) {
      self::synchronizeUFMatch($user, $uid, $system->getBestUFUniqueIdentifier($user), $uf, NULL, $ctype, $isLogin);
    }
    // Do not use a returned/transient DAO, even if the operation succeeded.
    $ufMatch = self::refreshSession();
    if ($ufMatch) {
      self::addUFMatchToRecent($ufMatch);
    }
  }

  /**
   * Add the synchronized contact to recent items after identity validation.
   *
   * @param CRM_Core_DAO_UFMatch $ufmatch Verified account/contact association.
   * @return void
   */
  private static function addUFMatchToRecent($ufmatch) {
    if ($ufmatch->contact_id) {

      list($displayName, $contactImage, $contactType, $contactSubtype, $contactImageUrl) = CRM_Contact_BAO_Contact::getDisplayAndImage($ufmatch->contact_id, TRUE, TRUE);

      $otherRecent = ['imageUrl' => $contactImageUrl,
        'subtype' => $contactSubtype,
        'editUrl' => CRM_Utils_System::url('civicrm/contact/add', "reset=1&action=update&cid={$ufmatch->contact_id}"),
      ];

      CRM_Utils_Recent::add(
        $displayName,
        CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid={$ufmatch->contact_id}"),
        $ufmatch->contact_id,
        $contactType,
        $ufmatch->contact_id,
        $displayName,
        $otherRecent
      );
    }
  }

  /**
   * Get or create the database association between a CMS account and a contact.
   *
   * Use this entry point when the caller needs a UFMatch result without assigning
   * Session ufID/userID. synchronize() wraps this operation with Session handling.
   * An existing association is reused; otherwise a new contact and UFMatch are
   * created, regardless of framework. This method never matches an existing
   * contact by email. Registration dedupe is available only through
   * UFMatchRegistration::register().
   *
   * @param object $user CMS account object, retained for caller compatibility; not read here.
   * @param int|string $userKey CMS user ID to associate in the current domain.
   * @param string $uniqId CMS account email stored as uf_name; must pass email validation.
   * @param string $uf CMS framework name, retained for caller compatibility; does not change behavior.
   * @param bool|null $status TRUE returns whether a new UFMatch was created, rather than the object.
   * @param string|null $ctype Contact type for creation; defaults to Individual.
   * @param bool $isLogin Retained for caller compatibility; currently does not change this method's behavior.
   *
   * @return CRM_Core_DAO_UFMatch|bool|null UFMatch object, creation status when requested, or FALSE/NULL on failure.
   */
  public static function &synchronizeUFMatch(&$user, $userKey, $uniqId, $uf, $status = NULL, $ctype = NULL, $isLogin = FALSE) {
    $result = self::createUFMatch($userKey, $uniqId, $ctype ?: 'Individual');
    $value = $status ? ($result['created'] && !empty($result['ufMatch'])) : $result['ufMatch'];
    return $value;
  }

  /**
   * Read an unambiguous, live UFMatch without the getUFValues() static cache.
   *
   * @param int|string $ufID CMS user ID to look up in the current domain.
   * @return CRM_Core_DAO_UFMatch|null Unique live association, or NULL if invalid.
   */
  public static function getPersistentUFMatch($ufID) {
    if (!ctype_digit((string) $ufID) || (int) $ufID <= 0) {
      return NULL;
    }
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT m.id, m.uf_id, m.uf_name, m.contact_id, m.domain_id,
              c.id AS live_contact_id, c.is_deleted
       FROM civicrm_uf_match m
       LEFT JOIN civicrm_contact c ON c.id = m.contact_id
       WHERE m.uf_id = %1 AND m.domain_id = %2",
      [1 => [(int) $ufID, 'Integer'], 2 => [CRM_Core_Config::domainID(), 'Integer']],
      TRUE,
      'CRM_Core_DAO_UFMatch'
    );
    // Multiple UFMatch rows for a UID are corruption, not a choice of identity.
    if ((int) $dao->N !== 1 || !$dao->fetch() || !$dao->live_contact_id || (int) $dao->is_deleted !== 0) {
      return NULL;
    }
    return $dao;
  }

  /**
   * Validate the current framework identity before using any CiviCRM session data.
   * This method never creates contacts and never accepts a caller's user object.
   *
   * @return CRM_Core_DAO_UFMatch|null Verified association, or NULL when no identity can be established.
   */
  public static function refreshSession() {
    $uid = (int) CRM_Utils_System::getLoggedInUfID();
    $session = CRM_Core_Session::singleton();
    try {
      $ufMatch = self::getPersistentUFMatch($uid);
    }
    catch (Throwable $e) {
      $session->reset(0);
      throw $e;
    }
    $sessionUID = $session->get('ufID');
    $sessionCID = $session->get('userID');
    $matches = $ufMatch && $uid > 0
      && (int) $ufMatch->uf_id === $uid
      && (int) $ufMatch->domain_id === (int) CRM_Core_Config::domainID()
      && (int) $sessionUID === $uid
      && (int) $sessionCID === (int) $ufMatch->contact_id;
    if (!$matches && ($uid > 0 || $sessionUID !== NULL || $sessionCID !== NULL)) {
      $session->reset(0);
    }
    if (!$matches && CRM_Core_Transaction::isActive()) {
      // A row visible on this connection may still roll back. Do not establish
      // a new session identity until the outermost CRM transaction commits.
      return NULL;
    }
    if ($ufMatch) {
      $session->set('ufID', (int) $ufMatch->uf_id);
      $session->set('userID', (int) $ufMatch->contact_id);
    }
    return $ufMatch;
  }

  /**
   * Ordinary login/programmatic creation: reuse a UFMatch or create a contact.
   * Registration owns its separate transaction and is the only dedupe caller.
   *
   * @param int|string $uid CMS user ID.
   * @param string $email Email address obtained from the CMS account.
   * @param string $ctype Contact type to use when creating a contact.
   * @return array Keys: ufMatch (DAO or NULL), created (whether a contact was created).
   */
  public static function createUFMatch($uid, $email, $ctype = 'Individual') {
    $empty = ['ufMatch' => NULL, 'created' => FALSE];
    if ((int) $uid <= 0 || !CRM_Utils_Rule::email($email)) {
      return $empty;
    }
    $domain = CRM_Core_Config::domainID();
    $transaction = new CRM_Core_Transaction();
    try {
      // Serialize UFMatch creation: the legacy schema has no UID/domain index.
      CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_domain WHERE id = %1 FOR UPDATE', [1 => [$domain, 'Integer']]);
      $existing = self::getPersistentUFMatch($uid);
      if ($existing) {
        $transaction->commit();
        return ['ufMatch' => self::getPersistentUFMatch($uid), 'created' => FALSE];
      }
      if (self::hasUFMatchConflict($uid, $email)) {
        $transaction->rollback();
        $transaction->commit();
        return $empty;
      }
      $contactID = self::createContactForUFMatch($email, [], [], $ctype);
      self::saveUFMatch($uid, $email, $contactID);
      $transaction->commit();
      if (!CRM_Core_Transaction::isActive()) {
        CRM_Core_Error::debug_log_message('UFMatch synchronization: created');
      }
      return ['ufMatch' => self::getPersistentUFMatch($uid), 'created' => TRUE];
    }
    catch (Throwable $e) {
      $transaction->rollback();
      $transaction->commit();
      throw $e;
    }
  }

  /**
   * Check under the caller's domain lock, before creating or updating a contact.
   *
   * @param int|string $uid CMS user ID to check.
   * @param string $email CMS account email to check for an existing uf_name.
   * @return bool Whether the current domain contains a conflicting UID or email.
   */
  public static function hasUFMatchConflict($uid, $email) {
    return (bool) CRM_Core_DAO::singleValueQuery(
      'SELECT id FROM civicrm_uf_match WHERE domain_id = %1 AND (uf_id = %2 OR uf_name = %3)',
      [1 => [CRM_Core_Config::domainID(), 'Integer'], 2 => [$uid, 'Integer'], 3 => [$email, 'String']]
    );
  }

  /**
   * Create a contact with the account email as primary. Caller owns transaction.
   * Values/fields must come from the validated registration profile, or be empty.
   *
   * @param string $email CMS account email, used as the new contact's primary email.
   * @param array $values Validated registration values, or an empty array for ordinary synchronization.
   * @param array $fields Enabled registration field definitions keyed by field name.
   * @param string $ctype Contact type to create.
   * @return int|string ID of the newly created contact.
   */
  public static function createContactForUFMatch($email, array $values, array $fields, $ctype = 'Individual') {
    $config = CRM_Core_Config::singleton();
    $resetCache = $config->doNotResetCache;
    try {
      // Keep permitted secondary emails, but choose the primary identity
      // from the account even if the profile submits another primary value.
      unset($values['email'], $values['email-Primary']);
      $values = ['email-Primary' => $email] + $values;
      $values['contact_type'] = $ctype;
      if ($ctype === 'Organization' && empty($values['organization_name'])) {
        $values['organization_name'] = $email;
      }
      elseif ($ctype === 'Household' && empty($values['household_name'])) {
        $values['household_name'] = $email;
      }
      $contactID = CRM_Contact_BAO_Contact::createProfileContact($values, $fields, NULL, NULL, NULL, $ctype);
      $emailID = CRM_Core_DAO::singleValueQuery(
        'SELECT id FROM civicrm_email WHERE contact_id = %1 AND email = %2 ORDER BY is_primary DESC, id LIMIT 1',
        [1 => [$contactID, 'Integer'], 2 => [$email, 'String']]
      );
      if (!$emailID) {
        $location = CRM_Core_BAO_LocationType::getDefault();
        $primary = ['contact_id' => $contactID, 'email' => $email, 'location_type_id' => $location->id];
        $emailDAO = CRM_Core_BAO_Email::add($primary);
        $emailID = $emailDAO->id;
      }
      // Only the newly-created contact is affected; existing contacts retain
      // every primary flag through the fill-only branch.
      CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_email SET is_primary = CASE WHEN id = %1 THEN 1 ELSE 0 END WHERE contact_id = %2',
        [1 => [$emailID, 'Integer'], 2 => [$contactID, 'Integer']]
      );
      return $contactID;
    }
    finally {
      $config->doNotResetCache = $resetCache;
    }
  }

  /**
   * Write only the UFMatch row. Caller owns the transaction, lock and contact choice.
   * Session callers must query the persisted UFMatch again after commit.
   *
   * @param int|string $uid CMS user ID.
   * @param string $email CMS account email to store as uf_name.
   * @param int|string $contactID Contact selected or created by the caller.
   * @return void
   */
  public static function saveUFMatch($uid, $email, $contactID) {
    $match = new CRM_Core_DAO_UFMatch();
    $match->uf_id = $uid;
    $match->uf_name = $email;
    $match->domain_id = CRM_Core_Config::domainID();
    $match->contact_id = $contactID;
    $match->save();
    if (!CRM_Core_Transaction::willCommit()) {
      throw new CRM_Core_Exception(ts('Unable to link the account to a contact.'));
    }
  }

  /**
   * Update the uf_name in the user object based on the contact's primary identifier.
   *
   * @param int $contactId contact ID
   *
   * @return void
   */
  public static function updateUFName($contactId) {
    if (!$contactId) {
      return;
    }
    $config = CRM_Core_Config::singleton();
    if ($config->userFramework == 'Standalone') {
      $ufName = CRM_Contact_BAO_Contact::getPrimaryOpenId($contactId);
    }
    else {
      $ufName = CRM_Contact_BAO_Contact::getPrimaryEmail($contactId);
    }

    if (!$ufName) {
      return;
    }

    $update = FALSE;

    // 1.do check for contact Id.
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    if (!$ufmatch->find(TRUE)) {
      return;
    }
    if ($ufmatch->uf_name != $ufName) {
      $update = TRUE;
    }

    // CRM-6928
    // 2.do check for duplicate ufName.
    $ufDupeName = new CRM_Core_DAO_UFMatch();
    $ufDupeName->uf_name = $ufName;
    $ufDupeName->domain_id = CRM_Core_Config::domainID();
    if ($ufDupeName->find(TRUE) &&
      $ufDupeName->contact_id != $contactId
    ) {
      $update = FALSE;
    }

    if (!$update) {

      return;

    }
    // save the updated ufmatch object
    $ufmatch->uf_name = $ufName;
    $ufmatch->save();

    $config->userSystem->updateCMSName($ufmatch->uf_id, $ufName);
  }

  /**
   * Update the email address for both the contact and their user profile.
   *
   * @param int $contactId contact ID
   * @param string $emailAddress new email address
   *
   * @return void
   */
  public static function updateContactEmail($contactId, $emailAddress) {
    $emailAddress = mb_strtolower($emailAddress, 'UTF-8');

    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->contact_id = $contactId;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    if ($ufmatch->find(TRUE)) {
      // Save the email in UF Match table
      $ufmatch->uf_name = $emailAddress;
      $ufmatch->save();

      //check if the primary email for the contact exists
      //$contactDetails[1] - email
      //$contactDetails[3] - email id

      $contactDetails = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);

      if (trim($contactDetails[1])) {
        $emailID = $contactDetails[3];
        //update if record is found
        $query = "UPDATE  civicrm_email
                     SET email = %1
                     WHERE id =  %2";
        $p = [1 => [$emailAddress, 'String'],
          2 => [$emailID, 'Integer'],
        ];
        $dao = &CRM_Core_DAO::executeQuery($query, $p);
      }
      else {
        //else insert a new email record

        $email = new CRM_Core_DAO_Email();
        $email->contact_id = $contactId;
        $email->is_primary = 1;
        $email->email = $emailAddress;
        $email->save();
        $emailID = $email->id;
      }

      CRM_Core_BAO_Log::register(
        $contactId,
        'civicrm_email',
        $emailID
      );
    }
  }

  /**
   * Delete the UF match records associated with a CMS user.
   *
   * @param int $ufID CMS user ID
   *
   * @return void
   */
  public static function deleteUser($ufID) {
    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->uf_id = $ufID;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    $ufmatch->delete();
  }

  /**
   * Get the contact ID for a given user framework ID.
   *
   * @param int $ufID CMS user ID
   *
   * @return int|null contact ID if found
   */
  public static function getContactId($ufID) {
    if (!isset($ufID)) {
      return NULL;
    }

    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->uf_id = $ufID;
    $ufmatch->domain_id = CRM_Core_Config::domainID();
    if ($ufmatch->find(TRUE)) {
      return (int ) $ufmatch->contact_id;
    }
    return NULL;
  }

  /**
   * Get the user framework ID for a given contact ID.
   *
   * @param int $contactID contact ID
   *
   * @return int|null CMS user ID if found
   */
  public static function getUFId($contactID) {
    if (!isset($contactID)) {
      return NULL;
    }

    $ufmatch = new CRM_Core_DAO_UFMatch();

    $ufmatch->contact_id = $contactID;
    if ($ufmatch->find(TRUE)) {
      return $ufmatch->uf_id;
    }
    return NULL;
  }

  public static function isEmptyTable() {
    $sql = "SELECT count(id) FROM civicrm_uf_match";
    return CRM_Core_DAO::singleValueQuery($sql) > 0 ? FALSE : TRUE;
  }

  /**
   * Get a list of all contact IDs present in the match table.
   *
   * @return int[] array of contact IDs
   */
  public static function getContactIDs() {
    $id = [];
    $dao = new CRM_Core_DAO_UFMatch();
    $dao->find();
    while ($dao->fetch()) {
      $id[] = $dao->contact_id;
    }
    return $id;
  }

  /**
   * Check if a specific user is allowed to login based on their identifier.
   *
   * @param string $openId the user's OpenID or identifier
   *
   * @return bool TRUE if allowed to login, FALSE otherwise
   */
  public static function getAllowedToLogin($openId) {
    $ufmatch = new CRM_Core_DAO_UFMatch();
    $ufmatch->uf_name = $openId;
    $ufmatch->allowed_to_login = 1;
    if ($ufmatch->find(TRUE)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get the next unused UF ID value.
   *
   * Useful for frameworks like Standalone that don't provide numeric IDs.
   *
   * @return int next highest unused UF ID
   */
  public static function getNextUfIdValue() {
    $query = "SELECT MAX(uf_id)+1 AS next_uf_id FROM civicrm_uf_match";
    $dao = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {
      $ufId = $dao->next_uf_id;
    }

    if (!isset($ufId)) {
      $ufId = 1;
    }
    return $ufId;
  }

  public static function isDuplicateUser($email) {
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if (!empty($email) &&
      isset($contactID)
    ) {
      $dao = new CRM_Core_DAO_UFMatch();
      $dao->uf_name = $email;
      if ($dao->find(TRUE) &&
        $contactID != $dao->contact_id
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get UF match values for a specific UF ID or the current logged-in user.
   *
   * @param int|null $ufID optional CMS user ID
   *
   * @return array associative array of UF match values
   */
  public static function getUFValues($ufID = NULL) {
    if (!$ufID) {
      $ufID = CRM_Utils_System::getLoggedInUfID();
    }
    if (!$ufID) {
      return [];
    }

    static $ufValues;
    if ($ufID && !isset($ufValues[$ufID])) {
      $ufmatch = new CRM_Core_DAO_UFMatch();
      $ufmatch->uf_id = $ufID;
      $ufmatch->domain_id = CRM_Core_Config::domainID();
      if ($ufmatch->find(TRUE)) {
        $ufValues[$ufID] = ['uf_id' => $ufmatch->uf_id,
          'uf_name' => $ufmatch->uf_name,
          'contact_id' => $ufmatch->contact_id,
          'domain_id' => $ufmatch->domain_id,
        ];
      }
    }

    return $ufValues[$ufID];
  }
}
