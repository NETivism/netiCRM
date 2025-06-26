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


class CRM_Contact_BAO_Group extends CRM_Contact_DAO_Group {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * group_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Contact_BAO_Group object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $group = new CRM_Contact_DAO_Group();
    $group->copyValues($params);
    if ($group->find(TRUE)) {
      CRM_Core_DAO::storeValues($group, $defaults);

      return $group;
    }

    return NULL;
  }

  /**
   * Function to delete the group and all the object that connect to
   * this group. Incredibly destructive
   *
   * @param int $id group id
   *
   * @return null
   * @access public
   * @static
   *
   */
  static function discard($id) {


    CRM_Utils_Hook::pre('delete', 'Group', $id, CRM_Core_DAO::$_nullArray);


    $transaction = new CRM_Core_Transaction();

    // added for CRM-1631 and CRM-1794
    // delete all subscribed mails with the selected group id

    $subscribe = new CRM_Mailing_Event_BAO_Subscribe();
    $subscribe->deleteGroup($id);

    // delete all Subscription  records with the selected group id
    $subHistory = new CRM_Contact_DAO_SubscriptionHistory();
    $subHistory->group_id = $id;
    $subHistory->delete();

    // delete all crm_group_contact records with the selected group id

    $groupContact = new CRM_Contact_DAO_GroupContact();
    $groupContact->group_id = $id;
    $groupContact->delete();

    // make all the 'add_to_group_id' field of 'civicrm_uf_group table', pointing to this group, as null
    $params = [1 => [$id, 'Integer']];
    $query = "update civicrm_uf_group SET `add_to_group_id`= NULL where `add_to_group_id` = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    $query = "update civicrm_uf_group SET `limit_listings_group_id`= NULL where `limit_listings_group_id` = %1";
    CRM_Core_DAO::executeQuery($query, $params);

    if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE) {
      // clear any descendant groups cache if exists

      $finalGroups = &CRM_Core_BAO_Cache::deleteGroup('descendant groups for an org');
    }

    // delete from group table
    $group = new CRM_Contact_DAO_Group();
    $group->id = $id;
    $group->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Group', $id, $group);

    // delete the recently created Group

    $groupRecent = [
      'id' => $id,
      'type' => 'Group',
    ];
    CRM_Utils_Recent::del($groupRecent);
  }

  /**
   * Returns an array of the contacts in the given group.
   *
   * @param int id group id
   *
   * @param string status One of Added, Removed, Pending. Empty for get all status. default 'Added'.
   *
   * @return array
   */
  static function getGroupContacts($id, $status = 'Added') {
    $search = [
      'contact_is_deleted' => 0,
      'group' => [$id => 1],
    ];
    if ($status !== 'Added') {
      $search['group_contact_status'] = [$status => 1];
    }
    if (empty($status)) {
      $groupContact = new CRM_Contact_DAO_GroupContact();
      $fields = $groupContact->fields();
      $statuses = explode(',', $fields['status']['enumValues']);
      foreach($statuses as $status) {
        $status = trim($status);
        $search['group_contact_status'][$status] = 1;
      }
    }
    $params = CRM_Contact_BAO_Query::convertFormValues($search);
    $smartGroupCache = TRUE;
    $returnProperties = ['contact_id' => 1];
    list($contacts, $options) = CRM_Contact_BAO_Query::apiQuery(
      $params,
      $returnProperties,
      NULL,
      NULL,
      0,
      0,
      $smartGroupCache
    );
    return $contacts;
  }

  /**
   * Returns count of contacts in the given group.
   *
   * @return int
   */
  static function getGroupContactsCount($id, $status = 'Added') {
    return count(self::getGroupContacts($id, $status));
  }

  /**
   * Get the count of a members in a group with the specific status
   *
   * @param int $id      group id
   * @param enum $status status of members in group
   *
   * @return int count of members in the group with above status
   * @access public
   */
  static function memberCount($id, $status = 'Added', $countChildGroups = FALSE) {
    if (!$countChildGroups) {
      if (empty($status)) {
        return CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_group_contact gc INNER JOIN civicrm_contact c on gc.contact_id = c.id WHERE c.is_deleted = 0 AND gc.group_id = %1", [
          1 => [$id, 'Integer']
        ]);
      }
      else {
        return CRM_Core_DAO::singleValueQuery("SELECT count(*) FROM civicrm_group_contact gc INNER JOIN civicrm_contact c on gc.contact_id = c.id WHERE c.is_deleted = 0 AND gc.status = %1 AND gc.group_id = %2", [
          1 => [$status, 'String'],
          2 => [$id, 'Integer']
        ]);
      }
    }
    else {
      return self::getGroupContactsCount($id, $status);
    }
  }

  /**
   * Get the list of member for a group id
   *
   * @param int $lngGroupId this is group id
   *
   * @return array $aMembers this arrray contains the list of members for this group id
   * @access public
   * @static
   */
  static function &getMember($groupID, $useCache = TRUE) {
    $params['group'] = [$groupID => 1];
    $params['return.contact_id'] = 1;
    $params['offset'] = 0;
    $params['rowCount'] = 0;
    $params['sort'] = NULL;
    $params['smartGroupCache'] = $useCache;

    require_once 'api/v2/Contact.php';

    $contacts = civicrm_contact_search($params);

    $aMembers = [];
    foreach ($contacts as $contact) {
      $aMembers[$contact['contact_id']] = 1;
    }

    return $aMembers;
  }

  /**
   * Returns array of group object(s) matching a set of one or Group properties.
   *
   * @param array       $param             Array of one or more valid property_name=>value pairs.
   *                                       Limits the set of groups returned.
   * @param array       $returnProperties  Which properties should be included in the returned group objects.
   *                                       (member_count should be last element.)
   *
   * @return  An array of group objects.
   *
   * @access public
   */
  static function getGroups($params = NULL, $returnProperties = NULL) {
    $dao = new CRM_Contact_DAO_Group();
    $dao->is_active = 1;
    if ($params) {
      foreach ($params as $k => $v) {
        if ($k == 'name' || $k == 'title') {
          $dao->whereAdd($k . ' LIKE "' . CRM_Core_DAO::escapeString($v) . '"');
        }
        elseif (is_array($v)) {
          $dao->whereAdd($k . ' IN (' . CRM_Utils_Array::implode(',', $v) . ')');
        }
        else {
          $dao->$k = $v;
        }
      }
    }
    // return only specific fields if returnproperties are sent
    if (!empty($returnProperties)) {
      $dao->selectAdd();
      $dao->selectAdd(CRM_Utils_Array::implode(',', $returnProperties));
    }
    $dao->find();

    $flag = $returnProperties && in_array('member_count', $returnProperties) ? 1 : 0;

    $groups = [];
    while ($dao->fetch()) {
      $group = new CRM_Contact_DAO_Group();
      if ($flag) {
        $dao->member_count = CRM_Contact_BAO_Group::memberCount($dao->id);
      }
      $groups[] = clone($dao);
    }
    return $groups;
  }

  /**
   * make sure that the user has permission to access this group
   *
   * @param int $id   the id of the object
   * @param int $name the name or title of the object
   *
   * @return string   the permission that the user has (or null)
   * @access public
   * @static
   */
  static function checkPermission($id, $title) {



    $allGroups = CRM_Core_PseudoConstant::allGroup();

    $permissions = NULL;
    if (CRM_Core_Permission::check('edit all contacts') ||
      CRM_ACL_API::groupPermission(CRM_ACL_API::EDIT, $id, NULL,
        'civicrm_saved_search', $allGroups
      )
    ) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }

    if (CRM_Core_Permission::check('view all contacts') ||
      CRM_ACL_API::groupPermission(CRM_ACL_API::VIEW, $id, NULL,
        'civicrm_saved_search', $allGroups
      )
    ) {
      $permissions[] = CRM_Core_Permission::VIEW;
    }

    if (!empty($permissions) && CRM_Core_Permission::check('delete contacts')) {
      // Note: using !empty() in if condition, restricts the scope of delete
      // permission to groups/contacts that are editable/viewable.
      // We can remove this !empty condition once we have ACL support for delete functionality.
      $permissions[] = CRM_Core_Permission::DELETE;
    }

    return $permissions;
  }

  /**
   * Create a new group
   *
   * @param array $params     Associative array of parameters
   *
   * @return object|null      The new group BAO (if created)
   * @access public
   * @static
   */
  public static function &create(&$params) {


    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'Group', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Group', NULL, $params);
    }

    // form the name only if missing: CRM-627
    if (!CRM_Utils_Array::value('name', $params) && !CRM_Utils_Array::value('id', $params)) {
      $params['name'] = CRM_Utils_String::titleToVar($params['title']);
      $params['name'] .= '_'.CRM_Utils_String::createRandom(2, CRM_Utils_String::ALPHANUMERIC);
    }

    // convert params if array type
    if (isset($params['group_type'])) {
      if (is_array($params['group_type'])) {
        $params['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . CRM_Utils_Array::implode(CRM_Core_DAO::VALUE_SEPARATOR,
          array_keys($params['group_type'])
        ) . CRM_Core_DAO::VALUE_SEPARATOR;
      }
      elseif (is_string ($params['group_type'])) {
        $type = explode(',', $params['group_type']);
        if (is_array($type)) {
          $params['group_type'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR,
          array_values($type)
          ) . CRM_Core_DAO::VALUE_SEPARATOR;
        }
      }
    }
    else {
      $params['group_type'] = '';
    }

    // CRM-19068.
    // Validate parents parameter when creating group.
    if (isset($params['parents']) && !empty($params['parents'])) {
      if (is_array($params['parents'])) {
        foreach ($params['parents'] as $parent => $dc) {
          CRM_Utils_Type::validate($parent, 'Integer');
        }
      }
      else {
        CRM_Utils_Type::validate($params['parents'], 'Integer');
      }
    }

    $group = new CRM_Contact_BAO_Group();
    $group->copyValues($params);
    $group->save();

    if (!$group->id) {
      return NULL;
    }

    $group->buildClause();
    $group->save();

    // add custom field values
    if (CRM_Utils_Array::value('custom', $params)) {

      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_group', $group->id);
    }

    // make the group, child of domain/site group by default.



    $domainGroupID = CRM_Core_BAO_Domain::getGroupId();
    if (CRM_Utils_Array::value('no_parent', $params) !== 1) {
      if (defined('CIVICRM_MULTISITE') && CIVICRM_MULTISITE &&
        empty($params['parents']) && ($domainGroupID != $group->id) &&
        !CRM_Contact_BAO_GroupNesting::hasParentGroups($group->id)
      ) {
        // if no parent present and the group doesn't already have any parents,
        // make sure site group goes as parent
        $params['parents'] = [$domainGroupID => 1];
      }
      elseif (CRM_Utils_Array::arrayKeyExists('parents', $params) && !is_array($params['parents'])) {
        $params['parents'] = [$params['parents'] => 1];
      }

      if (!empty($params['parents'])) {
        foreach ($params['parents'] as $parentId => $dnc) {
          if ($parentId && !CRM_Contact_BAO_GroupNesting::isParentChild($parentId, $group->id)) {
            CRM_Contact_BAO_GroupNesting::add($parentId, $group->id);
          }
        }
      }

      // clear any descendant groups cache if exists

      $finalGroups = &CRM_Core_BAO_Cache::deleteGroup('descendant groups for an org');

      // this is always required, since we don't know when a
      // parent group is removed

      CRM_Contact_BAO_GroupNestingCache::update();

      // update group contact cache for all parent groups
      $parentIds = CRM_Contact_BAO_GroupNesting::getParentGroupIds($group->id);
      foreach ($parentIds as $parentId) {
        CRM_Contact_BAO_GroupContactCache::add($parentId);
      }
    }

    if (CRM_Utils_Array::value('organization_id', $params)) {

      $groupOrg = [];
      $groupOrg = $params;
      $groupOrg['group_id'] = $group->id;
      CRM_Contact_BAO_GroupOrganization::add($groupOrg);
    }

    CRM_Contact_BAO_GroupContactCache::add($group->id);

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'Group', $group->id, $group);
    }
    else {
      CRM_Utils_Hook::post('create', 'Group', $group->id, $group);
    }

    $recentOther = [];
    if (CRM_Core_Permission::check('edit groups')) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/group', 'reset=1&action=update&id=' . $group->id);
      // currently same permission we are using for delete a group
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/group', 'reset=1&action=delete&id=' . $group->id);
    }


    // add the recently added group (unless hidden: CRM-6432)
    if (!$group->is_hidden) {
      CRM_Utils_Recent::add($group->title,
        CRM_Utils_System::url('civicrm/group/search', 'reset=1&force=1&context=smog&gid=' . $group->id),
        $group->id,
        'Group',
        NULL,
        NULL,
        $recentOther
      );
    }
    return $group;
  }

  /**
   * given a saved search compute the clause and the tables
   * and store it for future use
   */
  function buildClause() {
    $params = [['group', 'IN', [$this->id => 1], 0, 0]];

    if (!empty($params)) {
      $tables = $whereTables = [];

      $this->where_clause = CRM_Contact_BAO_Query::getWhereClause($params, NULL, $tables, $whereTables);
      if (!empty($tables)) {
        $this->select_tables = serialize($tables);
      }
      if (!empty($whereTables)) {
        $this->where_tables = serialize($whereTables);
      }
    }

    return;
  }

  /**
   * Defines a new group (static or query-based)
   *
   * @param array $params     Associative array of parameters
   *
   * @return object|null      The new group BAO (if created)
   * @access public
   * @static
   */
  public static function createGroup(&$params) {
    if (CRM_Utils_Array::value('saved_search_id', $params)) {
      $savedSearch = new CRM_Contact_BAO_SavedSearch();
      $savedSearch->form_values = CRM_Utils_Array::value('formValues', $params);
      $savedSearch->is_active = 1;
      $savedSearch->id = $params['saved_search_id'];
      $savedSearch->save();
    }

    return self::create($params);
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $isActive  value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static function setIsActive($id, $isActive) {
    return CRM_Core_DAO::setFieldValue('CRM_Contact_DAO_Group', $id, 'is_active', $isActive);
  }

  /**
   * build the condition to retrieve groups.
   *
   * @param string  $groupType     type of group(Access/Mailing)
   * @param boolen  $excludeHidden exclude hidden groups.
   *
   * @return string $condition
   * @static
   */
  static function groupTypeCondition($groupType = NULL, $excludeHidden = TRUE) {
    $value = NULL;
    if ($groupType == 'Mailing') {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . '2' . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    elseif ($groupType == 'Access') {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . '1' . CRM_Core_DAO::VALUE_SEPARATOR;
    }
    elseif (is_numeric($groupType)) {
      $value = CRM_Core_DAO::VALUE_SEPARATOR . "$groupType" . CRM_Core_DAO::VALUE_SEPARATOR;
    }

    $condition = NULL;
    if ($excludeHidden) {
      $condition = "is_hidden = 0";
    }

    if ($value) {
      if ($condition) {
        $condition .= " AND group_type LIKE '%$value%'";
      }
      else {
        $condition = "group_type LIKE '%$value%'";
      }
    }

    return $condition;
  }

  public function __toString() {
    return $this->title;
  }

  /**
   * This function create the hidden smart group when user perform
   * contact seach and want to send mailing to search contacts.
   *
   * @param  array $params ( reference ) an assoc array of name/value pairs
   *
   * @return array ( smartGroupId, ssId ) smart group id and saved search id
   * @access public
   * @static
   */
  static function createHiddenSmartGroup($params) {
    $ssId = CRM_Utils_Array::value('saved_search_id', $params);

    //add mapping record only for search builder saved search
    $mappingId = NULL;
    if ($params['search_context'] == 'builder') {
      //save the mapping for search builder

      if (!$ssId) {
        //save record in mapping table
        $temp = [];
        $mappingParams = ['mapping_type' => 'Search Builder'];
        $mapping = CRM_Core_BAO_Mapping::add($mappingParams, $temp);
        $mappingId = $mapping->id;
      }
      else {
        //get the mapping id from saved search

        $savedSearch = new CRM_Contact_BAO_SavedSearch();
        $savedSearch->id = $ssId;
        $savedSearch->find(TRUE);
        $mappingId = $savedSearch->mapping_id;
      }

      //save mapping fields
      CRM_Core_BAO_Mapping::saveMappingFields($params['form_values'], $mappingId);
    }

    //create/update saved search record.
    $savedSearch = new CRM_Contact_BAO_SavedSearch();
    $savedSearch->id = $ssId;
    $savedSearch->form_values = serialize($params['form_values']);
    $savedSearch->mapping_id = $mappingId;
    $savedSearch->search_custom_id = CRM_Utils_Array::value('search_custom_id', $params);
    $savedSearch->save();

    $ssId = $savedSearch->id;
    if (!$ssId) {
      return NULL;
    }

    $smartGroupId = NULL;
    if (CRM_Utils_Array::value('saved_search_id', $params)) {
      $smartGroupId = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Group', $ssId, 'id', 'saved_search_id');
    }
    else {
      //create group only when new saved search.
      $groupParams = ['title' => "Hidden Smart Group {$ssId}",
        'is_active' => CRM_Utils_Array::value('is_active', $params, 1),
        'is_hidden' => CRM_Utils_Array::value('is_hidden', $params, 1),
        'group_type' => CRM_Utils_Array::value('group_type', $params),
        'visibility' => CRM_Utils_Array::value('visibility', $params),
        'saved_search_id' => $ssId,
      ];


      $smartGroup = self::create($groupParams);
      $smartGroupId = $smartGroup->id;
    }

    return [$smartGroupId, $ssId];
  }

  static function publicDefaultGroups(&$defaults){
    $query = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_group WHERE visibility LIKE 'Public Pages' && is_hidden = 0");
    while ($query->fetch()) {
      $defaults['group['.$query->id.']'] = 1;
    }
    return $defaults;
  }
}

