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
 * Class contains functions for phone
 */
class CRM_Core_BAO_Phone extends CRM_Core_DAO_Phone {

  /**
   * Add or update a phone record.
   *
   * @param array &$params associative array of phone data
   *
   * @return CRM_Core_DAO_Phone the created/updated phone object
   */
  public static function add(&$params) {
    $phone = new CRM_Core_DAO_Phone();

    $phone->copyValues($params);

    return $phone->save();
  }

  /**
   * Fetch phone values based on entity criteria.
   *
   * @param array $entityBlock associative array containing entity identifying fields
   *
   * @return array array of phone data arrays
   */
  public static function &getValues($entityBlock) {
    $getValues = &CRM_Core_BAO_Block::getValues('phone', $entityBlock);
    return $getValues;
  }

  /**
   * Get all phone numbers for a specified contact, ordered by primary phone first.
   *
   * @param int $id contact ID
   * @param bool $updateBlankLocInfo if TRUE, return indexed sequentially; otherwise by ID
   * @param string|null $type optional phone type filter
   *
   * @return array array of phone details
   */
  public static function allPhones($id, $updateBlankLocInfo = FALSE, $type = NULL) {
    if (!$id) {
      return NULL;
    }

    $cond = NULL;
    if ($type) {

      $phoneTypeId = array_search($type, CRM_Core_PseudoConstant::phoneType());
      if ($phoneTypeId) {
        $cond = " AND civicrm_phone.phone_type_id = $phoneTypeId";
      }
    }

    $query = "
   SELECT phone, civicrm_location_type.name as locationType, civicrm_phone.is_primary as is_primary,
     civicrm_phone.id as phone_id, civicrm_phone.location_type_id as locationTypeId, civicrm_phone.phone_type_id
     FROM civicrm_contact
LEFT JOIN civicrm_phone ON ( civicrm_contact.id = civicrm_phone.contact_id )
LEFT JOIN civicrm_location_type ON ( civicrm_phone.location_type_id = civicrm_location_type.id )
WHERE     civicrm_contact.id = %1 $cond
ORDER BY civicrm_phone.is_primary DESC,  phone_id ASC ";

    $params = [1 => [$id, 'Integer']];

    $numbers = $values = [];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = ['locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->phone_id,
        'phone' => $dao->phone,
        'phone_type_id' => $dao->phone_type_id,
        'locationTypeId' => $dao->locationTypeId,
      ];

      if ($updateBlankLocInfo) {
        $numbers[$count++] = $values;
      }
      else {
        $numbers[$dao->phone_id] = $values;
      }
    }
    return $numbers;
  }

  /**
   * Get all phone numbers for a specified entity via its location block.
   *
   * @param array $entityElements array containing 'entity_id' and 'entity_table'
   * @param string|null $type optional phone type filter
   *
   * @return array|null array of phone details
   */
  public static function allEntityPhones($entityElements, $type = NULL) {
    if (empty($entityElements)) {
      return NULL;
    }

    $cond = NULL;
    if ($type) {

      $phoneTypeId = array_search($type, CRM_Core_PseudoConstant::phoneType());
      if ($phoneTypeId) {
        $cond = " AND civicrm_phone.phone_type_id = $phoneTypeId";
      }
    }

    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];

    $sql = " SELECT phone, ltype.name as locationType, ph.is_primary as is_primary,
     ph.id as phone_id, ph.location_type_id as locationTypeId
FROM civicrm_loc_block loc, civicrm_phone ph, civicrm_location_type ltype, {$entityTable} ev
WHERE ev.id = %1
AND   loc.id = ev.loc_block_id
AND   ph.id IN (loc.phone_id, loc.phone_2_id)
AND   ltype.id = ph.location_type_id
ORDER BY ph.is_primary DESC, phone_id ASC ";

    $params = [1 => [$entityId, 'Integer']];
    $numbers = [];
    $dao = &CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $numbers[$dao->phone_id] = ['locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->phone_id,
        'phone' => $dao->phone,
        'locationTypeId' => $dao->locationTypeId,
      ];
    }
    return $numbers;
  }

  /**
   * Set phone_type_id to NULL in related tables when an option is deleted.
   *
   * @param int $optionId ID of the phone type option being deleted
   *
   * @return void
   */
  public static function setOptionToNull($optionId) {
    if (!$optionId) {
      return;
    }

    $tables = ['civicrm_phone', 'civicrm_mapping_field', 'civicrm_uf_field'];
    $params = [1 => [$optionId, 'Integer']];

    foreach ($tables as $tableName) {
      $query = "UPDATE `{$tableName}` SET `phone_type_id` = NULL WHERE `phone_type_id` = %1";
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Check if a phone number already exists for a contact and set the 'id' parameter.
   *
   * Only performs lookup if 'id' is not provided and phone/contact_id are present.
   *
   * @param array &$params associative array of phone fields (passed by reference)
   *
   * @return void
   */
  public static function valueExists(&$params) {
    if (empty($params['id']) && !empty($params['phone']) && !empty($params['contact_id'])) {
      $check = preg_replace('/[^0-9]/', '', $params['phone']);
      $params['id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_phone WHERE REGEXP_REPLACE(phone, '[^0-9]+', '') LIKE %1 AND contact_id = %2", [
        1 => [$check, 'String'],
        2 => [$params['contact_id'], 'Integer']
      ]);
    }
  }
}
