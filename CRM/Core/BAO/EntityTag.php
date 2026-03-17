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
 * This class contains functions for managing Tag(tag) for a contact
 *
 * @copyright CiviCRM LLC (c) 2004-2010
 *
 */

class CRM_Core_BAO_EntityTag extends CRM_Core_DAO_EntityTag {

  /**
   * Get an array of tag IDs associated with a specific entity.
   *
   * @param int $entityID ID of the entity (usually contact ID)
   * @param string $entityTable name of the entity table (defaults to 'civicrm_contact')
   *
   * @return array array of tag IDs
   */
  public static function &getTag($entityID, $entityTable = 'civicrm_contact') {
    $tags = [];

    $entityTag = new CRM_Core_BAO_EntityTag();
    $entityTag->entity_id = $entityID;
    $entityTag->entity_table = $entityTable;
    $entityTag->find();

    while ($entityTag->fetch()) {
      $tags[$entityTag->tag_id] = $entityTag->tag_id;
    }
    return $tags;
  }

  /**
   * Add a tag to an entity.
   *
   * @param array &$params associative array of tag data (entity_id, tag_id, entity_table)
   *
   * @return CRM_Core_BAO_EntityTag|null the created entity tag object
   */
  public static function add(&$params) {

    $dataExists = self::dataExists($params);
    if (!$dataExists) {
      return NULL;
    }

    $entityTag = new CRM_Core_BAO_EntityTag();
    $entityTag->copyValues($params);

    // dont save the object if it already exists, CRM-1276
    if (!$entityTag->find(TRUE)) {
      $entityTag->save();
    }

    return $entityTag;
  }

  /**
   * Check if there is enough data to create an entity tag record.
   *
   * @param array &$params associative array of tag data
   *
   * @return bool TRUE if data exists (tag_id is non-zero)
   */
  public static function dataExists(&$params) {
    return ($params['tag_id'] == 0) ? FALSE : TRUE;
  }

  /**
   * Delete an entity tag record.
   *
   * @param array &$params associative array containing identifying fields
   *
   * @return void
   */
  public static function del(&$params) {
    $entityTag = new CRM_Core_BAO_EntityTag();
    $entityTag->copyValues($params);
    $entityTag->delete();
    //return $entityTag;
  }

  /**
   * Add multiple entities to a specific tag.
   *
   * @param array &$entityIds array of entity IDs to be added
   * @param int $tagId ID of the tag
   * @param string $entityTable name of the entity table (defaults to 'civicrm_contact')
   *
   * @return array [total_count, added_count, already_present_count]
   */
  public static function addEntitiesToTag(&$entityIds, $tagId, $entityTable = 'civicrm_contact') {
    $numEntitiesAdded = 0;
    $numEntitiesNotAdded = 0;
    foreach ($entityIds as $entityId) {
      $tag = new CRM_Core_DAO_EntityTag();

      $tag->entity_id = $entityId;
      $tag->tag_id = $tagId;
      $tag->entity_table = $entityTable;
      if (!$tag->find()) {
        $tag->save();
        $numEntitiesAdded++;
      }
      else {
        $numEntitiesNotAdded++;
      }
    }

    //invoke post hook on entityTag

    $object = [$entityIds, $entityTable];
    CRM_Utils_Hook::post('create', 'EntityTag', $tagId, $object);

    // reset the group contact cache for all groups
    // if tags are being used in a smart group

    CRM_Contact_BAO_GroupContactCache::remove();

    return [count($entityIds), $numEntitiesAdded, $numEntitiesNotAdded];
  }

  /**
   * Remove multiple entities from a specific tag.
   *
   * @param array &$entityIds array of entity IDs to be removed
   * @param int $tagId ID of the tag
   * @param string $entityTable name of the entity table (defaults to 'civicrm_contact')
   *
   * @return array [total_count, removed_count, not_present_count]
   */
  public static function removeEntitiesFromTag(&$entityIds, $tagId, $entityTable = 'civicrm_contact') {
    $numEntitiesRemoved = 0;
    $numEntitiesNotRemoved = 0;
    foreach ($entityIds as $entityId) {
      $tag = new CRM_Core_DAO_EntityTag();

      $tag->entity_id = $entityId;
      $tag->tag_id = $tagId;
      $tag->entity_table = $entityTable;
      if ($tag->find()) {
        $tag->delete();
        $numEntitiesRemoved++;
      }
      else {
        $numEntitiesNotRemoved++;
      }
    }

    //invoke post hook on entityTag

    $object = [$entityIds, $entityTable];
    CRM_Utils_Hook::post('delete', 'EntityTag', $tagId, $object);

    // reset the group contact cache for all groups
    // if tags are being used in a smart group

    CRM_Contact_BAO_GroupContactCache::remove();

    return [count($entityIds), $numEntitiesRemoved, $numEntitiesNotRemoved];
  }

