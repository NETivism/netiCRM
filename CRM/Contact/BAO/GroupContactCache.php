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

class CRM_Contact_BAO_GroupContactCache extends CRM_Contact_DAO_GroupContactCache {

  /**
   * Minimal cache time in seconds
   */
  const SMARTGROUP_CACHE_TIMEOUT_MINIMAL = 30;

  static $_alreadyLoaded = array();

  /**
   * Check to see if we have cache entries for this group
   * if not, regenerate, else return
   *
   * @param int $groupID groupID of group that we are checking against
   *
   * @return boolean true if we did not regenerate, false if we did
   */
  static function check($groupID) {
    if (empty($groupID)) {
      return TRUE;
    }

    if (!is_array($groupID)) {
      $groupID = array($groupID);
    }
    // note escapeString is a must here and we can't send the imploded value as second arguement to
    // the executeQuery(), since that would put single quote around the string and such a string
    // of comma separated integers would not work.
    $groupID = CRM_Core_DAO::escapeString(CRM_Utils_Array::implode(', ', $groupID));

    $config = CRM_Core_Config::singleton();
    $smartGroupCacheTimeout = self::smartGroupCacheTimeout();

    //make sure to give original timezone settings again.
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $now = date('YmdHis');
    date_default_timezone_set($originalTimezone);
    $query = "
SELECT     g.id
FROM       civicrm_group g
WHERE      g.id IN ( {$groupID} ) AND g.saved_search_id IS NOT NULL AND 
          (g.cache_date IS NULL OR (TIMESTAMPDIFF(MINUTE, g.cache_date, NOW()) >= $smartGroupCacheTimeout))
";

    $dao = CRM_Core_DAO::executeQuery($query);
    $groupIDs = array();
    while ($dao->fetch()) {
      $groupIDs[] = $dao->id;
    }

    if (empty($groupIDs)) {
      return TRUE;
    }
    else {
      self::add($groupIDs);
      return FALSE;
    }
  }

  static function checkAll($intersectGroups = array()) {
    $group = new CRM_Contact_DAO_Group();
    $group->is_active = 1;
    $group->find();
    while ($group->fetch()) {
      if ($group->saved_search_id) {
        $smartGroups[] = $group->id;
      }
    }
    if (!empty($intersectGroups)) {
      $smartGroups = array_intersect($smartGroups, $intersectGroups);
    }
    CRM_Contact_BAO_GroupContactCache::check($smartGroups);
  }

