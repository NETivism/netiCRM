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
 * This class contains function for UFField
 *
 */
class CRM_Core_BAO_UFField extends CRM_Core_DAO_UFField {

  /**
   * Retrieve a profile field record based on the provided parameters.
   *
   * @param array $params associative array of identifying fields
   * @param array $defaults associative array to hold retrieved values
   *
   * @return CRM_Core_BAO_UFField|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_UFField', $params, $defaults);
  }

  /**
   * Get the label of a profile field.
   *
   * @param int $id ID of the profile field
   *
   * @return string|null field label
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $id, 'title');
  }

  /**
   * Update the is_active flag for a profile field.
   *
   * Checks if the underlying custom field is active before enabling.
   *
   * @param int $id ID of the profile field
   * @param bool $is_active value to set for the is_active field
   *
   * @return CRM_Core_DAO_UFField|null updated DAO object
   */
  public static function setIsActive($id, $is_active) {
    //check if custom data profile field is disabled
    if ($is_active) {
      if (CRM_Core_BAO_UFField::checkUFStatus($id)) {
        return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFField', $id, 'is_active', $is_active);
      }
      else {
        CRM_Core_Session::setStatus(ts('Cannot enable this UF field since the used custom field is disabled.'));
      }
    }
    else {
      return CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFField', $id, 'is_active', $is_active);
    }
  }

  /**
   * Delete a profile field.
   *
   * @param int $id ID of the profile field
   *
   * @return bool TRUE on success
   */
  public static function del($id) {
    //delete  field field
    $field = new CRM_Core_DAO_UFField();
    $field->id = $id;
    $field->delete();
    return TRUE;
  }

  /**
   * Check for duplicate fields within a profile group.
   *
   * @param array $params associative array of field data
   * @param array $ids associative array containing 'uf_group' ID
   *
   * @return bool TRUE if a duplicate exists, FALSE otherwise
   */
  public static function duplicateField($params, $ids) {
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->uf_group_id = CRM_Utils_Array::value('uf_group', $ids);
    $ufField->field_type = $params['field_name'][0];
    $ufField->field_name = $params['field_name'][1];
    $ufField->location_type_id = (CRM_Utils_Array::value(2, $params['field_name'])) ? $params['field_name'][2] : 'NULL';
    if ($params['field_name'][1] == 'url') {
      $ufField->website_type_id = CRM_Utils_Array::value(2, $params['field_name'], NULL);
    }
    else {
      $ufField->location_type_id = (CRM_Utils_Array::value(2, $params['field_name'])) ? $params['field_name'][2] : 'NULL';
    }
    $ufField->phone_type_id = CRM_Utils_Array::value(3, $params['field_name']);

    if (CRM_Utils_Array::value('uf_field', $ids)) {
      $ufField->whereAdd("id <> " . CRM_Utils_Array::value('uf_field', $ids));
    }

    return $ufField->find(TRUE);
  }

