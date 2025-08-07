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
 * form to process actions on the field aspect of Custom
 */
class CRM_Custom_Form_Option extends CRM_Core_Form {

  public $_parent;
  /**
   * the custom field id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_fid;

  /**
   * the custom group id saved to the session for an update
   *
   * @var int
   * @access protected
   */
  protected $_gid;

  /**
   * The option group ID
   */
  protected $_optionGroupID = NULL;

  /**
   * The Option id, used when editing the Option
   *
   * @var int
   * @access protected
   */
  protected $_id;

  /**
   * Function to set variables up before form is built
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_fid = CRM_Utils_Request::retrieve('fid', 'Positive', $this);
    if ($this->_fid) {
      $params = [
        'id' => $this->_fid
      ];
      $field = [];
      CRM_Core_BAO_CustomField::retrieve($params, $field);
      if (!empty($field['attributes']) && strstr($field['attributes'], 'data-parent=')) {
        $matches = [];
        if (preg_match('/data-parent=(\d+)/i', $field['attributes'], $matches)) {
          $parentFieldId = (int)$matches[1];
          $params = ['id' => $parentFieldId];
          CRM_Core_BAO_CustomField::retrieve($params, $field);
          if ($field['id'] == $parentFieldId && $field['option_group_id']) {
            $optionValues = CRM_Core_OptionGroup::valuesByID($field['option_group_id']);
            $this->_parent['id'] = $parentFieldId;
            $this->_parent['options'] = $optionValues;
          }
        }
      }
    }

    $this->_gid = CRM_Utils_Request::retrieve('gid', 'Positive', $this);
    if (!isset($this->_gid) && $this->_fid) {
      $this->_gid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
        $this->_fid,
        'custom_group_id'
      );
    }
    if ($this->_fid) {
      $this->_optionGroupID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
        $this->_fid,
        'option_group_id'
      );
    }

    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this);
  }

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @param null
   *
   * @return array   array of default values
   * @access public
   */
  function setDefaultValues() {
    $defaults = $fieldDefaults = [];
    if (isset($this->_id)) {
      $params = ['id' => $this->_id];
      CRM_Core_BAO_CustomOption::retrieve($params, $defaults);

      $paramsField = ['id' => $this->_fid];
      CRM_Core_BAO_CustomField::retrieve($paramsField, $fieldDefaults);

      if ($fieldDefaults['html_type'] == 'CheckBox'
        || $fieldDefaults['html_type'] == 'Multi-Select'
        || $fieldDefaults['html_type'] == 'AdvMulti-Select'
      ) {
        $defaultCheckValues = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
          substr($fieldDefaults['default_value'], 1, -1)
        );
        if (in_array($defaults['value'], $defaultCheckValues)) {
          $defaults['default_value'] = 1;
        }
      }
      else {
        if (CRM_Utils_Array::value('default_value', $fieldDefaults) == CRM_Utils_Array::value('value', $defaults)) {
          $defaults['default_value'] = 1;
        }
      }
    }
    else {
      $defaults['is_active'] = 1;
    }


