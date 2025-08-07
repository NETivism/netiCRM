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
 * Business objects for managing price fields.
 *
 */
class CRM_Price_BAO_Field extends CRM_Price_DAO_Field {

  protected $_options;

  /**
   * takes an associative array and creates a price field object
   *
   * the function extract all the params it needs to initialize the create a
   * price field object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array  $params    (reference ) an assoc array of name/value pairs
   * @param array  $ids       the array that holds all the db ids
   *
   * @return object CRM_Price_BAO_Field object
   * @access public
   * @static
   */
  static function &add(&$params, $ids) {
    $priceFieldBAO = new CRM_Price_BAO_Field();

    $priceFieldBAO->copyValues($params);

    if ($id = CRM_Utils_Array::value('id', $ids)) {
      $priceFieldBAO->id = $id;
    }

    $priceFieldBAO->save();
    return $priceFieldBAO;
  }

  /**
   * takes an associative array and creates a price field object
   *
   * This function is invoked from within the web form layer and also from the api layer
   *
   * @param array $params (reference) an assoc array of name/value pairs
   *
   * @return object CRM_Price_DAO_Field object
   * @access public
   * @static
   */
  static function create(&$params, $ids) {



    $transaction = new CRM_Core_Transaction();

    $priceField = &self::add($params, $ids);

    if (is_a($priceField, 'CRM_Core_Error')) {
      $transaction->rollback();
      return $priceField;
    }

    $options = $optionsIds = [];

    $maxIndex = CRM_Price_Form_Field::NUM_OPTION;

    if ($priceField->html_type == 'Text') {
      $maxIndex = 1;


      $fieldValue = new CRM_Price_DAO_FieldValue();
      $fieldValue->price_field_id = $priceField->id;

      // update previous field values( if any )
      if ($fieldValue->find(TRUE)) {
        $optionsIds['id'] = $fieldValue->id;
      }
    }
    $defaultArray = [];
    if ($params['html_type'] == 'CheckBox' && isset($params['default_checkbox_option'])) {
      $tempArray = array_keys($params['default_checkbox_option']);
      foreach ($tempArray as $v) {
        if ($params['option_amount'][$v]) {
          $defaultArray[$v] = 1;
        }
      }
    }
    else {
      if (CRM_Utils_Array::value('default_option', $params)
        && isset($params['option_amount'][$params['default_option']])
      ) {
        $defaultArray[$params['default_option']] = 1;
      }
    }
    for ($index = 1; $index <= $maxIndex; $index++) {

      if (CRM_Utils_Array::value($index, $params['option_label']) &&
        !CRM_Utils_System::isNull($params['option_amount'][$index])
      ) {
        $options = [
          'price_field_id' => $priceField->id,
          'label' => trim($params['option_label'][$index]),
          'name' => CRM_Utils_String::munge($params['option_label'][$index], '_', 64),
          'amount' => CRM_Utils_Rule::cleanMoney(trim($params['option_amount'][$index])),
          'count' => CRM_Utils_Array::value($index, $params['option_count'], NULL),
          'max_value' => CRM_Utils_Array::value($index, $params['option_max_value'], NULL),
          'description' => CRM_Utils_Array::value($index, $params['option_description'], NULL),
          'weight' => $params['option_weight'][$index],
          'is_member' => $params['option_member'][$index],
          'is_active' => 1,
          'is_default' => CRM_Utils_Array::value($index, $defaultArray),
        ];
        CRM_Price_BAO_FieldValue::add($options, $optionsIds);
      }
    }
    if (CRM_Utils_Array::value('custom', $params) && is_array($params['custom'])) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_price_field', $priceField->id);
    }

