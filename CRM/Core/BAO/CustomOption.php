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
 * Business objects for managing custom data options.
 *
 */
class CRM_Core_BAO_CustomOption {
  public const VALUE_SEPERATOR = "";

  /**
   * Retrieve a custom option value based on the provided parameters.
   *
   * This is the inverse function of create. It also stores all the retrieved
   * values in the defaults array.
   *
   * @param array $params associative array of name/value pairs to match
   * @param array $defaults associative array to hold the flattened values
   *
   * @return CRM_Core_DAO_OptionValue|null matching DAO object
   */
  public static function retrieve(&$params, &$defaults) {

    $customOption = new CRM_Core_DAO_OptionValue();
    $customOption->copyValues($params);
    if ($customOption->find(TRUE)) {
      CRM_Core_DAO::storeValues($customOption, $defaults);
      return $customOption;
    }
    return NULL;
  }

  /**
   * Returns all active options ordered by weight for a given custom field.
   *
   * @param int $fieldID custom field ID whose options are needed
   * @param bool $inactiveNeeded whether to include inactive options
   *
   * @return array associative array of options for the field
   */
  public static function getCustomOption(
    $fieldID,
    $inactiveNeeded = FALSE
  ) {
    $options = [];
    if (!$fieldID) {
      return $options;
    }

    // get the option group id
    $optionGroupID = CRM_Core_DAO::getFieldValue(
      'CRM_Core_DAO_CustomField',
      $fieldID,
      'option_group_id'
    );
    if (!$optionGroupID) {
      return $options;
    }

    $dao = new CRM_Core_DAO_OptionValue();
    $dao->option_group_id = $optionGroupID;
    if (!$inactiveNeeded) {
      $dao->is_active = 1;
    }
    $dao->orderBy('weight ASC, label ASC');
    $dao->find();

    while ($dao->fetch()) {
      $options[$dao->id] = [];
      $options[$dao->id]['id'] = $dao->id;
      $options[$dao->id]['label'] = $dao->label;
      $options[$dao->id]['value'] = $dao->value;
    }

    CRM_Utils_Hook::customFieldOptions($fieldID, $options, TRUE);

    return $options;
  }

