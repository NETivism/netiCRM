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

class CRM_Core_BAO_Tag extends CRM_Core_DAO_Tag {

  public $tree;
  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve a tag record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_BAO_Tag|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $tag = new CRM_Core_DAO_Tag();
    $tag->copyValues($params);
    if ($tag->find(TRUE)) {
      CRM_Core_DAO::storeValues($tag, $defaults);
      return $tag;
    }
    return NULL;
  }

  /**
   * Get the tag tree structure.
   *
   * @param string|null $usedFor entity type (e.g., 'civicrm_contact')
   * @param bool $excludeHidden whether to exclude tag sets
   *
   * @return array nested associative array of tags
   */
  public function getTree($usedFor = NULL, $excludeHidden = FALSE) {
    if (!isset($this->tree)) {
      $this->buildTree($usedFor, $excludeHidden);
    }
    return $this->tree;
  }

  /**
   * Build the tag tree structure.
   *
   * @param string|null $usedFor entity type
   * @param bool $excludeHidden whether to exclude tag sets
   *
   * @return void
   */
  public function buildTree($usedFor = NULL, $excludeHidden = FALSE) {
    $sql = "SELECT civicrm_tag.id, civicrm_tag.parent_id,civicrm_tag.name FROM civicrm_tag ";

    $whereClause = [];
    if ($usedFor) {
      $whereClause[] = "used_for like '%{$usedFor}%'";
    }
    if ($excludeHidden) {
      $whereClause[] = "is_tagset = 0";
    }

    if (!empty($whereClause)) {
      $sql .= " WHERE " . CRM_Utils_Array::implode(' AND ', $whereClause);
    }

    $sql .= " ORDER BY parent_id,name";

    $dao = &CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);

    $orphan = [];
    while ($dao->fetch()) {
      if (!$dao->parent_id) {
        $this->tree[$dao->id]['name'] = $dao->name;
      }
      else {
        if (CRM_Utils_Array::arrayKeyExists($dao->parent_id, $this->tree)) {
          $parent = &$this->tree[$dao->parent_id];
          if (!isset($this->tree[$dao->parent_id]['children'])) {
            $this->tree[$dao->parent_id]['children'] = [];
          }
        }
        else {
          //3rd level tag
          if (!CRM_Utils_Array::arrayKeyExists($dao->parent_id, $orphan)) {
            $orphan[$dao->parent_id] = ['children' => []];
          }
          $parent = &$orphan[$dao->parent_id];
        }
        $parent['children'][$dao->id] = ['name' => $dao->name];
      }
    }
    if (count($orphan)) {
      //hang the 3rd level lists at the right place
      foreach ($this->tree as &$level1) {
        if (!isset($level1['children'])) {
          continue;
        }

        foreach ($level1['children'] as $key => &$level2) {
          if (CRM_Utils_Array::arrayKeyExists($key, $orphan)) {
            $level2['children'] = $orphan[$key]['children'];
          }
        }
      }
    }
  }

  /**
   * Get tags used for specific entity types.
   *
   * @param array|string $usedFor entity table name(s)
   * @param bool $buildSelect whether to return a simple (id => name) array
   * @param bool $all whether to include tag sets
   * @param int|null $parentId filter by parent tag ID
   *
   * @return array array of tag info
   */
  public static function getTagsUsedFor(
    $usedFor = ['civicrm_contact'],
    $buildSelect = TRUE,
    $all = FALSE,
    $parentId = NULL
  ) {
    $tags = [];

    if (empty($usedFor)) {
      return $tags;
    }
    if (!is_array($usedFor)) {
      $usedFor = [$usedFor];
    }

    if ($parentId === NULL) {
      $parentClause = " parent_id IS NULL AND ";
    }
    else {
      $parentClause = " parent_id = {$parentId} AND ";
    }

    foreach ($usedFor as $entityTable) {
      $tag = new CRM_Core_DAO_Tag();
      $tag->fields();
      $tag->orderBy('parent_id');
      if ($buildSelect) {
        $tag->whereAdd("is_tagset = 0 AND {$parentClause} used_for LIKE '%{$entityTable}%'");
      }
      else {
        $tag->whereAdd("used_for LIKE '%{$entityTable}%'");
      }
      if (!$all) {
        $tag->is_tagset = 0;
      }
      $tag->find();

      while ($tag->fetch()) {
        if ($buildSelect) {
          $tags[$tag->id] = $tag->name;
        }
        else {
          $tags[$tag->id]['name'] = $tag->name;
          $tags[$tag->id]['parent_id'] = $tag->parent_id;
          $tags[$tag->id]['is_tagset'] = $tag->is_tagset;
          $tags[$tag->id]['used_for'] = $tag->used_for;
        }
      }
      $tag->free();
    }

    return $tags;
  }

  /**
   * Get tags with hierarchical labels (using non-breaking spaces for indentation).
   *
   * @param string $usedFor entity table name
   * @param array &$tags associative array to store the result
   * @param int|null $parentId starting parent tag ID
   * @param string $separator indentation string
   * @param bool $flatlist whether to return a flat list
   *
   * @return array associative array of (id => formatted_label)
   */
  public static function getTags(
    $usedFor = 'civicrm_contact',
    &$tags = [],
    $parentId = NULL,
    $separator = '&nbsp;&nbsp;',
    $flatlist = TRUE
  ) {
    // We need to build a list of tags ordered by hierarchy and sorted by
    // name. The heirarchy will be communicated by an accumulation of
    // '&nbsp;&nbsp;' in front of the name to give it a visual offset.
    // Instead of recursively making mysql queries, we'll make one big
    // query and build the heirarchy with the algorithm below.
    $query = "SELECT id, name, parent_id, is_tagset
                  FROM civicrm_tag 
                  WHERE used_for LIKE '%{$usedFor}%' ORDER BY name";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);

    // Sort the tags into the correct storage by the parent_id/is_tagset
    // filter the filter was in place previously, we're just reusing it.
    // $roots represents the current leaf nodes that need to be checked for
    // children. $rows represents the unplaced nodes, not all of much
    // are necessarily placed.
    $rows = $roots = [];
    while ($dao->fetch()) {
      if (!$dao->parent_id && $dao->is_tagset == 0) {
        $roots[] = ['id' => $dao->id, 'prefix' => '', 'name' => $dao->name];
      }
      else {
        $rows[] = ['id' => $dao->id, 'prefix' => '', 'name' => $dao->name, 'parent_id' => $dao->parent_id];
      }
    }

    // While we have nodes left to build, shift the first (alphabetically)
    // node of the list, place it in our tags list and loop through the
    // list of unplaced nodes to find its children. We make a copy to
    // iterate through because we must modify the unplaced nodes list
    // during the loop.
    while (count($roots)) {
      $new_roots = [];
      $current_rows = $rows;
      $root = array_shift($roots);
      $tags[$root['id']] = [$root['prefix'], $root['name']];

      // As you find the children, append them to the end of the new set
      // of roots (maintain alphabetical ordering). Also remove the node
      // from the set of unplaced nodes.
      if (is_array($current_rows)) {
        foreach ($current_rows as $key => $row) {
          if ($row['parent_id'] == $root['id']) {
            $new_roots[] = ['id' => $row['id'], 'prefix' => $tags[$root['id']][0] . '&nbsp;&nbsp;', 'name' => $row['name']];
            unset($rows[$key]);
          }
        }
      }

      //As a group, insert the new roots into the beginning of the roots
      //list. This maintains the hierarchical ordering of the tags.
      $roots = array_merge($new_roots, $roots);
    }

    // Prefix each name with the calcuated spacing to give the visual
    // appearance of ordering when transformed into HTML in the form layer.
    foreach ($tags as &$tag) {
      $tag = $tag[0] . $tag[1];
    }

    return $tags;
  }

  /**
   * Delete a tag and all its entity associations.
   *
   * @param int $id tag ID
   *
   * @return bool TRUE on success
   */
  public static function del($id) {
    // delete all crm_entity_tag records with the selected tag id

    $entityTag = new CRM_Core_DAO_EntityTag();
    $entityTag->tag_id = $id;
    if ($entityTag->find()) {
      while ($entityTag->fetch()) {
        $entityTag->delete();
      }
    }

    // delete from tag table
    $tag = new CRM_Core_DAO_Tag();
    $tag->id = $id;

    CRM_Utils_Hook::pre('delete', 'Tag', $id, $tag);

    if ($tag->delete()) {
      CRM_Utils_Hook::post('delete', 'Tag', $id, $tag);
      CRM_Core_Session::setStatus(ts('Selected Tag has been Deleted Successfuly.'));
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add or update a tag record.
   *
   * @param array &$params associative array of tag data
   * @param array &$ids associative array containing 'tag' ID if updating
   *
   * @return CRM_Core_BAO_Tag|null created/updated tag object
   */
  public static function add(&$params, &$ids) {
    if (!self::dataExists($params)) {
      return NULL;
    }

    $tag = new CRM_Core_DAO_Tag();

    // if parent id is set then inherit used for and is hidden properties
    if (CRM_Utils_Array::value('parent_id', $params)) {
      // get parent details
      $params['used_for'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Tag', $params['parent_id'], 'used_for');
    }

    $tag->copyValues($params);
    $tag->id = CRM_Utils_Array::value('tag', $ids);

    $edit = ($tag->id) ? TRUE : FALSE;
    if ($edit) {
      CRM_Utils_Hook::pre('edit', 'Tag', $tag->id, $tag);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Tag', NULL, $tag);
    }

    $tag->save();

    if ($edit) {
      CRM_Utils_Hook::post('edit', 'Tag', $tag->id, $tag);
    }
    else {
      CRM_Utils_Hook::post('create', 'Tag', NULL, $tag);
    }

    // if we modify parent tag, then we need to update all children
    if ($tag->parent_id === 'null') {
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_tag SET used_for=%1 WHERE parent_id = %2",
        [1 => [$params['used_for'], 'String'],
          2 => [$tag->id, 'Integer'],
        ]
      );
    }

    return $tag;
  }

  /**
   * Check if there is enough data to create a tag record.
   *
   * @param array &$params associative array of tag data
   *
   * @return bool TRUE if name is present
   */
  public static function dataExists(&$params) {
    if (!empty($params['name'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get tag sets for a specific entity table.
   *
   * @param string $entityTable entity table name
   *
   * @return string[] array of tag set names
   */
  public static function getTagSet($entityTable) {
    $tagSets = [];
    $query = "SELECT name FROM civicrm_tag WHERE is_tagset=1 AND parent_id IS NULL and used_for LIKE '%{$entityTable}%'";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray, TRUE, NULL, FALSE, FALSE);
    while ($dao->fetch()) {
      $tagSets[] = $dao->name;
    }
    return $tagSets;
  }

  /**
   * Get tags that are not children of any tag set.
   *
   * @return array associative array of (id => name)
   */
  public static function getTagsNotInTagset() {
    $tags = $tagSets = [];
    // first get all the tag sets
    $query = "SELECT id FROM civicrm_tag WHERE is_tagset=1 AND parent_id IS NULL";
    $dao = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {
      $tagSets[] = $dao->id;
    }

    $parentClause = '';
    if (!empty($tagSets)) {
      $parentClause = ' WHERE ( parent_id IS NULL ) OR ( parent_id NOT IN ( ' . CRM_Utils_Array::implode(',', $tagSets) . ' ) )';
    }

    // get that tags that don't have tagset as parent
    $query = "SELECT id, name FROM civicrm_tag {$parentClause}";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $tags[$dao->id] = $dao->name;
    }

    return $tags;
  }
}
