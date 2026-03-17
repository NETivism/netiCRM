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

class CRM_Core_BAO_OptionValue extends CRM_Core_DAO_OptionValue {

  /**
   * class constructor
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve an option value record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_BAO_OptionValue|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->copyValues($params);
    if ($optionValue->find(TRUE)) {
      CRM_Core_DAO::storeValues($optionValue, $defaults);
      return $optionValue;
    }
    return NULL;
  }

  /**
   * Update the is_active flag for an option value in the database.
   *
   * @param int $id ID of the database record
   * @param bool $is_active value to set for the is_active field
   *
   * @return CRM_Core_DAO_OptionValue|null updated DAO object
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_OptionValue', $id, 'is_active', $is_active);
  }

  /**
   * Add or update an option value record.
   *
   * @param array $params associative array of option value data
   * @param array $ids associative array containing 'optionValue' ID if updating
   *
   * @return CRM_Core_DAO_OptionValue the created/updated option value object
   */
  public static function add(&$params, &$ids) {
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, FALSE);
    $params['is_default'] = CRM_Utils_Array::value('is_default', $params, FALSE);
    $params['is_optgroup'] = CRM_Utils_Array::value('is_optgroup', $params, FALSE);
    $params['filter'] = CRM_Utils_Array::value('filter', $params, FALSE);

    // action is taken depending upon the mode
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->copyValues($params);
    ;

    if (CRM_Utils_Array::value('is_default', $params)) {
      $query = 'UPDATE civicrm_option_value SET is_default = 0 WHERE  option_group_id = %1';

      // tweak default reset, and allow multiple default within group.
      if ($resetDefaultFor = CRM_Utils_Array::value('reset_default_for', $params)) {
        if (is_array($resetDefaultFor)) {
          $colName = key($resetDefaultFor);
          $colVal = $resetDefaultFor[$colName];
          $query .= " AND ( $colName IN (  $colVal ) )";
        }
      }

      $p = [1 => [$params['option_group_id'], 'Integer']];
      CRM_Core_DAO::executeQuery($query, $p);
    }

    $groupName = CRM_Core_DAO::getFieldValue(
      'CRM_Core_DAO_OptionGroup',
      $params['option_group_id'],
      'name',
      'id'
    );

    if (in_array($groupName, CRM_Core_OptionGroup::$_domainIDGroups)) {
      $optionValue->domain_id = CRM_Core_Config::domainID();
    }

