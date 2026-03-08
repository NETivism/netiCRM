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
 * Utility functions for Organic Groups and CiviCRM integration
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */
class CRM_Bridge_OG_Utils {
  public const aclEnabled = 1, syncFromCiviCRM = 1;

  /**
   * Is ACL enabled
   *
   * @return int
   */
  public static function aclEnabled() {
    return self::aclEnabled;
  }

  /**
   * Sync from CiviCRM
   *
   * @return int
   */
  public static function syncFromCiviCRM() {
    // make sure that acls are not enabled
    return !self::aclEnabled & self::syncFromCiviCRM;
  }

  /**
   * OG sync name
   *
   * @param int $ogID
   *
   * @return string
   */
  public static function ogSyncName($ogID) {
    return "OG Sync Group :{$ogID}:";
  }

  /**
   * OG sync ACL name
   *
   * @param int $ogID
   *
   * @return string
   */
  public static function ogSyncACLName($ogID) {
    return "OG Sync Group ACL :{$ogID}:";
  }

  /**
   * Get OG ID
   *
   * @param int $groupID
   * @param bool $abort
   *
   * @return int|null
   */
  public static function ogID($groupID, $abort = TRUE) {
    $source = CRM_Core_DAO::getFieldValue(
      'CRM_Contact_DAO_Group',
      $groupID,
      'source'
    );

    if (strpos($source, 'OG Sync Group') !== FALSE) {
      preg_match('/:(\d+):$/', $source, $matches);
      if (is_numeric($matches[1])) {
        return $matches[1];
      }
    }
    if ($abort) {
      CRM_Core_Error::fatal();
    }
    return NULL;
  }

  /**
   * Get contact ID from UF ID
   *
   * @param int $ufID
   *
   * @return int
   */
  public static function contactID($ufID) {

    $contactID = civicrm_uf_match_id_get($ufID);
    if ($contactID) {
      return $contactID;
    }

    // else create a contact for this user
    $user = user_load(['uid' => $ufID]);
    $params = ['contact_type' => 'Individual',
      'email' => $user->mail,
    ];

    $values = civicrm_contact_add($params);
    if ($values['is_error']) {
      CRM_Core_Error::fatal();
    }
    return $values['contact_id'];
  }

  /**
   * Get group ID
   *
   * @param string $source
   * @param string $title
   * @param bool $abort
   *
   * @return int|null
   */
  public static function groupID($source, $title = NULL, $abort = FALSE) {
    $query = "
SELECT id
  FROM civicrm_group
 WHERE source = %1";
    $params = [1 => [$source, 'String']];

    if ($title) {
      $query .= " OR title = %2";
      $params[2] = [$title, 'String'];
    }

    $groupID = CRM_Core_DAO::singleValueQuery($query, $params);
    if ($abort &&
      !$groupID
    ) {
      CRM_Core_Error::fatal();
    }

    return $groupID;
  }
}
