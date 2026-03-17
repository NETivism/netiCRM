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
 * This class handle creation of location block elements
 */
class CRM_Core_BAO_Location extends CRM_Core_DAO {

  /**
   * Location block element array
   */
  public static $blocks = ['phone', 'email', 'im', 'openid', 'address'];

  /**
   * Create various elements of a location block (phone, email, im, openid, address).
   *
   * @param array &$params associative array of name/value pairs
   * @param bool $fixAddress TRUE if address values should be formatted/standardized
   * @param string|null $entity the entity type if not a contact
   *
   * @return array array of created location block elements
   */
  public static function create(&$params, $fixAddress = TRUE, $entity = NULL) {
    $location = [];
    if (!self::dataExists($params)) {
      return $location;
    }

    // create location blocks.
    foreach (self::$blocks as $block) {
      if ($block != 'address') {
        $location[$block] = CRM_Core_BAO_Block::create($block, $params, $entity);
      }
      else {
        $location[$block] = CRM_Core_BAO_Address::create($params, $fixAddress, $entity);
      }
    }

    if ($entity) {
      // this is a special case for adding values in location block table
      $entityElements = ['entity_table' => $params['entity_table'],
        'entity_id' => $params['entity_id'],
      ];

      $location['id'] = self::createLocBlock($location, $entityElements);
    }
    else {
      // make sure contact should have only one primary block, CRM-5051
      self::checkPrimaryBlocks(CRM_Utils_Array::value('contact_id', $params));
      // make sure we always have billing flag #20146
      self::checkBillingAddress(CRM_Utils_Array::value('contact_id', $params));
    }

    return $location;
  }

  /**
   * Create an entry in the civicrm_loc_block table.
   *
   * @param array &$location array of created location block element objects
   * @param array &$entityElements array containing 'entity_id' and 'entity_table'
   *
   * @return int|null the ID of the created location block, or NULL on failure
   */
  public static function createLocBlock(&$location, &$entityElements) {
    $locId = self::findExisting($entityElements);
    $locBlock = [];

    if ($locId) {
      $locBlock['id'] = $locId;
    }

    $locBlock['phone_id'] = $location['phone'][0]->id;
    $locBlock['phone_2_id'] = CRM_Utils_Array::value(1, $location['phone']) ? $location['phone'][1]->id : NULL;
    $locBlock['email_id'] = $location['email'][0]->id;
    $locBlock['email_2_id'] = CRM_Utils_Array::value(1, $location['email']) ? $location['email'][1]->id : NULL;
    $locBlock['im_id'] = $location['im'][0]->id;
    $locBlock['im_2_id '] = CRM_Utils_Array::value(1, $location['im']) ? $location['im'][1]->id : NULL;
    $locBlock['address_id'] = $location['address'][0]->id;
    $locBlock['address_2_id'] = CRM_Utils_Array::value(1, $location['address']) ? $location['address'][1]->id : NULL;

    $countNull = 0;
    foreach ($locBlock as $key => $block) {
      if (empty($locBlock[$key])) {
        $locBlock[$key] = 'null';
        $countNull++;
      }
    }

    if (count($locBlock) == $countNull) {
      // implies nothing is set.
      return NULL;
    }

    $locBlockInfo = self::addLocBlock($locBlock);
    return $locBlockInfo->id;
  }