  static function add($groupID) {
    // first delete the current cache
    self::remove($groupID);
    if (!is_array($groupID)) {
      $groupID = array($groupID);
    }

    $returnProperties = array('contact_id');
    foreach ($groupID as $gid) {
      $params = array(array('group', 'IN', array($gid => 1), 0, 0));
      // the below call update the cache table as a byproduct of the query
      CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, 0, FALSE);
    }
  }

  static function store(&$groupID, &$values) {
    $processed = FALSE;

    // sort the values so we put group IDs in front and hence optimize
    // mysql storage (or so we think) CRM-9493
    sort($values);

    // to avoid long strings, lets do BULK_INSERT_COUNT values at a time
    while (!empty($values)) {
      $processed = TRUE;
      $input = array_splice($values, 0, CRM_Core_DAO::BULK_INSERT_COUNT);
      $str = CRM_Utils_Array::implode(',', $input);
      $sql = "INSERT IGNORE INTO civicrm_group_contact_cache (group_id,contact_id) VALUES $str;";
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  static function remove($groupID = NULL, $onceOnly = TRUE) {
    static $invoked = FALSE;

    // typically this needs to happy only once per instance
    // this is especially true in import, where we dont need
    // to do this all the time
    // this optimization is done only when no groupID is passed
    // i.e. cache is reset for all groups
    if ($onceOnly && $invoked && $groupID == NULL) {
      return;
    }

    if ($groupID == NULL) {
      $invoked = TRUE;
    }
    else if (is_array($groupID)) {
      foreach ($groupID as $gid) {
        unset(self::$_alreadyLoaded[$gid]);
      }
    }
    else if ($groupID && CRM_Utils_Array::arrayKeyExists($groupID, self::$_alreadyLoaded)) {
      unset(self::$_alreadyLoaded[$groupID]);
    }

    //when there are difference in timezones for mysql and php.
    //cache_date set null not behaving properly, CRM-6855

    //make sure to give original timezone settings again.
    $originalTimezone = date_default_timezone_get();
    date_default_timezone_set('UTC');
    $now = date('YmdHis');
    date_default_timezone_set($originalTimezone);

    if (!isset($groupID)) {
      $smartGroupCacheTimeout = self::smartGroupCacheTimeout();

      if ($smartGroupCacheTimeout == 0) {
        $query = "
TRUNCATE civicrm_group_contact_cache
";
        $update = "
UPDATE civicrm_group g
SET    cache_date = null
";
        $params = array();
      }
      else {
        // #30818, we have serious deadlock issue
        // purge cache is not a big deal 
        // so we get ids first then purge in next execution
        $dao = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_group WHERE TIMESTAMPDIFF(MINUTE, cache_date, $now) >= $smartGroupCacheTimeout");
        $ids = array();
        while($dao->fetch()) {
          $ids[] = $dao->id;
        }
        $query = "
  DELETE     g
  FROM       civicrm_group_contact_cache g
  WHERE      g.group_id IN ( %1 )
  ";
        $update = "
  UPDATE civicrm_group g
  SET    cache_date = null
  WHERE  id IN ( %1 )
  ";
        $groupIDs = CRM_Utils_Array::implode(', ', $ids);
        $params = array(1 => array($groupIDs, 'String'));
      }
    }
    elseif (is_array($groupID)) {
      $query = "
DELETE     g
FROM       civicrm_group_contact_cache g
WHERE      g.group_id IN ( %1 )
";
      $update = "
UPDATE civicrm_group g
SET    cache_date = null
WHERE  id IN ( %1 )
";
      $groupIDs = CRM_Utils_Array::implode(', ', $groupID);
      $params = array(1 => array($groupIDs, 'String'));
    }
    else {
      $query = "
DELETE     g
FROM       civicrm_group_contact_cache g
WHERE      g.group_id = %1
";
      $update = "
UPDATE civicrm_group g
SET    cache_date = null
WHERE  id = %1
";
      $params = array(1 => array($groupID, 'Integer'));
    }

    CRM_Core_DAO::executeQuery($query, $params);

    // also update the cache_date for these groups
    CRM_Core_DAO::executeQuery($update, $params);
  }

  /**
   * load the smart group cache for a saved search
   */
  static function load(&$group, $fresh = FALSE) {
    $groupID = $group->id;
    $savedSearchID = $group->saved_search_id;
    if (CRM_Utils_Array::arrayKeyExists($groupID, self::$_alreadyLoaded) && !$fresh) {
      return;
    }
    self::$_alreadyLoaded[$groupID] = 1;
    $sql = NULL;
    $idName = 'id';
    $customClass = NULL;
    if ($savedSearchID) {
      $ssParams = CRM_Contact_BAO_SavedSearch::getSearchParams($savedSearchID);

      // rectify params to what proximity search expects if there is a value for prox_distance
      // CRM-7021
      /*
        if (!empty($ssParams)) {
          CRM_Contact_BAO_ProximityQuery::fixInputParams($ssParams);
        }
*/


      $returnProperties = array();
      if (CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_SavedSearch', $savedSearchID, 'mapping_id')) {
        $fv = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);
        $returnProperties = CRM_Core_BAO_Mapping::returnProperties($fv);
      }

      $groupID = CRM_Utils_Type::escape($groupID, 'Integer');
      if (isset($ssParams['customSearchID'])) {
        $customClass = CRM_Contact_BAO_SearchCustom::customClass($ssParams['customSearchID'], $savedSearchID);
        $searchSQL = $customClass->contactIDs();
        // refs #30100, create temp table to prevent sql error
        $tempTable = CRM_Core_DAO::createTempTableName('civicrm_group_contact', TRUE);
        CRM_Core_DAO::executeQuery("CREATE TEMPORARY TABLE $tempTable ( contact_id int primary key) ENGINE=HEAP");
        CRM_Core_DAO::executeQuery("REPLACE INTO $tempTable (contact_id)($searchSQL)");
        CRM_Core_DAO::executeQuery("DELETE FROM $tempTable WHERE contact_id IN (SELECT contact_id FROM civicrm_group_contact WHERE civicrm_group_contact.status = 'Removed' AND civicrm_group_contact.group_id = $groupID)");
        $searchSQL = "SELECT contact_id FROM $tempTable";
        $idName = 'contact_id';
      }
      else {
        $additionalWhereClause = <<<EOT
          NOT EXISTS (
            SELECT 1 FROM civicrm_group_contact cgc_removed
            WHERE cgc_removed.contact_id = contact_a.id AND cgc_removed.group_id = {$groupID} AND cgc_removed.status = 'Removed'
          )
        EOT;
        $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($savedSearchID);

        $query = new CRM_Contact_BAO_Query(
          $ssParams, $returnProperties, NULL,
          FALSE, FALSE, 1,
          TRUE, TRUE,
          FALSE,
          CRM_Utils_Array::value('display_relationship_type', $formValues),
          CRM_Utils_Array::value('operator', $formValues, 'AND')
        );
        $query->_useDistinct = FALSE;
        $query->_useGroupBy = TRUE;
        $searchSQL = $query->searchQuery(
          0, 0, NULL,
          FALSE, FALSE,
          FALSE, TRUE,
          TRUE,
          $additionalWhereClause, NULL, NULL,
          TRUE
        );
      }
      $sql = $searchSQL;
    }

    if ($sql) {
      $sql = preg_replace("/^\s*SELECT/", "SELECT $groupID as group_id, ", $sql);
      $sql = preg_replace("/distinct/i", "", $sql);
    }

    // lets also store the records that are explicitly added to the group
    // this allows us to skip the group contact LEFT JOIN
    $sqlB = "
  SELECT $groupID as group_id, contact_id as $idName
  FROM   civicrm_group_contact
  WHERE  civicrm_group_contact.status = 'Added'
    AND  civicrm_group_contact.group_id = $groupID ";

    $groupIDs = array($groupID);
    self::remove($groupIDs);

    foreach (array($sql, $sqlB) as $selectSql) {
      if (!$selectSql) {
        continue;
      }
      $insertSql = "INSERT IGNORE INTO civicrm_group_contact_cache (group_id,contact_id) ($selectSql);";
      // FIXME
      $processed = TRUE;
      $result = CRM_Core_DAO::executeQuery($insertSql);
    }

    if ($group->children) {

      //Store a list of contacts who are removed from the parent group
      $sql = "
  SELECT contact_id
  FROM civicrm_group_contact
  WHERE  civicrm_group_contact.status = 'Removed'
  AND  civicrm_group_contact.group_id = $groupID ";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $removed_contacts = array();
      while ($dao->fetch()) {
        $removed_contacts[] = $dao->contact_id;
      }

      $childrenIDs = explode(',', $group->children);
      foreach ($childrenIDs as $childID) {
        $contactIDs = CRM_Contact_BAO_Group::getMember($childID, FALSE);
        //Unset each contact that is removed from the parent group
        foreach ($removed_contacts as $removed_contact) {
          unset($contactIDs[$removed_contact]);
        }
        $values = array();
        foreach ($contactIDs as $contactID => $dontCare) {
          $values[] = "({$groupID},{$contactID})";
        }

        self::store($groupIDs, $values);
        self::$_alreadyLoaded[$childID] = 1;
        $processed = TRUE;
      }
    }
    if ($processed) {
      self::updateCacheTime($groupIDs, $processed);
    }
  }

  /**
   * Change the cache_date
   *
   * @param $groupIDs array(int)
   * @param $processed bool, whether the cache data was recently modified
   */
  static function updateCacheTime($groupIDs, $processed) {
    // only update cache entry if we had any values
    if ($processed) {
      // also update the group with cache date information
      //make sure to give original timezone settings again.
      $originalTimezone = date_default_timezone_get();
      date_default_timezone_set('UTC');
      $now = date('YmdHis');
      date_default_timezone_set($originalTimezone);
    }
    else {
      $now = 'null';
    }

    $groupIDs = CRM_Utils_Array::implode(',', $groupIDs);
    $sql = "
  UPDATE civicrm_group
  SET    cache_date = $now
  WHERE  id IN ( $groupIDs )
  ";
    CRM_Core_DAO::executeQuery($sql,
      CRM_Core_DAO::$_nullArray
    );
  }

/**
   * Check to see if we have cache entries for this group
   * if not, regenerate, else return
   *
   * @param int/array $groupID groupID of group that we are checking against
   *                           if empty, all groups are checked
   * @param int       $limit   limits the number of groups we evaluate
   *
   * @return boolean true if we did not regenerate, false if we did
   */
  static function loadAll($groupIDs = null, $limit = 0) {
    // ensure that all the smart groups are loaded
    // this function is expensive and should be sparingly used if groupIDs is empty

    if (empty($groupIDs)) {
      $groupIDClause = null;
      $groupIDs = array( );
    }
    else {
      if (!is_array($groupIDs)) {
        $groupIDs = array($groupIDs);
      }

      // note escapeString is a must here and we can't send the imploded value as second arguement to
      // the executeQuery(), since that would put single quote around the string and such a string
      // of comma separated integers would not work.
      $groupIDString = CRM_Core_DAO::escapeString(CRM_Utils_Array::implode(', ', $groupIDs));

      $groupIDClause = "AND (g.id IN ( {$groupIDString} ))";
    }

    $smartGroupCacheTimeout = self::smartGroupCacheTimeout();

    //make sure to give original timezone settings again.
    $now = CRM_Utils_Date::getUTCTime();

    $limitClause = $orderClause = NULL;
    if ($limit > 0) {
      $limitClause = " LIMIT 0, $limit";
      $orderClause = " ORDER BY g.cache_date";
    }
    // We ignore hidden groups and disabled groups
    $query = "
SELECT  g.id
FROM    civicrm_group g
WHERE   ( g.saved_search_id IS NOT NULL OR g.children IS NOT NULL )
AND     ( g.is_hidden = 0 OR g.is_hidden IS NULL )
AND     g.is_active = 1
AND     ( g.cache_date IS NULL OR
          ( TIMESTAMPDIFF(MINUTE, g.cache_date, $now) >= $smartGroupCacheTimeout )
        )
        $groupIDClause
        $orderClause
        $limitClause
";

    $dao = CRM_Core_DAO::executeQuery($query);
    $processGroupIDs = array();
    $refreshGroupIDs = $groupIDs;
    while ($dao->fetch()) {
      $processGroupIDs[] = $dao->id;

      // remove this id from refreshGroupIDs
      foreach ($refreshGroupIDs as $idx => $gid) {
        if ($gid == $dao->id) {
          unset($refreshGroupIDs[$idx]);
          break;
        }
      }
    }

    if (empty($processGroupIDs)) {
      return TRUE;
    }
    else {
      self::add($processGroupIDs);
      return FALSE;
    }
  }

  static function smartGroupCacheTimeout() {
    $config = CRM_Core_Config::singleton();

    if (
      isset($config->smartGroupCacheTimeout) &&
      is_numeric($config->smartGroupCacheTimeout) &&
      $config->smartGroupCacheTimeout > 0) {
      return $config->smartGroupCacheTimeout;
    }

    // lets have a min cache time of 5 mins if not set
    return 15;
  }

  /**
   * Get all the smart groups that this contact belongs to
   * Note that this could potentially be a super slow function since
   * it ensure that all contact groups are loaded in the cache
   *
   * @param int     $contactID
   * @param boolean $showHidden - hidden groups are shown only if this flag is set
   *
   * @return array an array of groups that this contact belongs to
   */
  static function contactGroup($contactID, $showHidden = FALSE) {
    if (empty($contactID)) {
      return;
    }

    if (is_array($contactID)) {
      $contactIDs = $contactID;
    }
    else {
      $contactIDs = array($contactID);
    }

    // refs #31384, disable this resource hug function
    //self::loadAll();

    $hiddenClause = '';
    if (!$showHidden) {
      $hiddenClause = ' AND (g.is_hidden = 0 OR g.is_hidden IS NULL) ';
    }

    $contactIDString = CRM_Core_DAO::escapeString(CRM_Utils_Array::implode(', ', $contactIDs));
    $sql = "
SELECT     gc.group_id, gc.contact_id, g.title, g.children, g.description
FROM       civicrm_group_contact_cache gc
INNER JOIN civicrm_group g ON g.id = gc.group_id
WHERE      g.saved_search_id IS NOT NULL AND
           gc.contact_id IN ($contactIDString)
           $hiddenClause
ORDER BY   gc.contact_id, g.children
";

    $dao = CRM_Core_DAO::executeQuery($sql);
    $contactGroup = array();
    $prevContactID = null;
    while ($dao->fetch()) {
      if (
        $prevContactID &&
        $prevContactID != $dao->contact_id
      ) {
        $contactGroup[$prevContactID]['groupTitle'] = CRM_Utils_Array::implode(', ', $contactGroup[$prevContactID]['groupTitle']);
      }
      $prevContactID = $dao->contact_id;
      if (!CRM_Utils_Array::arrayKeyExists($dao->contact_id, $contactGroup)) {
        $contactGroup[$dao->contact_id] =
          array( 'group' => array(), 'groupTitle' => array());
      }

      $contactGroup[$dao->contact_id]['group'][] =
        array(
          'id' => $dao->group_id,
          'title' => $dao->title,
          'description' => $dao->description,
          'children' => $dao->children
        );
      $contactGroup[$dao->contact_id]['groupTitle'][] = $dao->title;
    }

    if ($prevContactID) {
      $contactGroup[$prevContactID]['groupTitle'] = CRM_Utils_Array::implode(', ', $contactGroup[$prevContactID]['groupTitle']);
    }

    if (is_numeric($contactID)) {
      return $contactGroup[$contactID];
    }
    else {
      return $contactGroup;
    }
  }

}