    $optionValue->id = CRM_Utils_Array::value('optionValue', $ids);
    $optionValue->save();
    CRM_Core_PseudoConstant::flush();
    return $optionValue;
  }

  /**
   * Delete an option value and update any affected entity records.
   *
   * @param int $optionValueId ID of the option value to delete
   *
   * @return bool TRUE on success, FALSE otherwise
   */
  public static function del($optionValueId) {
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->id = $optionValueId;

    if (self::updateRecords($optionValueId, CRM_Core_Action::DELETE)) {
      CRM_Core_PseudoConstant::flush();
      return $optionValue->delete();
    }
    return FALSE;
  }

  /**
   * Retrieve the label and description for a specific activity type.
   *
   * @param int $activityTypeId activity type ID
   *
   * @return array [label, description, name]
   */
  public static function getActivityTypeDetails($activityTypeId) {
    $query = "SELECT civicrm_option_value.label, civicrm_option_value.description, civicrm_option_value.name
   FROM civicrm_option_value
        LEFT JOIN civicrm_option_group ON ( civicrm_option_value.option_group_id = civicrm_option_group.id )
   WHERE civicrm_option_group.name = 'activity_type'
         AND civicrm_option_value.value =  {$activityTypeId} ";

    $dao = &CRM_Core_DAO::executeQuery($query);

    $dao->fetch();

    return [$dao->label, $dao->description, $dao->name];
  }

  /**
   * Get the label/title of an option value.
   *
   * @param int $id ID of the option value
   *
   * @return string|null option value label
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $id, 'label');
  }

  /**
   * Update records in related tables when an option value is changed or deleted.
   *
   * For example, updates contact records if a gender option is modified.
   *
   * @param int &$optionValueId ID of the option value (passed by reference)
   * @param int $action the action being performed (e.g., CRM_Core_Action::DELETE)
   *
   * @return bool TRUE if records were updated successfully, FALSE otherwise
   */
  public static function updateRecords(&$optionValueId, $action) {
    //finding group name
    $optionValue = new CRM_Core_DAO_OptionValue();
    $optionValue->id = $optionValueId;
    $optionValue->find(TRUE);

    $optionGroup = new CRM_Core_DAO_OptionGroup();
    $optionGroup->id = $optionValue->option_group_id;
    $optionGroup->find(TRUE);

    // group name
    $gName = $optionGroup->name;
    // value
    $value = $optionValue->value;

    // get the proper group name & affected field name
    $individuals = ['gender' => 'gender_id',
      'individual_prefix' => 'prefix_id',
      'individual_suffix' => 'suffix_id',
    ];
    $contributions = ['payment_instrument' => 'payment_instrument_id'];
    $activities = ['activity_type' => 'activity_type_id'];
    $participant = ['participant_role' => 'role_id'];
    $eventType = ['event_type' => 'event_type_id'];
    $aclRole = ['acl_role' => 'acl_role_id'];

    $all = array_merge($individuals, $contributions, $activities, $participant, $eventType, $aclRole);
    $fieldName = '';

    foreach ($all as $name => $id) {
      if ($gName == $name) {
        $fieldName = $id;
      }
    }
    if ($fieldName == '') {
      return TRUE;
    }

    if (CRM_Utils_Array::arrayKeyExists($gName, $individuals)) {

      $contactDAO = new CRM_Contact_DAO_Contact();

      $contactDAO->$fieldName = $value;
      $contactDAO->find();

      while ($contactDAO->fetch()) {
        if ($action == CRM_Core_Action::DELETE) {
          $contact = new CRM_Contact_DAO_Contact();
          $contact->id = $contactDAO->id;
          $contact->find(TRUE);

          // make sure dates doesn't get reset
          $contact->birth_date = CRM_Utils_Date::isoToMysql($contact->birth_date);
          $contact->deceased_date = CRM_Utils_Date::isoToMysql($contact->deceased_date);
          $contact->$fieldName = 'NULL';
          $contact->save();
        }
      }

      return TRUE;
    }

    if (CRM_Utils_Array::arrayKeyExists($gName, $contributions)) {

      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->$fieldName = $value;
      $contribution->find();
      while ($contribution->fetch()) {
        if ($action == CRM_Core_Action::DELETE) {
          $contribution->$fieldName = 'NULL';
          $contribution->save();
        }
      }
      return TRUE;
    }

    if (CRM_Utils_Array::arrayKeyExists($gName, $activities)) {

      $activity = new CRM_Activity_DAO_Activity();
      $activity->$fieldName = $value;
      $activity->find();
      while ($activity->fetch()) {
        $activity->delete();
      }
      return TRUE;
    }

    //delete participant role, type and event type option value
    if (CRM_Utils_Array::arrayKeyExists($gName, $participant)) {

      $participantValue = new CRM_Event_DAO_Participant();
      $participantValue->$fieldName = $value;
      if ($participantValue->find(TRUE)) {
        return FALSE;
      }
      return TRUE;
    }

    //delete event type option value
    if (CRM_Utils_Array::arrayKeyExists($gName, $eventType)) {

      $event = new CRM_Event_DAO_Event();
      $event->$fieldName = $value;
      if ($event->find(TRUE)) {
        return FALSE;
      }
      return TRUE;
    }

    //delete acl_role option value
    if (CRM_Utils_Array::arrayKeyExists($gName, $aclRole)) {

      $entityRole = new CRM_ACL_DAO_EntityRole();
      $entityRole->$fieldName = $value;

      $aclDAO = new CRM_ACL_DAO_ACL();
      $aclDAO->entity_id = $value;
      if ($entityRole->find(TRUE) || $aclDAO->find(TRUE)) {
        return FALSE;
      }
      return TRUE;
    }
  }

  /**
   * Update the weights of multiple option values within a group.
   *
   * @param int $opGroupId option group ID
   * @param array $opWeights associative array of (option_value => weight)
   *
   * @return void
   */
  public static function updateOptionWeights($opGroupId, $opWeights) {
    if (!is_array($opWeights) || empty($opWeights)) {
      return;
    }

    foreach ($opWeights as $opValue => $opWeight) {
      $optionValue = new CRM_Core_DAO_OptionValue();
      $optionValue->option_group_id = $opGroupId;
      $optionValue->value = $opValue;
      if ($optionValue->find(TRUE)) {
        $optionValue->weight = $opWeight;
        $optionValue->save();
      }
      $optionValue->free();
    }
  }

  /**
   * Get all option values for a group ID, using the system cache for performance.
   *
   * @param int $optionGroupID option group ID
   *
   * @return array array of option value arrays
   */
  public static function getOptionValuesArray($optionGroupID) {
    // check if we can get the field values from the system cache
    $cacheKey = __CLASS__ . '::' . __FUNCTION__ . '--' . $optionGroupID;
    $cache = CRM_Utils_Cache::singleton();
    $optionValues = $cache->get($cacheKey);
    if (empty($optionValues)) {
      $dao = new CRM_Core_DAO_OptionValue();
      $dao->option_group_id = $optionGroupID;
      $dao->orderBy('weight ASC, label ASC');
      $dao->find();

      $optionValues = [];
      while ($dao->fetch()) {
        $optionValues[$dao->id] = [];
        CRM_Core_DAO::storeValues($dao, $optionValues[$dao->id]);
      }

      $cache->set($cacheKey, $optionValues);
    }

    return $optionValues;
  }

  /**
   * Get label/value pairs for an option group ID, using the system cache.
   *
   * @param int $optionGroupID option group ID
   *
   * @return array associative array of (value => label)
   */
  public static function getOptionValuesAssocArray($optionGroupID) {
    $optionValues = self::getOptionValuesArray($optionGroupID);

    $options = [];
    foreach ($optionValues as $id => $value) {
      $options[$value['value']] = $value['label'];
    }
    return $options;
  }

  /**
   * Get label/value pairs for an option group name, using the system cache.
   *
   * @param string $optionGroupName option group name
   *
   * @return array associative array of (value => label)
   */
  public static function getOptionValuesAssocArrayFromName($optionGroupName) {
    $dao = new CRM_Core_DAO_OptionGroup();
    $dao->name = $optionGroupName;
    $dao->selectAdd();
    $dao->selectAdd('id');
    $dao->find(TRUE);
    $optionValues = self::getOptionValuesArray($dao->id);

    $options = [];
    foreach ($optionValues as $id => $value) {
      $options[$value['value']] = $value['label'];
    }
    return $options;
  }
}