  /**
   * Add or update a profile field record.
   *
   * @param array $params associative array of field data
   * @param array $ids associative array containing 'uf_field' ID if updating
   *
   * @return CRM_Core_DAO_UFField the created/updated field object
   */
  public static function add(&$params, &$ids) {
    // set values for uf field properties and save
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_type = $params['field_name'][0];
    $ufField->field_name = $params['field_name'][1];

    //should not set location type id for Primary
    $locationTypeId = NULL;
    if ($params['field_name'][1] == 'url') {
      $ufField->website_type_id = CRM_Utils_Array::value(2, $params['field_name']);
    }
    else {
      $locationTypeId = CRM_Utils_Array::value(2, $params['field_name']);
      $ufField->website_type_id = NULL;
    }
    if ($locationTypeId) {
      $ufField->location_type_id = $locationTypeId;
    }
    else {
      $ufField->location_type_id = 'null';
    }

    $ufField->phone_type_id = CRM_Utils_Array::value(3, $params['field_name'], 'NULL');
    $ufField->listings_title = CRM_Utils_Array::value('listings_title', $params);
    $ufField->visibility = CRM_Utils_Array::value('visibility', $params);
    $ufField->help_post = CRM_Utils_Array::value('help_post', $params);
    $ufField->label = CRM_Utils_Array::value('label', $params);
    $ufField->is_required = CRM_Utils_Array::value('is_required', $params, FALSE);
    $ufField->is_active = CRM_Utils_Array::value('is_active', $params, FALSE);
    $ufField->in_selector = CRM_Utils_Array::value('in_selector', $params, FALSE);
    $ufField->is_view = CRM_Utils_Array::value('is_view', $params, FALSE);
    $ufField->is_registration = CRM_Utils_Array::value('is_registration', $params, FALSE);
    $ufField->is_match = CRM_Utils_Array::value('is_match', $params, FALSE);
    $ufField->is_searchable = CRM_Utils_Array::value('is_searchable', $params, FALSE);

    // fix for CRM-316
    $oldWeight = NULL;

    if (CRM_Utils_Array::value('field_id', $params)) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $params['field_id'], 'weight', 'id');
    }
    $fieldValues = ['uf_group_id' => $params['group_id']];

    $ufField->weight = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_UFField', $oldWeight, $params['weight'], $fieldValues);

    // need the FKEY - uf group id
    $ufField->uf_group_id = CRM_Utils_Array::value('uf_group', $ids, FALSE);
    $ufField->id = CRM_Utils_Array::value('uf_field', $ids, FALSE);

    return $ufField->save();
  }

  /**
   * Automatically calculate the weight for a profile field.
   *
   * @param array $params field data including 'weight', 'group_id', and 'field_id'
   *
   * @return int the calculated weight
   */
  public static function autoWeight($params) {
    // fix for CRM-316
    $oldWeight = NULL;

    if (CRM_Utils_Array::value('field_id', $params)) {
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $params['field_id'], 'weight', 'id');
    }
    $fieldValues = ['uf_group_id' => $params['group_id']];
    return CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_UFField', $oldWeight, $params['weight'], $fieldValues);
  }

  /**
   * Enable or disable profile fields associated with a specific custom field.
   *
   * @param int $customFieldId custom field ID
   * @param bool $is_active TRUE to enable, FALSE to disable
   *
   * @return void
   */
  public static function setUFField($customFieldId, $is_active) {
    //find the profile id given custom field
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_name = "custom_" . $customFieldId;

    $ufField->find();
    while ($ufField->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::setIsActive($ufField->id, $is_active);
    }
  }

  /**
   * Copy all profile fields from one group to another.
   *
   * @param int $old_id source profile group ID
   * @param int $new_id target profile group ID
   *
   * @return void
   */
  public static function copy($old_id, $new_id) {
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->uf_group_id = $old_id;
    $ufField->find();
    while ($ufField->fetch()) {
      //copy the field records as it is on new ufgroup id
      $ufField->uf_group_id = $new_id;
      $ufField->id = NULL;
      $ufField->save();
    }
  }

  /**
   * Delete profile fields associated with a specific custom field.
   *
   * @param int $customFieldId custom field ID
   *
   * @return void
   */
  public static function delUFField($customFieldId) {
    //find the profile id given custom field id
    $ufField = new CRM_Core_DAO_UFField();
    $ufField->field_name = "custom_" . $customFieldId;

    $ufField->find();
    while ($ufField->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::del($ufField->id);
    }
  }

  /**
   * Enable or disable profile fields associated with a specific custom group.
   *
   * @param int $customGroupId custom group ID
   * @param bool $is_active TRUE to enable, FALSE to disable
   *
   * @return void
   */
  public static function setUFFieldStatus($customGroupId, $is_active) {
    //find the profile id given custom group id
    $queryString = "SELECT civicrm_custom_field.id as custom_field_id
                        FROM   civicrm_custom_field, civicrm_custom_group
                        WHERE  civicrm_custom_field.custom_group_id = civicrm_custom_group.id
                          AND  civicrm_custom_group.id = %1";
    $p = [1 => [$customGroupId, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($queryString, $p);

    while ($dao->fetch()) {
      //enable/ disable profile
      CRM_Core_BAO_UFField::setUFField($dao->custom_field_id, $is_active);
    }
  }

  /**
   * Check the status of the underlying custom field used in a profile field.
   *
   * @param int $UFFieldId profile field ID
   *
   * @return bool TRUE if active or not a custom field, FALSE if custom field is disabled
   */
  public static function checkUFStatus($UFFieldId) {
    $fieldName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFField', $UFFieldId, 'field_name');
    // return if field is not a custom field

    if (!$customFieldId = CRM_Core_BAO_CustomField::getKeyID($fieldName)) {
      return TRUE;
    }

    $customField = new CRM_Core_DAO_CustomField();
    $customField->id = $customFieldId;
    // if uf field is custom field
    if ($customField->find(TRUE)) {
      if (!$customField->is_active) {
        return FALSE;
      }
      else {
        return TRUE;
      }
    }
  }
  /**
   * Determine if a profile group uses both contact and activity fields in a way
   * that is supported for mixed profiles.
   *
   * @param int $ufGroupId profile group ID
   *
   * @return bool TRUE if it's a supported contact+activity profile, FALSE otherwise
   */
  public static function checkContactActivityProfileType($ufGroupId) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = [];
    if ($ufGroup->group_type) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroup->group_type);
      $profileTypes = explode(',', $typeParts[0]);
    }

    if (empty($profileTypes)) {
      return FALSE;
    }
    $components = ['Contribution', 'Participant', 'Membership'];
    if (!in_array('Activity', $profileTypes)) {
      return FALSE;
    }
    elseif (count($profileTypes) == 1) {
      return FALSE;
    }

    if ($index = array_search('Contact', $profileTypes)) {
      unset($profileTypes[$index]);
      if (count($profileTypes) == 1) {
        return TRUE;
      }
    }

    $contactTypes = ['Individual', 'Household', 'Organization'];
    $subTypes = CRM_Contact_BAO_ContactType::subTypes();

    $profileTypeComponent = array_intersect($components, $profileTypes);
    if (!empty($profileTypeComponent) ||
      count(array_intersect($contactTypes, $profileTypes)) > 1 ||
      count(array_intersect($subTypes, $profileTypes)) > 1
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check if a profile group uses specific required and/or optional profile types.
   *
   * @param int $ufGroupId profile group ID
   * @param string[] $required array of types those are required
   * @param string[]|null $optional array of types those are optional
   *
   * @return bool TRUE if valid, FALSE otherwise
   */
  public static function checkValidProfileType($ufGroupId, $required, $optional = NULL) {
    if (!is_array($required) || empty($required)) {
      return;
    }

    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = [];
    if ($ufGroup->group_type) {
      $typeParts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $ufGroup->group_type);
      $profileTypes = explode(',', $typeParts[0]);
    }

    if (empty($profileTypes)) {
      return FALSE;
    }

    $valid = TRUE;
    foreach ($required as $key => $val) {
      if (!in_array($val, $profileTypes)) {
        $valid = FALSE;
        break;
      }
    }

    if ($valid && is_array($optional)) {
      foreach ($optional as $key => $val) {
        if (in_array($val, $profileTypes)) {
          $valid = TRUE;
          break;
        }
      }
    }

    return $valid;
  }

  /**
   * Check if a profile contains mixed field types (e.g., multiple contact types or components).
   *
   * @param int $ufGroupId profile group ID
   *
   * @return bool TRUE if it is a mixed profile, FALSE if pure
   */
  public static function checkProfileType($ufGroupId) {
    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->find(TRUE);

    $profileTypes = [];
    if ($ufGroup->group_type) {
      $profileTypes = explode(',', $ufGroup->group_type);
    }

    //early return if new profile.
    if (empty($profileTypes)) {
      return FALSE;
    }

    //we need to unset Contact
    if (count($profileTypes) > 1) {
      $index = array_search('Contact', $profileTypes);
      if ($index !== FALSE) {
        unset($profileTypes[$index]);
      }
    }

    // suppress any subtypes if present

    CRM_Contact_BAO_ContactType::suppressSubTypes($profileTypes);

    $contactTypes = ['Contact', 'Individual', 'Household', 'Organization'];
    $components = ['Contribution', 'Participant', 'Membership'];
    $fields = [];

    // check for mix profile condition
    if (count($profileTypes) > 1) {
      //check the there are any components include in profile
      foreach ($components as $value) {
        if (in_array($value, $profileTypes)) {
          return TRUE;
        }
      }
      //check if there are more than one contact types included in profile
      if (count($profileTypes) > 1) {
        return TRUE;
      }
    }
    elseif (count($profileTypes) == 1) {
      // note for subtype case count would be zero
      $profileTypes = array_values($profileTypes);
      if (!in_array($profileTypes[0], $contactTypes)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the primary entity type for a profile group.
   *
   * @param int $ufGroupId profile group ID
   * @param bool $returnMixType whether to return the specific type for mixed profiles
   * @param bool $onlyPure whether to return only if the profile is pure
   * @param bool $skipComponentType whether to ignore component types
   *
   * @return string|null the profile type (e.g., 'Individual', 'Activity', 'Mixed')
   */
  public static function getProfileType($ufGroupId, $returnMixType = TRUE, $onlyPure = FALSE, $skipComponentType = FALSE) {
    // profile types
    $contactTypes = ['Contact', 'Individual', 'Household', 'Organization'];

    $subTypes = CRM_Contact_BAO_ContactType::subTypes();

    $components = ['Contribution', 'Participant', 'Membership'];

    $ufGroup = new CRM_Core_DAO_UFGroup();
    $ufGroup->id = $ufGroupId;
    $ufGroup->is_active = 1;

    $ufGroup->find(TRUE);

    $profileTypes = [];
    if ($ufGroup->group_type) {
      $profileTypes = explode(',', $ufGroup->group_type);
    }

    if ($onlyPure) {
      if (count($profileTypes) == 1) {
        return $profileTypes[0];
      }
      else {
        return NULL;
      }
    }

    //we need to unset Contact
    if (count($profileTypes) > 1) {
      $index = array_search('Contact', $profileTypes);
      if ($index !== FALSE) {
        unset($profileTypes[$index]);
      }
    }

    $profileType = $mixProfileType = NULL;

    // this case handles pure profile
    if (count($profileTypes) == 1) {
      $profileType = array_pop($profileTypes);
    }
    else {
      //check the there are any components include in profile
      $componentCount = [];
      foreach ($components as $value) {
        if (in_array($value, $profileTypes)) {
          $componentCount[] = $value;
        }
      }

      //check contact type included in profile
      $contactTypeCount = [];
      foreach ($contactTypes as $value) {
        if (in_array($value, $profileTypes)) {
          $contactTypeCount[] = $value;
        }
      }
      // subtype counter
      $subTypeCount = [];
      foreach ($subTypes as $value) {
        if (in_array($value, $profileTypes)) {
          $subTypeCount[] = $value;
        }
      }
      if (!$skipComponentType && count($componentCount) == 1) {
        $profileType = $componentCount[0];
      }
      elseif (count($componentCount) > 1) {
        $mixProfileType = $componentCount[1];
      }
      elseif (count($subTypeCount) == 1) {
        $profileType = $subTypeCount[0];
      }
      elseif (count($contactTypeCount) == 1) {
        $profileType = $contactTypeCount[0];
      }
      elseif (count($subTypeCount) > 1) {
        // this is mix subtype profiles
        $mixProfileType = $subTypeCount[1];
      }
      elseif (count($contactTypeCount) > 1) {
        // this is mix contact profiles
        $mixProfileType = $contactTypeCount[1];
      }
    }

    if ($mixProfileType) {
      if ($returnMixType) {
        return $mixProfileType;
      }
      else {
        return 'Mixed';
      }
    }
    else {
      return $profileType;
    }
  }

  /**
   * Check if any profile group configured for User Registration is mixed.
   *
   * @param string $ctype contact type to check
   *
   * @return bool TRUE if no mixed registration profiles found, FALSE otherwise
   */
  public static function checkProfileGroupType($ctype) {
    $ufGroup = new CRM_Core_DAO_UFGroup();

    $query = "
SELECT ufg.id as id
  FROM civicrm_uf_group as ufg, civicrm_uf_join as ufj
 WHERE ufg.id = ufj.uf_group_id
   AND ufj.module = 'User Registration'
   AND ufg.is_active = 1 ";

    $ufGroup = &CRM_Core_DAO::executeQuery($query);

    $fields = [];
    $validProfiles = ['Individual', 'Organization', 'Household', 'Contribution'];
    while ($ufGroup->fetch()) {
      $profileType = self::getProfileType($ufGroup->id);
      if (in_array($profileType, $validProfiles)) {
        continue;
      }
      elseif ($profileType) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Check if a profile has any fields marked as searchable or in selector.
   *
   * @param int $profileID profile group ID
   *
   * @return bool TRUE if searchable/selector fields exist, FALSE otherwise
   */
  public static function checkSearchableORInSelector($profileID) {
    $result = FALSE;
    if (!$profileID) {
      return $result;
    }

    $query = "
SELECT  id 
  From  civicrm_uf_field 
 WHERE  (in_selector = 1 OR is_searchable = 1)
   AND  uf_group_id = {$profileID}";

    $ufFields = CRM_Core_DAO::executeQuery($query);
    while ($ufFields->fetch()) {
      $result = TRUE;
      break;
    }

    return $result;
  }

  /**
   * Reset the 'in_selector' and 'is_searchable' flags for all fields in a profile.
   *
   * @param int $profileID profile group ID
   *
   * @return void
   */
  public static function resetInSelectorANDSearchable($profileID) {
    if (!$profileID) {
      return;
    }
    $query = "UPDATE civicrm_uf_field SET in_selector = 0, is_searchable = 0 WHERE  uf_group_id = {$profileID}";
    CRM_Core_DAO::executeQuery($query);
  }
}
