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
   * Synchronize a UF user object with its corresponding CiviCRM contact.
   *
   * Ensures that a contact exists for the user and updates the CRM database if necessary.
   *
   * @param object &$user the CMS user object
   * @param bool $update whether the user object has been edited
   * @param string $uf the name of the user framework
   * @param string $ctype contact type
   * @param bool $isLogin whether this is a login operation
   *
   * @return void
   */
  public static function synchronize(&$user, $update, $uf, $ctype, $isLogin = FALSE) {
    $userSystem = CRM_Core_Config::singleton()->userSystem;
    $session = CRM_Core_Session::singleton();
    if (!is_object($session)) {
      CRM_Core_Error::fatal('wow, session is not an object?');
      return;
    }

    $userSystemID = $userSystem->getBestUFID($user);
    $uniqId = $userSystem->getBestUFUniqueIdentifier($user);

    // if the id of the object is zero (true for anon users in drupal)
    // have we already processed this user, if so early
    // return.
    $userID = $session->get('userID');
    $ufID = $session->get('ufID');

    if (!$update && $ufID == $userSystemID) {
      return;
    }

    //check do we have logged in user.
    $isUserLoggedIn = CRM_Utils_System::isUserLoggedIn();

    // reset the session if we are a different user
    if ($ufID != $userSystemID) {
      $session->reset(0);

      //get logged in user ids, and set to session.
      if ($isUserLoggedIn) {
        $userIds = self::getUFValues();
        $session->set('ufID', CRM_Utils_Array::value('uf_id', $userIds, ''));
        $session->set('userID', CRM_Utils_Array::value('contact_id', $userIds, ''));
      }
    }

    // return early
    if ($userSystemID == 0) {
      return;
    }

    $ufmatch = self::synchronizeUFMatch($user, $userSystemID, $uniqId, $uf, NULL, $ctype, $isLogin);
    if (!$ufmatch) {
      return;
    }

    //make sure we have session w/ consistent ids.
    $ufID = $ufmatch->uf_id;
    $userID = $ufmatch->contact_id;
    if ($isUserLoggedIn) {
      $loggedInUserUfID = CRM_Utils_System::getLoggedInUfID();
      //are we processing logged in user.
      if ($loggedInUserUfID && $loggedInUserUfID != $ufID) {
        $userIds = self::getUFValues($loggedInUserUfID);
        $ufID = CRM_Utils_Array::value('uf_id', $userIds, '');
        $userID = CRM_Utils_Array::value('contact_id', $userIds, '');
      }
    }

    //set user ids to session.
    $session->set('ufID', $ufID);
    $session->set('userID', $userID);

    // add current contact to recentlty viewed
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
   * Lower-level logic to synchronize a UF user with a CiviCRM contact.
   *
   * @param object &$user the CMS user object
   * @param string $userKey the unique ID from the user framework
   * @param string $uniqId the unique identifier (email or OpenID)
   * @param string $uf the name of the user framework
   * @param bool|null $status whether to return only the creation status
   * @param string|null $ctype contact type
   * @param bool $isLogin whether this is a login operation
   *
   * @return CRM_Core_DAO_UFMatch|bool|null matching object, creation status, or NULL
   */
  public static function &synchronizeUFMatch(&$user, $userKey, $uniqId, $uf, $status = NULL, $ctype = NULL, $isLogin = FALSE) {
    if (!CRM_Utils_Rule::email($uniqId)) {
      return $status ? NULL : FALSE;
    }

    $newContact = FALSE;

    // make sure that a contact id exists for this user id
    $ufmatch = new CRM_Core_DAO_UFMatch();
    if (CRM_Core_DAO::checkFieldExists('civicrm_uf_match', 'domain_id')) {
      $ufmatch->domain_id = CRM_Core_Config::domainID();
    }
    $ufmatch->uf_id = $userKey;
    if (!$ufmatch->find(TRUE)) {
      // very dirty way use POST as verify parameter
      if (!empty($_POST) && !$isLogin && isset($_POST['_qf_default'])) {
        $params = $_POST;
        $params['email'] = $uniqId;

        $dedupeParams = CRM_Dedupe_Finder::formatParams($params, 'Individual');
        $dedupeParams['check_permission'] = FALSE;
        $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');

        if (!empty($ids) && defined('CIVICRM_UNIQ_EMAIL_PER_SITE') && CIVICRM_UNIQ_EMAIL_PER_SITE) {
          // restrict dupeIds to ones that belong to current domain/site.
          $siteContacts = CRM_Core_BAO_Domain::getContactList();
          foreach ($ids as $index => $dupeId) {
            if (!in_array($dupeId, $siteContacts)) {
              unset($ids[$index]);
            }
          }
          // re-index the array
          $ids = array_values($ids);
        }
        if (!empty($ids)) {
          $dao = new CRM_Core_DAO();
          $dao->contact_id = $ids[0];
        }
        else {
          // not only verify by dedupe rule
          // also fallback to email only check, refs #26873
          if (isset($_POST['_qf_default']) && empty($_POST['last_name'])) {
            $dao = CRM_Contact_BAO_Contact::matchContactOnEmail($uniqId, $ctype);
          }
        }
      }
      else {
        $dao = CRM_Contact_BAO_Contact::matchContactOnEmail($uniqId, $ctype);
      }

      // refs #22380, we don't need transaction when dedupe
      $transaction = new CRM_Core_Transaction();
      if (!empty($dao)) {
        $ufmatch->contact_id = $dao->contact_id;
        $ufmatch->uf_name = $uniqId;
      }
      else {
        $params = ['email-Primary' => $uniqId];

        if ($ctype == 'Organization') {
          $params['organization_name'] = $uniqId;
        }
        elseif ($ctype == 'Household') {
          $params['household_name'] = $uniqId;
        }
        if (!$ctype) {
          $ctype = "Individual";
        }
        $params['contact_type'] = $ctype;

        // extract first / middle / last name
        // for joomla
        if ($uf == 'Joomla' && $user->name) {
          CRM_Utils_String::extractName($user->name, $params);
        }

        $contactId = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray);
        $ufmatch->contact_id = $contactId;
        $ufmatch->uf_name = $uniqId;
      }

      // check that there are not two CMS IDs matching the same CiviCRM contact - this happens when a civicrm
      // user has two e-mails and there is a cms match for each of them
      // the gets rid of the nasty fata error but still reports the error
      $sql = "
SELECT uf_id
FROM   civicrm_uf_match
WHERE  ( contact_id = %1
OR     uf_name      = %2
OR     uf_id        = %3 )
AND    domain_id    = %4
";
      $params = [1 => [$ufmatch->contact_id, 'Integer'],
        2 => [$ufmatch->uf_name, 'String'],
        3 => [$ufmatch->uf_id, 'Integer'],
        4 => [$ufmatch->domain_id, 'Integer'],
      ];

      $conflict = CRM_Core_DAO::singleValueQuery($sql, $params);

      if (!$conflict) {
        $ufmatch->save();
        $ufmatch->free();
        $newContact = TRUE;

        $transaction->commit();
      }
      else {
        $msg = ts(
          "Contact ID %1 is a match for %2 user %3 but has already been matched to %4",
          [1 => $ufmatch->contact_id,
            2 => $uf,
            3 => $ufmatch->uf_id,
            4 => $conflict,
          ]
        );
        CRM_Core_Error::debug_var('ufmatch_error', $msg);
        unset($conflict);
        // we don't need rollback transaction because we still need to create contact.
      }
    }

    if ($status) {
      return $newContact;
    }
    else {
      return $ufmatch;
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