  /**
   * Find the existing location block ID for a given entity.
   *
   * @param array $entityElements array containing 'entity_id' and 'entity_table'
   *
   * @return int|null the location block ID if found, otherwise NULL
   */
  public static function findExisting($entityElements) {
    $eid = $entityElements['entity_id'];
    $etable = $entityElements['entity_table'];
    $query = "
SELECT e.loc_block_id as locId
FROM {$etable} e
WHERE e.id = %1";

    $params = [1 => [$eid, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $locBlockId = $dao->locId;
    }
    return $locBlockId;
  }

  /**
   * Add a location block record.
   *
   * @param array &$params associative array of location block data
   *
   * @return CRM_Core_DAO_LocBlock the created/updated location block object
   */
  public static function addLocBlock(&$params) {
    $locBlock = new CRM_Core_DAO_LocBlock();

    $locBlock->copyValues($params);

    return $locBlock->save();
  }

  /**
   * Delete a location block and all its associated elements (address, phone, etc.).
   *
   * @param int $locBlockId ID of the location block to delete
   *
   * @return void
   */
  public static function deleteLocBlock($locBlockId) {
    if (!$locBlockId) {
      return;
    }

    $locBlock = new CRM_Core_DAO_LocBlock();
    $locBlock->id = $locBlockId;

    $locBlock->find(TRUE);

    //resolve conflict of having same ids for multiple blocks
    $store = [
      'IM_1' => $locBlock->im_id,
      'IM_2' => $locBlock->im_2_id,
      'Email_1' => $locBlock->email_id,
      'Email_2' => $locBlock->email_2_id,
      'Phone_1' => $locBlock->phone_id,
      'Phone_2' => $locBlock->phone_2_id,
      'Address_1' => $locBlock->address_id,
      'Address_2' => $locBlock->address_2_id,
    ];
    $locBlock->delete();
    foreach ($store as $daoName => $id) {
      if ($id) {
        $daoName = 'CRM_Core_DAO_' . substr($daoName, 0, -2);
        $dao = new $daoName();
        $dao->id = $id;
        $dao->find(TRUE);
        $dao->delete();
        $dao->free();
      }
    }
  }

  /**
   * Check if any location block data exists in the parameters.
   *
   * @param array &$params associative array of name/value pairs
   *
   * @return bool TRUE if at least one location block element is present
   */
  public static function dataExists(&$params) {
    // return if no data present
    $dataExists = FALSE;
    foreach (self::$blocks as $block) {
      if (CRM_Utils_Array::arrayKeyExists($block, $params)) {
        $dataExists = TRUE;
        break;
      }
    }

    return $dataExists;
  }

  /**
   * Fetch all location values for a given entity.
   *
   * @param array $entityBlock associative array containing entity identifying fields
   * @param bool $microformat TRUE if microformat output is required
   *
   * @return array|null nested array of location values (keyed by block type)
   */
  public static function &getValues($entityBlock, $microformat = FALSE) {
    if (empty($entityBlock)) {
      return NULL;
    }

    //get all the blocks for this contact
    foreach (self::$blocks as $block) {
      if (strcasecmp('im', $block) === 0) {
        $name = 'CRM_Core_BAO_IM';
      }
      elseif (strcasecmp('openid', $block) === 0) {
        $name = 'CRM_Core_BAO_OpenID';
      }
      else {
        $name = 'CRM_Core_BAO_'.ucfirst($block);
      }
      $blocks[$block] = $name::getValues($entityBlock, $microformat);
    }
    return $blocks;
  }

  /**
   * Delete all blocks (address, phone, etc.) associated with a specific location type for a contact.
   *
   * @param int $contactId contact ID
   * @param int|string $locationTypeId location type ID (can be 'null')
   *
   * @return void
   */
  public static function deleteLocationBlocks($contactId, $locationTypeId) {
    // ensure that contactId has a value
    if (empty($contactId) ||
      !CRM_Utils_Rule::positiveInteger($contactId)
    ) {
      CRM_Core_Error::fatal();
    }

    if (empty($locationTypeId) ||
      !CRM_Utils_Rule::positiveInteger($locationTypeId)
    ) {
      // so we only delete the blocks which DO NOT have a location type Id
      // CRM-3581
      $locationTypeId = 'null';
    }

    static $blocks = ['Address', 'Phone', 'IM', 'OpenID', 'Email'];

    $params = ['contact_id' => $contactId, 'location_type_id' => $locationTypeId];
    foreach ($blocks as $name) {
      CRM_Core_BAO_Block::blockDelete($name, $params);
    }
  }

  /**
   * Copy or update a location block and all its elements.
   *
   * @param int $locBlockId source location block ID
   * @param int|null $updateLocBlockId optional target location block ID to update
   *
   * @return int ID of the newly created or updated location block
   */
  public static function copyLocBlock($locBlockId, $updateLocBlockId = NULL) {
    //get the location info.
    $defaults = $updateValues = [];
    $locBlock = ['id' => $locBlockId];
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_LocBlock', $locBlock, $defaults);

    if ($updateLocBlockId) {
      //get the location info for update.
      $copyLocationParams = ['id' => $updateLocBlockId];
      CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_LocBlock', $copyLocationParams, $updateValues);
      foreach ($updateValues as $key => $value) {
        if ($key != 'id') {
          $copyLocationParams[$key] = 'null';
        }
      }
    }

    //copy all location blocks (email, phone, address, etc)
    foreach ($defaults as $key => $value) {
      if ($key != 'id') {
        $tbl = explode("_", $key);
        $name = ucfirst($tbl[0]);
        $updateParams = NULL;
        if ($updateId = CRM_Utils_Array::value($key, $updateValues)) {
          $updateParams = ['id' => $updateId];
        }

        $copy = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_' . $name, ['id' => $value], $updateParams);
        $copyLocationParams[$key] = $copy->id;
      }
    }

    $copyLocation = &CRM_Core_DAO::copyGeneric(
      'CRM_Core_DAO_LocBlock',
      ['id' => $locBlock['id']],
      $copyLocationParams
    );
    return $copyLocation->id;
  }

  /**
   * Ensure that a contact has only one primary block for each location type element.
   *
   * If multiple primary blocks exist, resets all but one. If no primary block
   * exists but data does, makes one primary.
   *
   * @param int $contactId contact ID
   *
   * @return void
   */
  public static function checkPrimaryBlocks($contactId) {
    if (!$contactId) {
      return;
    }

    // get the loc block ids.

    $primaryLocBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($contactId, ['is_primary' => 1]);
    $nonPrimaryBlockIds = CRM_Contact_BAO_Contact::getLocBlockIds($contactId, ['is_primary' => 0]);

    foreach (['Email', 'IM', 'Phone', 'Address', 'OpenID'] as $block) {
      $name = strtolower($block);
      if (CRM_Utils_Array::arrayKeyExists($name, $primaryLocBlockIds) &&
        !CRM_Utils_System::isNull($primaryLocBlockIds[$name])
      ) {
        if (count($primaryLocBlockIds[$name]) > 1) {
          // keep only single block as primary.
          $primaryId = array_pop($primaryLocBlockIds[$name]);
          $resetIds = "(" . CRM_Utils_Array::implode(',', $primaryLocBlockIds[$name]) . ")";
          // reset all primary except one.
          CRM_Core_DAO::executeQuery("UPDATE civicrm_$name SET is_primary = 0 WHERE id IN $resetIds");
        }
      }
      elseif (CRM_Utils_Array::arrayKeyExists($name, $nonPrimaryBlockIds) &&
        !CRM_Utils_System::isNull($nonPrimaryBlockIds[$name])
      ) {
        // data exists and no primary block - make one primary.
        CRM_Core_DAO::setFieldValue(
          "CRM_Core_DAO_" . $block,
          array_pop($nonPrimaryBlockIds[$name]),
          'is_primary',
          1
        );
      }
    }
  }

  /**
   * Ensure that a contact has exactly one billing address and handles location type logic.
   *
   * @param int $contactId contact ID
   *
   * @return void
   */
  public static function checkBillingAddress($contactId) {
    if (!$contactId) {
      return;
    }

    $dao = CRM_Core_DAO::executeQuery("SELECT id, location_type_id, is_billing, is_primary FROM civicrm_address WHERE contact_id = %1", [1 => [$contactId, 'Integer']]);
    $addr = ['billing' => [], 'nonbilling' => []];
    while ($dao->fetch()) {
      if ($dao->is_billing) {
        $addr['billing'][$dao->id] = $dao->location_type_id;
      }
      else {
        $addr['nonbilling'][$dao->id] = $dao->location_type_id;
      }
    }
    $locationTypes = CRM_Core_PseudoConstant::locationType(TRUE, 'name');
    $billingLocationTypeId = array_search('Billing', $locationTypes);

    if (count($addr['billing']) > 1) {
      // keep only single block as billing.
      $keepId = 0;
      foreach ($addr['billing'] as $addressId => $locationTypeId) {
        if ($locationTypeId == $billingLocationTypeId) {
          $keepId = $addressId;
          break;
        }
      }
      if (!$keepId) {
        $keep = array_pop($addr['billing']);
      }
      unset($addr['billing'][$keepId]);
      $restIds = "(" . CRM_Utils_Array::implode(',', array_keys($addr['billing'])) . ")";
      CRM_Core_DAO::executeQuery("UPDATE civicrm_address SET is_billing = 0 WHERE id IN $restIds");
    }
    elseif (count($addr['billing']) == 0 && count($addr['nonbilling']) > 0) {
      $setBilling = FALSE;
      foreach ($addr['nonbilling'] as $addressId => $locationTypeId) {
        if ($locationTypeId == $billingLocationTypeId) {
          CRM_Core_DAO::setFieldValue("CRM_Core_DAO_Address", $addressId, 'is_billing', 1);
          $setBilling = TRUE;
          break;
        }
      }
      if (!$setBilling) {
        reset($addr['nonbilling']);
        $firstAddr = key($addr['nonbilling']);
        CRM_Core_DAO::setFieldValue("CRM_Core_DAO_Address", $firstAddr, 'is_billing', 1);
      }
    }
  }
}
