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



/**
 * Class contains functions for phone
 */
class CRM_Core_BAO_Phone extends CRM_Core_DAO_Phone {

  /**
   * takes an associative array and adds phone
   *
   * @param array  $params         (reference ) an assoc array of name/value pairs
   *
   * @return object       CRM_Core_BAO_Phone object on success, null otherwise
   * @access public
   * @static
   */
  static function add(&$params) {
    $phone = new CRM_Core_DAO_Phone();

    $phone->copyValues($params);

    return $phone->save();
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array entityBlock input parameters to find object
   *
   * @return array    array of phone objects
   * @access public
   * @static
   */
  static function &getValues($entityBlock) {
    $getValues = &CRM_Core_BAO_Block::getValues('phone', $entityBlock);
    return $getValues;
  }

  /**
   * Get all the phone numbers for a specified contact_id, with the primary being first
   *
   * @param int $id the contact id
   * @param boolean $updateBlankLocInfo
   * @param string $type Phone type name
   *
   * @return array  the array of phone ids which are potential numbers
   * @access public
   * @static
   */
  static function allPhones($id, $updateBlankLocInfo = FALSE, $type = NULL) {
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
   * Get all the phone numbers for a specified location_block id, with the primary phone being first
   *
   * @param array $entityElements the array containing entity_id and
   * entity_table name
   *
   * @return array  the array of phone ids which are potential numbers
   * @access public
   * @static
   */
  static function allEntityPhones($entityElements, $type = NULL) {
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
   * Set NULL to phone, mapping, uffield
   *
   * @param $optionId value of option to be deleted
   *
   * return void
   * @static
   */
  static function setOptionToNull($optionId) {
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
   * Get current exists id from value(phone)
   *
   * Only effect when phone id not provided. Id will be added into params before add.
   * 
   * @param array $params referenced array to be add exists phone id
   * @return void
   */
  static function valueExists(&$params) {
    if (empty($params['id']) && !empty($params['phone']) && !empty($params['contact_id'])) {
      $check = preg_replace('/[^0-9]/', '', $params['phone']);
      $params['id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_phone WHERE REGEXP_REPLACE(phone, '[^0-9]+', '') LIKE %1 AND contact_id = %2", [
        1 => [$check, 'String'],
        2 => [$params['contact_id'], 'Integer']
      ]);
    }
  }
}