    $transaction->commit();
    return $priceField;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Price_DAO_Field object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_Field', $params, $defaults);
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id         Id of the database record
   * @param boolean  $is_active  Value we want to set the is_active field
   *
   * @return   Object            DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_Field', $id, 'is_active', $is_active);
  }

  /**
   * Get the field title.
   *
   * @param int $id id of field.
   *
   * @return string name
   *
   * @access public
   * @static
   *
   */
  public static function getTitle($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Field', $id, 'label');
  }

  /**
   * This function for building custom fields
   *
   * @param object  $qf             form object (reference)
   * @param string  $elementName    name of the custom field
   * @param boolean $inactiveNeeded
   * @param boolean $useRequired    true if required else false
   * @param boolean $search         true if used for search else false
   * @param string  $label          label for custom field
   *
   * @access public
   * @static
   */
  public static function addQuickFormElement(&$qf,
    $elementName,
    $fieldId,
    $inactiveNeeded,
    $useRequired = TRUE,
    $label = NULL,
    $fieldOptions = NULL,
    $freezeOptions = []
  ) {

    $field = new CRM_Price_DAO_Field();
    $field->id = $fieldId;
    if (!$field->find(TRUE)) {
      /* FIXME: failure! */

      return NULL;
    }

    if($qf instanceof CRM_Event_Form_Registration_Register || $qf instanceof CRM_Contribute_Form_Contribution_Main || $qf instanceof CRM_Event_Form_Registration_AdditionalParticipant){
      if(!empty($field->active_on)){
        if(time() < strtotime($field->active_on)){
          return NULL;
        }
      }
      if(!empty($field->expire_on)){
        if(time() > strtotime($field->expire_on)){
          return NULL;
        }
      }
    }

    $config = CRM_Core_Config::singleton();
    $qf->assign('currencySymbol', CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Currency', $config->defaultCurrency, 'symbol', 'name'));
    if (!isset($label)) {
      $label = $field->label;
    }

    if (isset($qf->_online) && $qf->_online) {
      $useRequired = FALSE;
    }

    $customOption = $fieldOptions;
    if (!is_array($customOption)) {
      $customOption = CRM_Price_BAO_Field::getOptions($field->id, $inactiveNeeded);
    }

    // validate member related fields
    static $optionMemberJson;
    $optionMember = [];
    $backendForm = ['CRM_Event_Form_Participant', 'CRM_Contribute_Form_Contribution'];
    $formClass = get_class($qf);
    if (!in_array($formClass, $backendForm)) {
      foreach ($customOption as $optId => $opt) {
        if ($opt['is_member']) {
          $optionMember[$optId] = $opt;
        }
      }
    }

    $isMember = FALSE;
    $currentContactId = FALSE;
    if(!empty($optionMember)) {
      if( method_exists($qf, 'getContactID')) {
        $currentContactId = $qf->getContactID();
      }
      else{
        $session = CRM_Core_Session::singleton();
        $currentContactId = $session->get('userID');
      }
      if ($currentContactId) {
        $membershipTypes = CRM_Member_PseudoConstant::membershipType();
        foreach ($membershipTypes as $membershipTypeId => $mtype) {
          $currentMembership = CRM_Member_BAO_Membership::getContactMembership($currentContactId, $membershipTypeId, $is_test = 0);
          if( !empty($currentMembership) && $currentMembership['is_current_member']) {
            $isMember = TRUE;
            break;
          }
        }
      }
    }
    //use value field.
    $valueFieldName = 'amount';
    $seperator = '|';
    $disabledOptions = [];

    if (!empty($optionMember) && !$isMember) {
      foreach($optionMember as $optId => $opt){
        if ($field->is_display_amounts) {
          $opt['label'] .= ' - ';
          $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
        }
        $opt['label'] .= ' ('.ts('membership required').')'; 
        $id = strtolower($field->html_type).'_'.$opt['price_field_id'].'_'.$optId;
        $disabledOptions[$optId] = $customOption[$optId];
        $freezeOptions[] = $optId;
      }
    }
    $qf->_priceSet['fields'][$fieldId]['disabled_options'] = $disabledOptions;

    switch ($field->html_type) {
      case 'Text':
        if (empty($customOption)) {
          return;
        }
        if($disabledOptions[$optId]) {
          return;
        }
        $optionKey = key($customOption);
        $count = CRM_Utils_Array::value('count', $customOption[$optionKey], '');
        $max_value = CRM_Utils_Array::value('max_value', $customOption[$optionKey], '');
        $priceVal = CRM_Utils_Array::implode($seperator, [$customOption[$optionKey][$valueFieldName], $count, $max_value]);

        //check for label.
        if (CRM_Utils_Array::value('label', $fieldOptions[$optionKey])) {
          $label = $fieldOptions[$optionKey]['label'];
        }

        if ($field->is_display_amounts) {
          $label .= ' - ';
          $label .= CRM_Utils_Money::format(CRM_Utils_Array::value($valueFieldName, $customOption[$optionKey]));
        }

        $attributes = [
          'size' => "4",
          'min' => 0,
          'step' => 1,
          'price' => json_encode([$optionKey, $priceVal]),
          'inputmode' => 'numeric',
          'placeholder' => ts('Please enter %1', [1 => ts('Quantity')]),
        ];
        $qf->addNumber($elementName, $label, $attributes,
          $useRequired && $field->is_required
        );
        $element = $qf->getElement($elementName);

        // CRM-6902
        if (in_array($optionKey, $freezeOptions)) {
          $element->freeze();
        }

        // integers will have numeric rule applied to them.
        $qf->addRule($elementName, ts('%1 must be an integer (whole number).', [1 => $label]), 'positiveInteger');
        break;

      case 'Radio':
        $choice = [];

        foreach ($customOption as $opId => $opt) {
          if ($field->is_display_amounts) {
            $opt['label'] .= ' - ';
            $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
          }
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal = CRM_Utils_Array::implode($seperator, [$opt[$valueFieldName], $count, $max_value]);

          $choice[$opId] = $qf->createElement('radio', NULL, '', $opt['label'], $opt['id'],
            ['price' => json_encode([$elementName, $priceVal])]
          );

          // only enable qty / participant selection when specify max value
          if (!empty($field->max_value) || $field->max_value == '0') {
            $attr = [
              'size' => "1",
              'min' => 1,
              'step' => 1,
              'price' => json_encode([$elementName, $opId]),
              'inputmode' => 'numeric',
              'placeholder' => ts('Quantity'),
            ];
            $qf->addNumber($elementName.'_'.$opId.'_count', ts('Amount'), $attr);
            $participantCount[$opId] = $qf->getElement($elementName.'_'.$opId.'_count');
          }
          else {
            $participantCount[$opId] = $qf->add('hidden', $elementName.'_'.$opId.'_count', 1);
          }

          // CRM-6902
          if (in_array($opId, $freezeOptions)) {
            $choice[$opId]->freeze();
            $participantCount[$opId]->freeze();
          }
        }

        if (!$field->is_required) {
          // add "none" option
          $choice[] = $qf->createElement('radio', NULL, '', ts('- none -'), '0',
            ['price' => json_encode([$elementName, "0"])]
          );
        }

        $element = &$qf->addGroup($choice, $elementName, $label);

        if ($useRequired && $field->is_required) {
          $qf->addRule($elementName, ts('%1 is a required field.', [1 => $label]), 'required');
        }
        break;

      case 'Select':
        $selectOption = $allowedOptions = $priceVal = [];

        foreach ($customOption as $opt) {
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal[$opt['id']] = CRM_Utils_Array::implode($seperator, [$opt[$valueFieldName], $count, $max_value]);

          if ($field->is_display_amounts) {
            $opt['label'] .= ' - ';
            $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
          }
          $selectOption[$opt['id']] = $opt['label'];

          if (in_array($opt['id'], $freezeOptions)) {
            unset($selectOption[$opt['id']]);
          }
          else {
            $allowedOptions[] = $opt['id'];
          }
        }
        $element = &$qf->add('select', $elementName, $label,
          ['' => ts('- select -')] + $selectOption,
          $useRequired && $field->is_required,
          ['price' => json_encode($priceVal)]
        );

        // CRM-6902
        $button = substr($qf->controller->getButtonName(), -4);
        if (!empty($freezeOptions) && $button != 'skip') {
          $qf->addRule($elementName, ts('Invalid value for field(s)'), 'regex', "/" . CRM_Utils_Array::implode('|', $allowedOptions) . "/");
        }
        break;

      case 'CheckBox':

        $check = [];
        foreach ($customOption as $opId => $opt) {
          $count = CRM_Utils_Array::value('count', $opt, '');
          $max_value = CRM_Utils_Array::value('max_value', $opt, '');
          $priceVal = CRM_Utils_Array::implode($seperator, [$opt[$valueFieldName], $count, $max_value]);

          if ($field->is_display_amounts) {
            $opt['label'] .= ' - ';
            $opt['label'] .= CRM_Utils_Money::format($opt[$valueFieldName]);
          }
          $check[$opId] = $qf->createElement('checkbox', $opt['id'], NULL, $opt['label'],
            ['price' => json_encode([$opt['id'], $priceVal])]
          );

          if (!empty($field->max_value) || $field->max_value == '0') {
            $attr = [
              'size' => "1",
              'min' => 1,
              'step' => 1,
              'price' => json_encode([$elementName, $opId]),
              'inputmode' => 'numeric',
              'placeholder' => ts('Quantity'),
            ];
            $qf->addNumber($elementName.'_'.$opId.'_count', ts('Amount'), $attr);
            $participantCount[$opId] = $qf->getElement($elementName.'_'.$opId.'_count');
          }
          else {
            $participantCount[$opId] = $qf->add('hidden', $elementName.'_'.$opId.'_count', 1);
          }

          // CRM-6902
          if (in_array($opId, $freezeOptions)) {
            $check[$opId]->freeze();
            $participantCount[$opId]->freeze();
          }
        }
        $element = &$qf->addGroup($check, $elementName, $label);
        if ($useRequired && $field->is_required) {
          $qf->addRule($elementName, ts('%1 is a required field.', [1 => $label]), 'required');
        }
        break;
    }
    if (isset($qf->_online) && $qf->_online) {
      $element->freeze();
    }
    return $element;
  }

  /**
   * Retrieve a list of options for the specified field
   *
   * @param int $fieldId price field ID
   * @param bool $inactiveNeeded include inactive options
   * @param bool $reset ignore stored values\
   *
   * @return array array of options
   */
  public static function getOptions($fieldId, $inactiveNeeded = FALSE, $reset = FALSE) {
    static $options = [];

    if ($reset || empty($options[$fieldId])) {
      $values = [];

      CRM_Price_BAO_FieldValue::getValues($fieldId, $values, 'weight', !$inactiveNeeded);
      $options[$fieldId] = $values;
    }

    return $options[$fieldId];
  }

  public static function getOptionId($optionLabel, $fid) {
    if (!$optionLabel || !$fid) {
      return;
    }

    $optionGroupName = "civicrm_price_field.amount.{$fid}";

    $query = "
SELECT 
        option_value.id as id
FROM 
        civicrm_option_value option_value,
        civicrm_option_group option_group
WHERE 
        option_group.name  = %1
    AND option_group.id    = option_value.option_group_id
    AND option_value.label = %2";

    $dao = &CRM_Core_DAO::executeQuery($query, [1 => [$optionGroupName, 'String'], 2 => [$optionLabel, 'String']]);

    while ($dao->fetch()) {
      return $dao->id;
    }
  }

  /**
   * Delete the price set field.
   *
   * @param   int   $id    Field Id
   *
   * @return  boolean
   *
   * @access public
   * @static
   *
   */
  public static function deleteField($id) {
    $field = new CRM_Price_DAO_Field();
    $field->id = $id;

    if ($field->find(TRUE)) {
      // delete the options for this field

      CRM_Price_BAO_FieldValue::deleteValues($id);

      // reorder the weight before delete
      $fieldValues = ['price_set_id' => $field->price_set_id];


      CRM_Utils_Weight::delWeight('CRM_Price_DAO_Field', $field->id, $fieldValues);

      // now delete the field
      return $field->delete();
    }

    return NULL;
  }

  static function &htmlTypes() {
    static $htmlTypes = NULL;
    if (!$htmlTypes) {
      $htmlTypes = [
        'Text' => ts('Text / Numeric Quantity'),
        'Select' => ts('Select'),
        'Radio' => ts('Radio'),
        'CheckBox' => ts('CheckBox'),
      ];
    }
    return $htmlTypes;
  }

  /**
   * Validate the priceset
   *
   * @param int $priceSetId, array $fields
   *
   * retrun the error string
   *
   * @access public
   * @static
   *
   */

  public static function priceSetValidation($priceSetId, $fields, &$error) {
    // check for at least one positive
    // amount price field should be selected.
    $priceField = new CRM_Price_DAO_Field();
    $priceField->price_set_id = $priceSetId;
    $priceField->find();

    $priceFields = [];

    while ($priceField->fetch()) {
      $key = "price_{$priceField->id}";
      if (CRM_Utils_Array::value($key, $fields)) {
        $priceFields[$priceField->id] = $fields[$key];
      }
    }

    if (!empty($priceFields)) {
      // we should has to have positive amount.
      $sql = "
SELECT  id, html_type 
FROM  civicrm_price_field 
WHERE  id IN (" . CRM_Utils_Array::implode(',', array_keys($priceFields)) . ')';
      $fieldDAO = CRM_Core_DAO::executeQuery($sql);
      $htmlTypes = [];
      while ($fieldDAO->fetch()) {
        $htmlTypes[$fieldDAO->id] = $fieldDAO->html_type;
      }

      $selectedAmounts = [];


      foreach ($htmlTypes as $fieldId => $type) {
        $options = [];
        CRM_Price_BAO_FieldValue::getValues($fieldId, $options);

        if (empty($options)) {

          continue;

        }

        if ($type == 'Text') {
          foreach ($options as $opId => $option) {
            $selectedAmounts[$opId] = $priceFields[$fieldId] * $option['amount'];
            break;
          }
        }
        elseif (is_array($fields["price_{$fieldId}"])) {
          foreach (array_keys($fields["price_{$fieldId}"]) as $opId) {
            $selectedAmounts[$opId] = $options[$opId]['amount'];
          }
        }
        elseif (in_array($fields["price_{$fieldId}"], array_keys($options))) {
          $selectedAmounts[$fields["price_{$fieldId}"]] = $options[$fields["price_{$fieldId}"]]['amount'];
        }
      }

      list($componentName) = explode(':', $fields['_qf_default']);
      // now we have all selected amount in hand.
      $totalAmount = array_sum($selectedAmounts);
      if ($totalAmount < 0) {
        $error['_qf_default'] = ts('%1 amount can not be less than zero. Please select the options accordingly.', [1 => $componentName]);
      }
    }
    else {
      $error['_qf_default'] = ts("Please select at least one option from price set.");
    }
  }

  static public function getPriceLevels($where = []) {
    if (empty($where)) {
      $where = " (1) ";
    }
    else {
      $where = ' '. CRM_Utils_Array::implode(' AND ', $where).' ';
    }
    $label = "";
    if (strstr($where, 'entity_id')) {
      $label = "cv.label as label, cf.label as field_label, cv.id, cv.amount";
    }
    else {
      if (strstr($where, 'entity_table') && strstr($where, 'civicrm_event')) {
        $label = "CONCAT(cf.label, '-', cv.label) as label, e.title as field_label, cv.id, cv.amount";
      }
      else if (strstr($where, 'entity_table') && strstr($where, 'civicrm_contribution_page')) {
        $label = "CONCAT(cf.label, '-', cv.label) as label, p.title as field_label, cv.id, cv.amount";
      }
      else {
        $label = "CONCAT(cf.label, '-', cv.label) label, ps.title as field_label, cv.id, cv.amount";
      }
    }
    $query = "
SELECT $label
FROM civicrm_price_field_value cv
LEFT JOIN civicrm_price_field cf
  ON cv.price_field_id = cf.id
LEFT JOIN civicrm_price_set_entity ce
  ON ce.price_set_id = cf.price_set_id
LEFT JOIN civicrm_event e
  ON e.id = ce.entity_id AND ce.entity_table = 'civicrm_event'
LEFT JOIN civicrm_contribution_page p
  ON p.id = ce.entity_id AND ce.entity_table = 'civicrm_contribution_page'
LEFT JOIN civicrm_price_set ps
  ON ce.price_set_id = ps.id
WHERE $where
ORDER BY ce.entity_id DESC, cf.id, cf.weight, cv.weight ASC
";
    $dao = CRM_Core_DAO::executeQuery($query);
    $levels = $levelAll = [];
    while ($dao->fetch()) {
      if ($dao->field_label) {
        if ($dao->field_label === $dao->label) {
          // when text field, we only need first label
          $levels[$dao->field_label]['priceset:'.$dao->id] = $dao->field_label.": " . $dao->amount;
        }
        else {
          $levels[$dao->field_label]['priceset:'.$dao->id] = $dao->field_label.' - '.$dao->label . ": " . $dao->amount;
        }
      }
      else {
        $levels['priceset:'.$dao->id] = $dao->label.': '. $dao->amount;
      }
      $levelAll[$dao->field_label][] = $dao->id;
    }
    foreach($levels as $label => &$lev) {
      if (is_array($lev) && count($lev) > 1) {
        $commaSeperated = CRM_Utils_Array::implode(',', $levelAll[$label]);
        $lev['priceset:'.$commaSeperated] = $label.' ('.ts('All').')';
      }
    }

    return $levels;
  }

  /**
   * This function is to make a copy of a price field, including
   * all the fields
   *
   * @param int $id the price field id to copy
   *
   * @return the copy object
   * @access public
   * @static
   */
  static function copy($fid) {
    $fieldsFix = [
      'suffix' => [
        'label' => ' ' . ts("Copy"),
      ]
    ];
    $copy = &CRM_Core_DAO::copyGeneric('CRM_Price_DAO_Field', ['id' => $fid], NULL, $fieldsFix);
    $fieldValues = &CRM_Core_DAO::copyGeneric('CRM_Price_DAO_FieldValue', ['price_field_id' => $fid], ['price_field_id' => $copy->id]);


    CRM_Utils_Hook::copy('PriceField', $copy);
    return $copy;

  }
}