  /**
   * Get the display label for a custom field option.
   *
   * @param int $fieldId custom field ID
   * @param mixed $value value of the option
   * @param string|null $htmlType HTML type of the field (retrieved from DB if NULL)
   * @param string|null $dataType data type of the field (retrieved from DB if NULL)
   *
   * @return string|null the formatted display label
   */
  public static function getOptionLabel($fieldId, $value, $htmlType = NULL, $dataType = NULL) {
    if (!$fieldId) {
      return NULL;
    }

    if (!$htmlType || !$dataType) {
      $sql = "
SELECT html_type, data_type
FROM   civicrm_custom_field
WHERE  id = %1
";
      $params = [1 => [$fieldId, 'Integer']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      if ($dao->fetch()) {
        $htmlType = $dao->html_type;
        $dataType = $dao->data_type;
      }
      else {
        CRM_Core_Error::fatal();
      }
    }

    $options = NULL;
    switch ($htmlType) {
      case 'CheckBox':
      case 'Multi-Select':
      case 'AdvMulti-Select':
      case 'Select':
      case 'Radio':
      case 'Autocomplete-Select':
        if (!in_array($dataType, ['Boolean', 'ContactReference'])) {
          $options = &self::valuesByID($fieldId);
        }
    }

    return CRM_Core_BAO_CustomField::getDisplayValueCommon(
      $value,
      $options,
      $htmlType,
      $dataType
    );
  }

  /**
   * Delete a custom option and update related custom values.
   *
   * This function removes the option value from the database and updates
   * any existing records that use this option to a default/empty value.
   *
   * @param int $optionId ID of the option value to delete
   *
   * @return void
   */
  public static function del($optionId) {
    // get the customFieldID
    $query = "
SELECT f.id as id, f.data_type as dataType
FROM   civicrm_option_value v,
       civicrm_option_group g,
       civicrm_custom_field f
WHERE  v.id    = %1
AND    g.id    = f.option_group_id
AND    g.id    = v.option_group_id";
    $params = [1 => [$optionId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      if (in_array(
        $dao->dataType,
        ['Int', 'Float', 'Money', 'Boolean']
      )) {
        $value = 0;
      }
      else {
        $value = '';
      }
      $params = ['optionId' => $optionId,
        'fieldId' => $dao->id,
        'value' => $value,
      ];
      // delete this value from the tables
      self::updateCustomValues($params);

      // also delete this option value
      $query = "
DELETE
FROM   civicrm_option_value
WHERE  id = %1";
      $params = [1 => [$optionId, 'Integer']];
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Update custom value tables after an option value has changed or been deleted.
   *
   * @param array $params associative array containing 'optionId', 'fieldId', and 'value'
   *
   * @return void
   */
  public static function updateCustomValues($params) {
    $optionDAO = new CRM_Core_DAO_OptionValue();
    $optionDAO->id = $params['optionId'];
    $optionDAO->find(TRUE);
    $oldValue = $optionDAO->value;

    // get the table, column, html_type and data type for this field
    $query = "
SELECT g.table_name  as tableName ,
       f.column_name as columnName,
       f.data_type   as dataType,
       f.html_type   as htmlType
FROM   civicrm_custom_group g,
       civicrm_custom_field f
WHERE  f.custom_group_id = g.id
  AND  f.id = %1";
    $queryParams = [1 => [$params['fieldId'], 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    if ($dao->fetch()) {
      if ($dao->dataType == 'Money') {

        $params['value'] = CRM_Utils_Rule::cleanMoney($params['value']);
      }
      switch ($dao->htmlType) {
        case 'Autocomplete-Select':
        case 'Select':
        case 'Radio':
          $query = "
UPDATE {$dao->tableName}
SET    {$dao->columnName} = %1
WHERE  {$dao->columnName} = %2";
          if ($dao->dataType == 'Auto-complete') {
            $dataType = "String";
          }
          else {
            $dataType = $dao->dataType;
          }
          $queryParams = [1 => [$params['value'],
              $dataType,
            ],
            2 => [$oldValue,
              $dataType,
            ],
          ];
          break;

        case 'AdvMulti-Select':
        case 'Multi-Select':
        case 'CheckBox':
          $oldString = CRM_Core_DAO::VALUE_SEPARATOR . $oldValue . CRM_Core_DAO::VALUE_SEPARATOR;
          $newString = CRM_Core_DAO::VALUE_SEPARATOR . $params['value'] . CRM_Core_DAO::VALUE_SEPARATOR;
          $query = "
UPDATE {$dao->tableName}
SET    {$dao->columnName} = REPLACE( {$dao->columnName}, %1, %2 )";
          $queryParams = [1 => [$oldString, 'String'],
            2 => [$newString, 'String'],
          ];
          break;

        default:
          CRM_Core_Error::fatal();
      }
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    }
  }

  /**
   * Get all option values for a custom field ID.
   *
   * @param int $customFieldID custom field ID
   * @param int|null $optionGroupID optional option group ID (retrieved from field ID if NULL)
   *
   * @return array associative array of (value => label)
   */
  public static function &valuesByID($customFieldID, $optionGroupID = NULL) {
    $options = [];
    if (!$optionGroupID) {
      $optionGroupID = CRM_Core_DAO::getFieldValue(
        'CRM_Core_DAO_CustomField',
        $customFieldID,
        'option_group_id'
      );
    }

    if (!empty($optionGroupID) && is_numeric($optionGroupID)) {
      $options = &CRM_Core_OptionGroup::valuesByID($optionGroupID);
    }

    CRM_Utils_Hook::customFieldOptions($customFieldID, $options, FALSE);

    return $options;
  }
}