  /**
   * Create or synchronize entity tags based on provided parameters.
   *
   * Compares provided tags with existing tags and adds/removes records as necessary.
   *
   * @param array &$params associative array where keys are tag IDs
   * @param string $entityTable name of the entity table
   * @param int $entityID ID of the entity
   *
   * @return void
   */
  public static function create(&$params, $entityTable, $entityID) {
    // get categories for the contact id
    $entityTag = &CRM_Core_BAO_EntityTag::getTag($entityID, $entityTable);

    // get the list of all the categories

    $allTag = CRM_Core_BAO_Tag::getTags($entityTable);

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($params)) {
      $params = [];
    }

    // this fix is done to prevent warning generated by array_key_exits incase of empty array is given as input
    if (!is_array($entityTag)) {
      $entityTag = [];
    }

    // check which values has to be inserted/deleted for contact
    foreach ($allTag as $key => $varValue) {
      $tagParams['entity_table'] = $entityTable;
      $tagParams['entity_id'] = $entityID;
      $tagParams['tag_id'] = $key;

      if (CRM_Utils_Array::arrayKeyExists($key, $params) && !CRM_Utils_Array::arrayKeyExists($key, $entityTag)) {
        // insert a new record
        CRM_Core_BAO_EntityTag::add($tagParams);
      }
      elseif (!CRM_Utils_Array::arrayKeyExists($key, $params) && CRM_Utils_Array::arrayKeyExists($key, $entityTag)) {
        // delete a record for existing contact
        CRM_Core_BAO_EntityTag::del($tagParams);
      }
    }
  }

  /**
   * Retrieve all entity IDs assigned to a specific tag.
   *
   * @param CRM_Core_DAO_Tag $tag the tag object
   *
   * @return array array of entity IDs (note: property name in code is contact_id)
   */
  public function getEntitiesByTag($tag) {
    $contactIds = [];
    $entityTagDAO = new CRM_Core_DAO_EntityTag();
    $entityTagDAO->tag_id = $tag->id;
    $entityTagDAO->find();
    while ($entityTagDAO->fetch()) {
      $contactIds[] = $entityTagDAO->contact_id;
    }
    return $contactIds;
  }

  /**
   * Get tags associated with a specific contact.
   *
   * @param int $contactID contact ID
   * @param bool $count TRUE to return only the count, FALSE to return names
   *
   * @return array|int tag names array (id => name) or count of tags
   */
  public static function getContactTags($contactID, $count = FALSE) {
    $contactTags = [];
    if (!$count) {
      $select = "SELECT name, ct.id ";
    }
    else {
      $select = "SELECT count(*) as cnt";
    }

    $query = "{$select} 
        FROM civicrm_tag ct 
        INNER JOIN civicrm_entity_tag et ON ( ct.id = et.tag_id AND
            et.entity_id    = {$contactID} AND
            et.entity_table = 'civicrm_contact' AND
            ct.is_tagset = 0 ) GROUP BY et.entity_id, et.tag_id";

    $dao = CRM_Core_DAO::executeQuery($query);

    if ($count) {
      $dao->fetch();
      return $dao->N;
    }

    while ($dao->fetch()) {
      $contactTags[$dao->id] = $dao->name;
    }

    return $contactTags;
  }

  /**
   * Get child tags associated with a specific entity and parent tag.
   *
   * @param int $parentId ID of the parent tag
   * @param int $entityId ID of the entity
   * @param string $entityTable name of the entity table
   *
   * @return array array of child tag info
   */
  public static function getChildEntityTags($parentId, $entityId, $entityTable = 'civicrm_contact') {
    $entityTags = [];
    $query = "SELECT ct.id as tag_id, name FROM civicrm_tag ct
                    INNER JOIN civicrm_entity_tag et ON ( et.entity_id = {$entityId} AND
                     et.entity_table = '{$entityTable}' AND  et.tag_id = ct.id)
                  WHERE ct.parent_id = {$parentId}";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $entityTags[$dao->tag_id] = ['id' => $dao->tag_id,
        'name' => $dao->name,
      ];
    }

    return $entityTags;
  }
}
