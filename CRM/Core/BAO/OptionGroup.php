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

class CRM_Core_BAO_OptionGroup extends CRM_Core_DAO_OptionGroup {

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve an option group record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_BAO_OptionGroup|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);
    if ($optionGroup->find(TRUE)) {
      CRM_Core_DAO::storeValues($optionGroup, $defaults);
      return $optionGroup;
    }
    return NULL;
  }

  /**
   * Update the is_active flag for an option group in the database.
   *
   * @param int $id ID of the database record
   * @param bool $is_active value to set for the is_active field
   *
   * @return CRM_Core_DAO_OptionGroup|null updated DAO object
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_OptionGroup', $id, 'is_active', $is_active);
  }

  /**
   * Add or update an option group record.
   *
   * @param array $params associative array of option group data
   * @param array $ids associative array containing 'optionGroup' ID if updating
   *
   * @return CRM_Core_DAO_OptionGroup the created/updated option group object
   */
  public static function add(&$params, &$ids) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);

    // action is taken depending upon the mode
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->copyValues($params);
    ;

    if ($params['is_default']) {
      $query = "UPDATE civicrm_option_group SET is_default = 0";
      CRM_Core_DAO::executeQuery($query);
    }

    $optionGroup->id = CRM_Utils_Array::value('optionGroup', $ids);
    $optionGroup->save();
    return $optionGroup;
  }

  /**
   * Delete an option group and all its associated option values.
   *
   * @param int $optionGroupId ID of the option group to delete
   *
   * @return void
   */
  public static function del($optionGroupId) {
    // need to delete all option value field before deleting group

    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->option_group_id = $optionGroupId;
    $optionValue->delete();

    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->delete();
  }

  /**
   * Get the name of an option group.
   *
   * @param int $optionGroupId ID of the option group
   *
   * @return string|null option group name
   */
  public static function getTitle($optionGroupId) {
    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionGroupId;
    $optionGroup->find(TRUE);
    return $optionGroup->name;
  }

  /**
   * Copy an option group and its values for a specific component.
   *
   * Useful for duplicating sets of options like contribution amounts or price fields.
   *
   * @param string $component component name (e.g., 'event', 'price')
   * @param int $fromId source component ID
   * @param int $toId target component ID
   * @param int|bool $defaultId default option value ID to map
   * @param string|null $discountSuffix optional suffix for discount groups
   *
   * @return int|bool the new default value ID if $defaultId was provided, TRUE on success, FALSE on failure
   */
  public static function copyValue($component, $fromId, $toId, $defaultId = FALSE, $discountSuffix = NULL) {
    $page = '_page';
    if ($component == 'event') {
      //fix for CRM-3391.
      //as for event we remove 'page' from group name.
      $page = NULL;
    }
    elseif ($component == 'price') {
      $page = '_field';
    }

    $fromGroupName = 'civicrm_' . $component . $page . '.amount.' . $fromId . $discountSuffix;
    $toGroupName = 'civicrm_' . $component . $page . '.amount.' . $toId . $discountSuffix;

    $optionGroupId = CRM_Core_DAO::getFieldValue(
      'CRM_Core_DAO_OptionGroup',
      $fromGroupName,
      'id',
      'name'
    );
    if ($optionGroupId) {
      $copyOptionGroup = &CRM_Core_DAO::copyGeneric(
        'CRM_Core_DAO_OptionGroup',
        ['name' => $fromGroupName],
        ['name' => $toGroupName]
      );

      $copyOptionValue = &CRM_Core_DAO::copyGeneric(
        'CRM_Core_DAO_OptionValue',
        ['option_group_id' => $optionGroupId],
        ['option_group_id' => $copyOptionGroup->id]
      );

      if ($discountSuffix) {
        $copyDiscount = &CRM_Core_DAO::copyGeneric(
          'CRM_Core_DAO_Discount',
          ['entity_id' => $fromId,
            'entity_table' => 'civicrm_' . $component,
            'option_group_id' => $optionGroupId,
          ],
          ['entity_id' => $toId,
            'option_group_id' => $copyOptionGroup->id,
          ]
        );
      }

      if ($defaultId) {
        $query = "
SELECT second.id default_id 
FROM civicrm_option_value first, civicrm_option_value second
WHERE second.option_group_id =%1
AND first.option_group_id =%2
AND first.weight = second.weight
AND first.id =%3
";
        $params = [1 => [$copyOptionGroup->id, 'Int'],
          2 => [$optionGroupId, 'Int'],
          3 => [$defaultId, 'Int'],
        ];

        $dao = CRM_Core_DAO::executeQuery($query, $params);

        while ($dao->fetch()) {
          $id = $dao->default_id;
        }
        return $id;
      }
      return FALSE;
    }
  }
}
