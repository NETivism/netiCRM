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
class CRM_Core_BAO_UFJoin extends CRM_Core_DAO_UFJoin {

  /**
   * Create, update, or delete a UF join record.
   *
   * @param array $params associative array of UF join data
   *
   * @return CRM_Core_DAO_UFJoin the join object
   */
  public static function &create($params) {
    // see if a record exists with the same weight
    $id = self::findJoinEntryId($params);
    if ($id) {
      $params['id'] = $id;
    }

    $dao = new CRM_Core_DAO_UFJoin();
    $dao->copyValues($params);
    if ($params['uf_group_id']) {
      $dao->save();
    }
    else {
      $dao->delete();
    }

    return $dao;
  }

  /**
   * Delete all UF join records for a specific module and entity.
   *
   * @param array &$params associative array containing 'module', 'entity_table', and 'entity_id'
   *
   * @return void
   */
  public static function &deleteAll(&$params) {
    $module = CRM_Utils_Array::value('module', $params);
    $entityTable = CRM_Utils_Array::value('entity_table', $params);
    $entityID = CRM_Utils_Array::value('entity_id', $params);

    if (empty($entityTable) ||
      empty($entityID) ||
      empty($module)
    ) {
      return;
    }

    $dao = new CRM_Core_DAO_UFJoin();
    $dao->module = $module;
    $dao->entity_table = $entityTable;
    $dao->entity_id = $entityID;
    $dao->delete();
  }

  /**
   * Find the ID of an existing UF join record based on parameters.
   *
   * @param array &$params associative array of identifying fields
   *
   * @return int|null join record ID if found, otherwise NULL
   */
  public static function findJoinEntryId(&$params) {
    if (CRM_Utils_Array::value('id', $params)) {
      return $params['id'];
    }

    $dao = new CRM_Core_DAO_UFJoin();

    // CRM-4377 (ab)uses the module column
    if (isset($params['module'])) {
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    // user reg / my account can have multiple entries, so we return if thats
    // the case. (since entity_table/id is empty in those cases
    if (!$dao->entity_table ||
      !$dao->entity_id
    ) {
      return NULL;
    }
    $dao->weight = CRM_Utils_Array::value('weight', $params);
    if ($dao->find(TRUE)) {
      return $dao->id;
    }
    return NULL;
  }

  /**
   * Find the UF group ID for a specific join record.
   *
   * @param array &$params associative array of identifying fields
   *
   * @return int|null UF group ID if found, otherwise NULL
   */
  public static function findUFGroupId(&$params) {

    $dao = new CRM_Core_DAO_UFJoin();

    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    $dao->weight = CRM_Utils_Array::value('weight', $params);
    if (!empty($params['module'])) {
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    if ($dao->find(TRUE)) {
      return $dao->uf_group_id;
    }
    return NULL;
  }

  /**
   * Get the primary and secondary UF group IDs associated with an entity.
   *
   * @param array &$params associative array identifying the entity
   *
   * @return array [first_gid, second_gid, first_active, second_active]
   */
  public static function getUFGroupIds(&$params) {

    $dao = new CRM_Core_DAO_UFJoin();

    // CRM-4377 (ab)uses the module column
    if (isset($params['module'])) {
      $dao->module = CRM_Utils_Array::value('module', $params);
    }
    $dao->entity_table = CRM_Utils_Array::value('entity_table', $params);
    $dao->entity_id = CRM_Utils_Array::value('entity_id', $params);
    $dao->orderBy('weight asc');

    $first = $second = $firstActive = $secondActive = NULL;
    $firstWeight = NULL;
    $dao->find();
    if ($dao->fetch()) {
      $first = $dao->uf_group_id;
      $firstWeight = $dao->weight;
      $firstActive = $dao->is_active;
    }
    while ($dao->fetch()) {
      if ($first != $dao->uf_group_id) {
        $second = $dao->uf_group_id;
        $secondActive = $dao->is_active;
        break;
      }
    }

    // if there is only one profile check to see the weight, if > 1 then let it be second
    // this is an approx rule, but should work in most cases.
    if ($second == NULL &&
      $firstWeight > 1
    ) {
      $second = $first;
      $first = NULL;
    }

    return [$first, $second, $firstActive, $secondActive];
  }
}
