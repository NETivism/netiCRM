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

class CRM_Core_BAO_Discount extends CRM_Core_DAO_Discount {

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Delete a discount record.
   *
   * @param int $id discount ID
   *
   * @return bool TRUE on success, FALSE otherwise
   */
  public static function del($id) {
    // delete all discount records with the selected discounted id
    $discount = new CRM_Core_DAO_Discount();
    $discount->id = $id;
    if ($discount->delete()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Add or update a discount record.
   *
   * @param array &$params associative array of discount data
   *
   * @return CRM_Core_DAO_Discount the created/updated discount object
   */
  public static function add(&$params) {
    $discount = new CRM_Core_DAO_Discount();
    $discount->copyValues($params);
    $discount->save();
    return $discount;
  }

  /**
   * Get the option group IDs associated with discounts for a specific entity.
   *
   * @param int $entityId entity ID
   * @param string $entityTable entity table name
   *
   * @return array associative array of (discount_id => option_group_id)
   */
  public static function getOptionGroup($entityId, $entityTable) {
    $optionGroupIDs = [];

    $dao = new CRM_Core_DAO_Discount();
    $dao->entity_id = $entityId;
    $dao->entity_table = $entityTable;
    $dao->find();
    while ($dao->fetch()) {
      $optionGroupIDs[$dao->id] = $dao->option_group_id;
    }
    return $optionGroupIDs;
  }

  /**
   * Determine which discount set matches the registration date.
   *
   * @param int $entityID entity ID
   * @param string $entityTable entity table name
   * @param int $timestamp Unix timestamp to check against (defaults to CRM_REQUEST_TIME)
   *
   * @return int|bool|null discount ID of the matching set, or FALSE if none found
   */
  public static function findSet($entityID, $entityTable, $timestamp = CRM_REQUEST_TIME) {
    if (empty($entityID) ||
      empty($entityTable)
    ) {
      // adding this here, to trap errors if values are not sent
      CRM_Core_Error::fatal();
      return NULL;
    }

    $dao = new CRM_Core_DAO_Discount();
    $dao->entity_id = $entityID;
    $dao->entity_table = $entityTable;
    $dao->find();

    while ($dao->fetch()) {
      $endDate = $dao->end_date;
      // if end date is not we consider current date as end date
      if (!$endDate) {
        $endDate = date('Ymd');
      }
      $falls = CRM_Utils_Date::getRange($dao->start_date, $endDate, $timestamp);
      if ($falls == TRUE) {
        return $dao->id;
      }
    }
    return FALSE;
  }
}