    if ($this->_action & CRM_Core_Action::ADD) {
      $fieldValues = ['option_group_id' => $this->_optionGroupID];
      $defaults['weight'] = CRM_Utils_Weight::getDefaultWeight('CRM_Core_DAO_OptionValue', $fieldValues);
    }

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @param null
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    if ($this->_action == CRM_Core_Action::DELETE) {
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Delete'),
            'isDefault' => TRUE,
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );
    }
    else {
      // lets trim all the whitespace
      $this->applyFilter('__ALL__', 'trim');

      // hidden Option Id for validation use
      $this->add('hidden', 'optionId', $this->_id);

      //hidden field ID for validation use
      $this->add('hidden', 'fieldId', $this->_fid);

      // label
      $this->add('text', 'label', ts('Option Label'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'label'), TRUE);

      $this->add('text', 'value', ts('Option Value'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'value'), TRUE);

      // weight
      $this->add('text', 'weight', ts('Order'), CRM_Core_DAO::getAttribute('CRM_Core_DAO_OptionValue', 'weight'), TRUE);

      if (!empty($this->_parent['id']) && is_array($this->_parent['options'])) {
        $options = ['' => ts('- select Parent -')] + $this->_parent['options'];
        $this->addSelect('grouping', ts('Parent'), $options);
      }
      $this->addRule('weight', ts('is a numeric field'), 'numeric');

      // is active ?
      $this->add('checkbox', 'is_active', ts('Active?'));

      // Set the default value for Custom Field
      $this->add('checkbox', 'default_value', ts('Default'));

      // add a custom form rule
      $this->addFormRule(['CRM_Custom_Form_Option', 'formRule'], $this);

      // add buttons
      $this->addButtons([
          ['type' => 'next',
            'name' => ts('Save'),
            'isDefault' => TRUE,
          ],
          ['type' => 'next',
            'name' => ts('Save and New'),
            'subName' => 'new',
          ],
          ['type' => 'cancel',
            'name' => ts('Cancel'),
          ],
        ]
      );


      // if view mode pls freeze it with the done button.
      if ($this->_action & CRM_Core_Action::VIEW) {
        $this->freeze();
        $url = CRM_Utils_System::url('civicrm/admin/custom/group/field/option',
          'reset=1&action=browse&fid=' . $this->_fid . '&gid=' . $this->_gid,
          TRUE, NULL, FALSE
        );
        $this->addElement('button',
          'done',
          ts('Done'),
          ['onclick' => "location.href='$url'", 'class' => 'form-submit']
        );
      }
    }
    $this->assign('id', $this->_id);
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields, $files, $form) {
    $optionLabel = CRM_Utils_Type::escape($fields['label'], 'String');
    $optionValue = CRM_Utils_Type::escape($fields['value'], 'String');
    $fieldId = $form->_fid;
    $optionGroupId = $form->_optionGroupID;

    $temp = [];
    if (empty($form->_id)) {
      $query = "
SELECT count(*) 
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND label = %2";
      $params = [1 => [$optionGroupId, 'Integer'],
        2 => [$optionLabel, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['label'] = ts('There is an entry with the same label.');
      }

      $query = "
SELECT count(*) 
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND value = %2";
      $params = [1 => [$optionGroupId, 'Integer'],
        2 => [$optionValue, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['value'] = ts('There is an entry with the same value.');
      }
    }
    else {
      //capture duplicate entries while updating Custom Options
      $optionId = CRM_Utils_Type::escape($fields['optionId'], 'Integer');

      //check label duplicates within a custom field
      $query = "
SELECT count(*) 
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND id != %2
   AND label = %3";
      $params = [1 => [$optionGroupId, 'Integer'],
        2 => [$optionId, 'Integer'],
        3 => [$optionLabel, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['label'] = ts('There is an entry with the same label.');
      }

      //check value duplicates within a custom field
      $query = "
SELECT count(*) 
  FROM civicrm_option_value
 WHERE option_group_id = %1
   AND id != %2
   AND value = %3";
      $params = [1 => [$optionGroupId, 'Integer'],
        2 => [$optionId, 'Integer'],
        3 => [$optionValue, 'String'],
      ];
      if (CRM_Core_DAO::singleValueQuery($query, $params) > 0) {
        $errors['value'] = ts('There is an entry with the same value.');
      }
    }

    $query = "
SELECT data_type 
  FROM civicrm_custom_field
 WHERE id = %1";
    $params = [1 => [$fieldId, 'Integer']];
    $dao = &CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->fetch()) {
      switch ($dao->data_type) {
        case 'Int':
          if (!CRM_Utils_Rule::integer($fields["value"])) {
            $errors['value'] = ts('Please enter a valid integer value.');
          }
          break;

        case 'Float':
          //     case 'Money':
          if (!CRM_Utils_Rule::numeric($fields["value"])) {
            $errors['value'] = ts('Please enter a valid number value.');
          }
          break;

        case 'Money':
          if (!CRM_Utils_Rule::money($fields["value"])) {
            $errors['value'] = ts('Please enter a valid value.');
          }
          break;

        case 'Date':
          if (!CRM_Utils_Rule::date($fields["value"])) {
            $errors['value'] = ts('Please enter a valid date using YYYY-MM-DD format. Example: 2004-12-31.');
          }
          break;

        case 'Boolean':
          if (!CRM_Utils_Rule::integer($fields["value"]) &&
            ($fields["value"] != '1' || $fields["value"] != '0')
          ) {
            $errors['value'] = ts('Please enter 1 or 0 as value.');
          }
          break;

        case 'Country':
          if (!empty($fields["value"])) {
            $params = [1 => [$fields['value'], 'String']];
            $query = "SELECT count(*) FROM civicrm_country WHERE name = %1 OR iso_code = %1";
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['value'] = ts('Invalid default value for country.');
            }
          }
          break;

        case 'StateProvince':
          if (!empty($fields["value"])) {
            $params = [1 => [$fields['value'], 'String']];
            $query = "
SELECT count(*) 
  FROM civicrm_state_province
 WHERE name = %1
    OR abbreviation = %1";
            if (CRM_Core_DAO::singleValueQuery($query, $params) <= 0) {
              $errors['value'] = ts('The invalid value for State/Province data type');
            }
          }
          break;
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form
   *
   * @param null
   *
   * @return void
   * @access public
   */

  public function postProcess() {
    // store the submitted values in an array
    $params = $this->controller->exportValues('Option');

    // set values for custom field properties and save


    $customOption = new CRM_Core_DAO_OptionValue();
    $customOption->label = $params['label'];
    $customOption->name = CRM_Utils_String::titleToVar($params['label']);
    $customOption->weight = $params['weight'];
    $customOption->value = $params['value'];
    if ($params['grouping']) {
      $customOption->grouping = $params['grouping'];
    }
    $customOption->is_active = CRM_Utils_Array::value('is_active', $params, FALSE);

    if ($this->_action == CRM_Core_Action::DELETE) {
      $fieldValues = ['option_group_id' => $this->_optionGroupID];
      $wt = CRM_Utils_Weight::delWeight('CRM_Core_DAO_OptionValue', $this->_id, $fieldValues);
      CRM_Core_BAO_CustomOption::del($this->_id);
      CRM_Core_Session::setStatus(ts('Your multiple choice option has been deleted', [1 => $customOption->label]));
      return;
    }

    if ($this->_id) {
      $customOption->id = $this->_id;
      CRM_Core_BAO_CustomOption::updateCustomValues($params);
      $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', $this->_id, 'weight', 'id');
    }

    $fieldValues = ['option_group_id' => $this->_optionGroupID];
    $customOption->weight = CRM_Utils_Weight::updateOtherWeights('CRM_Core_DAO_OptionValue', $oldWeight, $params['weight'], $fieldValues);

    $customOption->option_group_id = $this->_optionGroupID;

    $customField = new CRM_Core_DAO_CustomField();
    $customField->id = $this->_fid;
    if ($customField->find(TRUE) &&
      ($customField->html_type == 'CheckBox' ||
        $customField->html_type == 'AdvMulti-Select' ||
        $customField->html_type == 'Multi-Select'
      )
    ) {
      $defVal = explode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR,
        substr($customField->default_value, 1, -1)
      );
      if (CRM_Utils_Array::value('default_value', $params)) {
        if (!in_array($customOption->value, $defVal)) {
          if (empty($defVal[0])) {
            $defVal = [$customOption->value];
          }
          else {
            $defVal[] = $customOption->value;
          }
          $customField->default_value = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $defVal) . CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
          $customField->save();
        }
      }
      elseif (in_array($customOption->value, $defVal)) {
        $tempVal = [];
        foreach ($defVal as $v) {
          if ($v != $customOption->value) {
            $tempVal[] = $v;
          }
        }

        $customField->default_value = CRM_Core_BAO_CustomOption::VALUE_SEPERATOR . CRM_Utils_Array::implode(CRM_Core_BAO_CustomOption::VALUE_SEPERATOR, $tempVal) . CRM_Core_BAO_CustomOption::VALUE_SEPERATOR;
        $customField->save();
      }
    }
    else {
      switch ($customField->data_type) {
        case 'Money':

          $customOption->value = CRM_Utils_Rule::cleanMoney($customOption->value);
          break;

        case 'Int':
          $customOption->value = intval($customOption->value);
          break;

        case 'Float':
          $customOption->value = floatval($customOption->value);
          break;
      }

      if (CRM_Utils_Array::value('default_value', $params)) {
        $customField->default_value = $customOption->value;
        $customField->save();
      }
      elseif ($customField->find(TRUE) && $customField->default_value == $customOption->value) {
        // this is the case where this option is the current default value and we have been reset
        $customField->default_value = 'null';
        $customField->save();
      }
    }

    $customOption->save();

    CRM_Core_Session::setStatus(ts('Your multiple choice option \'%1\' has been saved', [1 => $customOption->label]));
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();
    if ($buttonName == $this->getButtonName('next', 'new')) {
      CRM_Core_Session::setStatus(ts(' You can add another option.'));
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/admin/custom/group/field/option',
          'reset=1&action=add&fid=' . $this->_fid . '&gid=' . $this->_gid
        ));
    }
  }
}

