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
 *
 */
class CRM_Core_BAO_Domain extends CRM_Core_DAO_Domain {

  /**
   * Cache for the current domain object
   */
  public static $_domain = NULL;

  /**
   * Cache for a domain's location array
   */
  public $_location = NULL;

  /**
   * Retrieve a domain record based on the provided parameters.
   *
   * @param array $params associative array of name/value pairs
   * @param array $defaults associative array to hold the flattened values
   *
   * @return CRM_Core_DAO_Domain|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_Domain', $params, $defaults);
  }

  /**
   * Get the current domain BAO object.
   *
   * @return CRM_Core_BAO_Domain the current domain object
   */
  public static function &getDomain() {
    $config = CRM_Core_Config::singleton();
    if (!empty($config->domain->id)) {
      return $config->domain;
    }

    $domain = new CRM_Core_BAO_Domain();
    $domain->id = CRM_Core_Config::domainID();
    if (!$domain->find(TRUE)) {
      CRM_Core_Error::fatal();
    }
    return $domain;
  }

  /**
   * Get the version of the current domain.
   *
   * @return string|null domain version
   */
  public static function version() {
    return CRM_Core_DAO::getFieldValue(
      'CRM_Core_DAO_Domain',
      CRM_Core_Config::domainID(),
      'version'
    );
  }

  /**
   * Get the location values associated with the domain.
   *
   * @return array|null associative array of location values
   */
  public function &getLocationValues() {
    if ($this->_location == NULL) {
      $params = [
        'entity_id' => $this->id,
        'entity_table' => self::getTableName(),
      ];
      $this->_location = CRM_Core_BAO_Location::getValues($params, TRUE);

      if (empty($this->_location)) {
        $this->_location = NULL;
      }
    }
    return $this->_location;
  }

  /**
   * Update the values of an existing domain.
   *
   * @param array &$params associative array of domain data
   * @param int &$id domain ID
   *
   * @return CRM_Core_DAO_Domain updated domain object
   */
  public static function edit(&$params, &$id) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->id = $id;
    $domain->copyValues($params);
    $domain->save();
    return $domain;
  }

  /**
   * Create a new domain record.
   *
   * @param array $params associative array of domain data
   *
   * @return CRM_Core_DAO_Domain created domain object
   */
  public static function create($params) {
    $domain = new CRM_Core_DAO_Domain();
    $domain->copyValues($params);
    $domain->save();
    return $domain;
  }

  /**
   * Check if multiple domains exist in the system.
   *
   * @return bool TRUE if more than one domain exists, otherwise FALSE
   */
  public static function multipleDomains() {
    $session = CRM_Core_Session::singleton();

    $numberDomains = $session->get('numberDomains');
    if (!$numberDomains) {
      $query = "SELECT count(*) from civicrm_domain";
      $numberDomains = CRM_Core_DAO::singleValueQuery($query);
      $session->set('numberDomains', $numberDomains);
    }
    return $numberDomains > 1 ? TRUE : FALSE;
  }

  /**
   * Get the default 'from' name and email address for the domain.
   *
   * @return array [from_name, from_email]
   */
  public static function getNameAndEmail() {
    $config = CRM_Core_Config::singleton();
    if (!empty($config->domain->from) && !empty($config->domain->email)) {
      return [$config->domain->from, $config->domain->email];
    }

    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
    if (empty($fromEmailAddress)) {
      $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL);
    }
    if (!empty($fromEmailAddress)) {
      $addr = reset($fromEmailAddress);
      $email = CRM_Utils_Mail::pluckEmailFromHeader($addr);
      $fromName = CRM_Utils_Array::value(1, explode('"', $addr));
      return [$fromName, $email];
    }
    else {
      $url = CRM_Utils_System::url('civicrm/admin/options/from_email', 'reset=1&group=from_email_address');
      $status = ts("There is no valid default from email address configured for the domain. You can configure here <a href='%1'>Configure From Email Address.</a>", [1 => $url]);
      CRM_Core_Session::setStatus($status);
    }
  }

  /**
   * Add a contact to the system-wide domain group.
   *
   * @param int $contactID contact ID
   *
   * @return int|bool group ID on success, FALSE otherwise
   */
  public static function addContactToDomainGroup($contactID) {
    $groupID = self::getGroupId();

    if ($groupID) {
      $contactIDs = [$contactID];

      CRM_Contact_BAO_GroupContact::addContactsToGroup($contactIDs, $groupID);

      return $groupID;
    }
    return FALSE;
  }

  /**
   * Get the ID of the system-wide domain group.
   *
   * @return int|bool group ID if found, FALSE otherwise
   */
  public static function getGroupId() {
    static $groupID = NULL;

    if ($groupID) {
      return $groupID;
    }

    if (defined('CIVICRM_DOMAIN_GROUP_ID') && CIVICRM_DOMAIN_GROUP_ID) {
      $groupID = CIVICRM_DOMAIN_GROUP_ID;
    }
    elseif (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
      // create a group with that of domain name
      $title = CRM_Core_DAO::getFieldValue(
        'CRM_Core_DAO_Domain',
        CRM_Core_Config::domainID(),
        'name'
      );
      $groupID = CRM_Core_DAO::getFieldValue(
        'CRM_Contact_DAO_Group',
        $title,
        'id',
        'title'
      );
      if (empty($groupID) && !empty($title)) {
        $groupParams = ['title' => $title,
          'is_active' => 1,
          'no_parent' => 1,
        ];

        $group = CRM_Contact_BAO_Group::create($groupParams);
        $groupID = $group->id;
      }
    }
    return $groupID ? $groupID : FALSE;
  }

  /**
   * Check if a given group is the domain group.
   *
   * @param int $groupId group ID to check
   *
   * @return bool TRUE if it is the domain group, otherwise FALSE
   */
  public static function isDomainGroup($groupId) {
    $domainGroupID = self::getGroupId();
    return $domainGroupID == $groupId ? TRUE : FALSE;
  }

  /**
   * Get the IDs of all child groups of the domain group.
   *
   * @return int[] array of child group IDs
   */
  public static function getChildGroupIds() {
    $domainGroupID = self::getGroupId();
    $childGrps = [];

    if ($domainGroupID) {

      $childGrps = CRM_Contact_BAO_GroupNesting::getChildGroupIds($domainGroupID);
      $childGrps[] = $domainGroupID;
    }
    return $childGrps;
  }

  /**
   * Retrieve a list of contact IDs that belong to the current domain/site.
   *
   * @return int[] array of contact IDs
   */
  public static function getContactList() {
    $siteGroups = CRM_Core_BAO_Domain::getChildGroupIds();
    $siteContacts = [];

    if (!empty($siteGroups)) {
      $query = "
SELECT      cc.id
FROM        civicrm_contact cc
INNER JOIN  civicrm_group_contact gc ON 
           (gc.contact_id = cc.id AND gc.status = 'Added' AND gc.group_id IN (" . CRM_Utils_Array::implode(',', $siteGroups) . "))";

      $dao = &CRM_Core_DAO::executeQuery($query);
      while ($dao->fetch()) {
        $siteContacts[] = $dao->id;
      }
    }
    return $siteContacts;
  }
}
